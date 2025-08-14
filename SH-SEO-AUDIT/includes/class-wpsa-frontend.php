<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPSA_Frontend {
	/**
	 * @var WPSA_Plugin
	 */
	protected $plugin;

	/**
	 * @param WPSA_Plugin $plugin
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;

		add_shortcode( 'seo_audit', array( $this, 'shortcode_form' ) );

		add_action( 'wp_ajax_run_wpsa_audit', array( $this, 'ajax_run_audit' ) );
		add_action( 'wp_ajax_nopriv_run_wpsa_audit', array( $this, 'ajax_run_audit' ) );
	}

	/**
	 * Render the audit form and container.
	 *
	 * @return string
	 */
	public function shortcode_form() {
		wp_enqueue_style( 'wpsa-frontend-css' );
		wp_enqueue_script( 'wpsa-frontend-js' );
		wp_localize_script( 'wpsa-frontend-js', 'wpsa_ajax', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'wpsa_run_audit' ),
			'i18n' => array(
				'running' => __( 'Running audit...', 'wp-seo-audit' ),
				'invalidUrl' => __( 'Please enter a valid URL (including http/https).', 'wp-seo-audit' ),
				'invalidCompany' => __( 'Please enter your company name.', 'wp-seo-audit' ),
				'invalidEmail' => __( 'Please enter a valid email address.', 'wp-seo-audit' ),
				'invalidPhone' => __( 'Please enter a valid contact number.', 'wp-seo-audit' ),
				'sent' => __( 'Report emailed. Check your inbox.', 'wp-seo-audit' ),
			)
		) );

		ob_start();
		?>
		<div class="wpsa-container">
			<form id="wpsa-form" class="wpsa-form" novalidate>
				<div class="wpsa-field">
					<label for="wpsa_company"><?php esc_html_e( 'Company Name', 'wp-seo-audit' ); ?></label>
					<input type="text" id="wpsa_company" name="company" class="wpsa-input" placeholder="Your Company" required />
				</div>
				<div class="wpsa-field">
					<label for="wpsa_email"><?php esc_html_e( 'Email', 'wp-seo-audit' ); ?></label>
					<input type="email" id="wpsa_email" name="email" class="wpsa-input" placeholder="you@example.com" required />
				</div>
				<div class="wpsa-field">
					<label for="wpsa_phone"><?php esc_html_e( 'Contact Number', 'wp-seo-audit' ); ?></label>
					<input type="tel" id="wpsa_phone" name="phone" class="wpsa-input" placeholder="+1 555 123 4567" required />
				</div>
				<div class="wpsa-field">
					<label for="wpsa_url"><?php esc_html_e( 'Website URL', 'wp-seo-audit' ); ?></label>
					<input type="url" id="wpsa_url" name="url" class="wpsa-input" placeholder="https://example.com" required />
				</div>
				<button type="submit" class="wpsa-button"><?php esc_html_e( 'Run Audit', 'wp-seo-audit' ); ?></button>
			</form>
			<div class="wpsa-message" aria-live="polite"></div>
			<div class="wpsa-results" hidden></div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * AJAX handler to run the audit and send emails.
	 *
	 * @return void
	 */
	public function ajax_run_audit() {
		check_ajax_referer( 'wpsa_run_audit', 'nonce' );

		$company = isset( $_POST['company'] ) ? sanitize_text_field( wp_unslash( $_POST['company'] ) ) : '';
		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$url = isset( $_POST['url'] ) ? (string) wp_unslash( $_POST['url'] ) : '';
		$url = trim( $url );

		if ( empty( $company ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter your company name.', 'wp-seo-audit' ) ), 400 );
		}
		if ( empty( $email ) || ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'wp-seo-audit' ) ), 400 );
		}
		if ( empty( $phone ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid contact number.', 'wp-seo-audit' ) ), 400 );
		}
		if ( empty( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid URL.', 'wp-seo-audit' ) ), 400 );
		}

		$parsed = wp_parse_url( $url );
		if ( empty( $parsed['scheme'] ) || ! in_array( strtolower( $parsed['scheme'] ), array( 'http', 'https' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'URL must start with http or https.', 'wp-seo-audit' ) ), 400 );
		}

		$api_key = $this->plugin->get_api_key();
		$cache_ttl = $this->plugin->get_cache_ttl();

		$strategies = array( 'mobile', 'desktop' );
		$results = array();
		$errors = array();

		foreach ( $strategies as $strategy ) {
			$response = WPSA_PageSpeed::run_audit( $url, $strategy, $api_key, $cache_ttl );
			if ( is_wp_error( $response ) ) {
				$errors[ $strategy ] = $response->get_error_message();
			} else {
				$results[ $strategy ] = $response;
			}
		}

		$lead = array(
			'company' => $company,
			'email' => $email,
			'phone' => $phone,
			'url' => esc_url_raw( $url ),
		);

		$sent_owner = WPSA_Mailer::send_owner_email( $this->plugin, $lead, $results, $errors );
		$settings = $this->plugin->get_settings();
		$sent_customer = false;
		if ( ! empty( $settings['autoresponder_enabled'] ) ) {
			$sent_customer = WPSA_Mailer::send_customer_email( $this->plugin, $lead, $results, $errors );
		}

		if ( empty( $results ) ) {
			wp_send_json_error( array( 'message' => __( 'Audit failed. Please try again later.', 'wp-seo-audit' ), 'errors' => $errors ), 500 );
		}

		wp_send_json_success( array(
			'url' => esc_url_raw( $url ),
			'lead' => $lead,
			'results' => $results,
			'errors' => $errors,
			'email_owner_sent' => (bool) $sent_owner,
			'email_customer_sent' => (bool) $sent_customer,
		) );
	}
}