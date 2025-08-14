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

		add_action( 'wp_ajax_run_wpsa_audit_step', array( $this, 'ajax_run_audit_step' ) );
		add_action( 'wp_ajax_nopriv_run_wpsa_audit_step', array( $this, 'ajax_run_audit_step' ) );
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
				'progress' => array(
					'init' => __( 'Starting…', 'wp-seo-audit' ),
					'mobile' => __( 'Running mobile audit…', 'wp-seo-audit' ),
					'desktop' => __( 'Running desktop audit…', 'wp-seo-audit' ),
					'email' => __( 'Sending emails…', 'wp-seo-audit' ),
					'done' => __( 'Completed', 'wp-seo-audit' ),
				),
			)
		) );

		ob_start();
		?>
		<div class="wpsa-container">
			<form id="wpsa-form" class="wpsa-form" novalidate>
				<div class="wpsa-field">
					<label for="wpsa_company"><?php esc_html_e( 'Company Name', 'wp-seo-audit' ); ?></label>
					<input type="text" id="wpsa_company" name="company" class="wpsa-input" placeholder="Your Company" />
				</div>
				<div class="wpsa-field">
					<label for="wpsa_email"><?php esc_html_e( 'Email', 'wp-seo-audit' ); ?></label>
					<input type="email" id="wpsa_email" name="email" class="wpsa-input" placeholder="you@example.com" required />
				</div>
				<div class="wpsa-field">
					<label for="wpsa_phone"><?php esc_html_e( 'Contact Number', 'wp-seo-audit' ); ?></label>
					<input type="tel" id="wpsa_phone" name="phone" class="wpsa-input" placeholder="+1 555 123 4567" />
				</div>
				<div class="wpsa-field">
					<label for="wpsa_url"><?php esc_html_e( 'Website URL', 'wp-seo-audit' ); ?></label>
					<input type="url" id="wpsa_url" name="url" class="wpsa-input" placeholder="https://example.com" required />
				</div>
				<button type="submit" class="wpsa-button"><?php esc_html_e( 'Run Audit', 'wp-seo-audit' ); ?></button>
			</form>
			<div class="wpsa-progress" aria-live="polite" hidden>
				<div class="wpsa-progress-bar"><span class="wpsa-progress-fill" style="width:0%"></span></div>
				<ul class="wpsa-progress-steps">
					<li data-step="mobile"><?php esc_html_e( 'Mobile', 'wp-seo-audit' ); ?></li>
					<li data-step="desktop"><?php esc_html_e( 'Desktop', 'wp-seo-audit' ); ?></li>
					<li data-step="email"><?php esc_html_e( 'Email', 'wp-seo-audit' ); ?></li>
				</ul>
			</div>
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

		// Only email and URL are required
		if ( empty( $email ) || ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'wp-seo-audit' ) ), 400 );
		}
		if ( empty( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid URL.', 'wp-seo-audit' ) ), 400 );
		}

		$parsed = wp_parse_url( $url );
		if ( empty( $parsed['scheme'] ) || ! in_array( strtolower( $parsed['scheme'] ), array( 'http', 'https' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'URL must start with http or https.', 'wp-seo-audit' ) ), 400 );
		}

		// Check if outbound HTTP is blocked by WP config
		if ( defined( 'WP_HTTP_BLOCK_EXTERNAL' ) && WP_HTTP_BLOCK_EXTERNAL ) {
			$allowed = defined( 'WP_ACCESSIBLE_HOSTS' ) ? WP_ACCESSIBLE_HOSTS : '';
			if ( false === stripos( (string) $allowed, 'googleapis.com' ) ) {
				wp_send_json_error( array(
					'message' => __( 'Server is blocking outbound HTTP requests. Please allow googleapis.com in WP_ACCESSIBLE_HOSTS.', 'wp-seo-audit' ),
					'hint' => 'Define WP_ACCESSIBLE_HOSTS=*.googleapis.com in wp-config.php or disable WP_HTTP_BLOCK_EXTERNAL.',
				), 503 );
			}
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

		$settings = $this->plugin->get_settings();

		// If both strategies failed, include a more actionable message
		if ( empty( $results ) ) {
			$first_error = '';
			if ( ! empty( $errors ) ) {
				$first_error = reset( $errors );
			}
			$hint = '';
			if ( empty( $api_key ) ) {
				$hint = __( 'Tip: Add a Google PageSpeed API key in Settings → SEO Audit to avoid public quota limits.', 'wp-seo-audit' );
			}
			wp_send_json_error( array(
				'message' => $first_error ? sprintf( __( 'Audit failed: %s', 'wp-seo-audit' ), $first_error ) : __( 'Audit failed. Please try again later.', 'wp-seo-audit' ),
				'errors' => $errors,
				'hint' => $hint,
			), 502 );
		}

		// Send emails after having at least one result
		$sent_owner = WPSA_Mailer::send_owner_email( $this->plugin, $lead, $results, $errors );
		$sent_customer = false;
		if ( ! empty( $settings['autoresponder_enabled'] ) ) {
			$sent_customer = WPSA_Mailer::send_customer_email( $this->plugin, $lead, $results, $errors );
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

	/**
	 * Step-based audit to improve perceived performance.
	 */
	public function ajax_run_audit_step() {
		check_ajax_referer( 'wpsa_run_audit', 'nonce' );

		$step = isset( $_POST['step'] ) ? sanitize_key( wp_unslash( $_POST['step'] ) ) : '';
		$job_id = isset( $_POST['job_id'] ) ? sanitize_text_field( wp_unslash( $_POST['job_id'] ) ) : '';
		$cache_ttl = $this->plugin->get_cache_ttl();

		if ( ! in_array( $step, array( 'mobile', 'desktop', 'email' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid step.', 'wp-seo-audit' ) ), 400 );
		}

		$job_key = '';
		$job = null;
		if ( ! empty( $job_id ) ) {
			$job_key = 'wpsa_job_' . preg_replace( '/[^a-zA-Z0-9_-]/', '', $job_id );
			$job = get_transient( $job_key );
		}

		if ( ! $job ) {
			// Initialize job with lead and URL
			$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
			$url = isset( $_POST['url'] ) ? (string) wp_unslash( $_POST['url'] ) : '';
			$company = isset( $_POST['company'] ) ? sanitize_text_field( wp_unslash( $_POST['company'] ) ) : '';
			$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';

			if ( empty( $email ) || ! is_email( $email ) ) {
				wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'wp-seo-audit' ) ), 400 );
			}
			if ( empty( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid URL.', 'wp-seo-audit' ) ), 400 );
			}

			$parsed = wp_parse_url( $url );
			if ( empty( $parsed['scheme'] ) || ! in_array( strtolower( $parsed['scheme'] ), array( 'http', 'https' ), true ) ) {
				wp_send_json_error( array( 'message' => __( 'URL must start with http or https.', 'wp-seo-audit' ) ), 400 );
			}

			$job_id = wp_generate_uuid4();
			$job_key = 'wpsa_job_' . $job_id;
			$job = array(
				'created' => time(),
				'lead' => array(
					'company' => $company,
					'email' => $email,
					'phone' => $phone,
					'url' => esc_url_raw( $url ),
				),
				'results' => array(),
				'errors' => array(),
			);
		}

		$api_key = $this->plugin->get_api_key();

		if ( 'mobile' === $step || 'desktop' === $step ) {
			$response = WPSA_PageSpeed::run_audit( $job['lead']['url'], $step, $api_key, $cache_ttl );
			if ( is_wp_error( $response ) ) {
				$job['errors'][ $step ] = $response->get_error_message();
			} else {
				$job['results'][ $step ] = $response;
			}
			set_transient( $job_key, $job, max( 600, (int) $cache_ttl ) );

			$progress = ( 'mobile' === $step ) ? 33 : 66;
			wp_send_json_success( array(
				'job_id' => $job_id,
				'progress' => $progress,
				'step' => $step,
				'partial' => $job['results'],
				'errors' => $job['errors'],
			) );
		}

		if ( 'email' === $step ) {
			if ( empty( $job['results'] ) ) {
				wp_send_json_error( array( 'message' => __( 'Nothing to email. Run the audit first.', 'wp-seo-audit' ) ), 400 );
			}
			$sent_owner = WPSA_Mailer::send_owner_email( $this->plugin, $job['lead'], $job['results'], $job['errors'] );
			$settings = $this->plugin->get_settings();
			$sent_customer = false;
			if ( ! empty( $settings['autoresponder_enabled'] ) ) {
				$sent_customer = WPSA_Mailer::send_customer_email( $this->plugin, $job['lead'], $job['results'], $job['errors'] );
			}
			// Extend job TTL briefly after email
			set_transient( $job_key, $job, max( 600, (int) $cache_ttl ) );
			wp_send_json_success( array(
				'job_id' => $job_id,
				'progress' => 100,
				'lead' => $job['lead'],
				'results' => $job['results'],
				'errors' => $job['errors'],
				'email_owner_sent' => (bool) $sent_owner,
				'email_customer_sent' => (bool) $sent_customer,
				'step' => 'email',
			) );
		}
	}
}