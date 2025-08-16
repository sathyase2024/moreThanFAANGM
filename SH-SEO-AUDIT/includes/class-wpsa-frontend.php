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
				'progress' => array(
					'init' => __( 'Starting…', 'wp-seo-audit' ),
					'mobile' => __( 'Running mobile audit…', 'wp-seo-audit' ),
					'desktop' => __( 'Running desktop audit…', 'wp-seo-audit' ),
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
					<input type="email" id="wpsa_email" name="email" class="wpsa-input" placeholder="you@example.com" />
				</div>
				<div class="wpsa-field">
					<label for="wpsa_phone"><?php esc_html_e( 'Contact Number', 'wp-seo-audit' ); ?></label>
					<input type="tel" id="wpsa_phone" name="phone" class="wpsa-input" placeholder="+1 555 123 4567" />
				</div>
				<div class="wpsa-field">
					<label for="wpsa_url"><?php esc_html_e( 'Website URL', 'wp-seo-audit' ); ?></label>
					<input type="url" id="wpsa_url" name="url" class="wpsa-input" placeholder="https://example.com" required />
				</div>
				<div class="wpsa-field">
					<label for="wpsa_strategy"><?php esc_html_e( 'Strategy', 'wp-seo-audit' ); ?></label>
					<select id="wpsa_strategy" name="strategy" class="wpsa-input">
						<option value="both"><?php esc_html_e( 'Mobile + Desktop', 'wp-seo-audit' ); ?></option>
						<option value="mobile"><?php esc_html_e( 'Mobile only', 'wp-seo-audit' ); ?></option>
						<option value="desktop"><?php esc_html_e( 'Desktop only', 'wp-seo-audit' ); ?></option>
					</select>
				</div>
				<div class="wpsa-field">
					<label for="wpsa_categories"><?php esc_html_e( 'Categories', 'wp-seo-audit' ); ?></label>
					<select id="wpsa_categories" name="categories" class="wpsa-input">
						<option value="full"><?php esc_html_e( 'Full (Performance, SEO, Accessibility, Best Practices)', 'wp-seo-audit' ); ?></option>
						<option value="performance"><?php esc_html_e( 'Performance only', 'wp-seo-audit' ); ?></option>
					</select>
				</div>
				<label style="display:flex;align-items:center;gap:6px;margin:4px 0 8px"><input type="checkbox" id="wpsa_nocache" /> <span><?php esc_html_e( 'Refresh results (ignore cache)', 'wp-seo-audit' ); ?></span></label>
				<button type="submit" class="wpsa-button"><?php esc_html_e( 'Run Audit', 'wp-seo-audit' ); ?></button>
			</form>
			<div class="wpsa-progress" aria-live="polite" hidden>
				<div class="wpsa-progress-bar"><span class="wpsa-progress-fill" style="width:0%"></span></div>
				<ul class="wpsa-progress-steps">
					<li data-step="mobile"><?php esc_html_e( 'Mobile', 'wp-seo-audit' ); ?></li>
					<li data-step="desktop"><?php esc_html_e( 'Desktop', 'wp-seo-audit' ); ?></li>
				</ul>
			</div>
			<div class="wpsa-message" aria-live="polite"></div>
			<div class="wpsa-results" hidden></div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * AJAX handler to run the audit and show results only (no email).
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

		wp_send_json_success( array(
			'url' => esc_url_raw( $url ),
			'lead' => $lead,
			'results' => $results,
			'errors' => $errors,
		) );
	}

	/**
	 * Step-based audit to improve perceived performance (no email step).
	 */
	public function ajax_run_audit_step() {
		check_ajax_referer( 'wpsa_run_audit', 'nonce' );

		$step = isset( $_POST['step'] ) ? sanitize_key( wp_unslash( $_POST['step'] ) ) : '';
		$job_id = isset( $_POST['job_id'] ) ? sanitize_text_field( wp_unslash( $_POST['job_id'] ) ) : '';
		$bypass_cache = ! empty( $_POST['bypass_cache'] );
		$cache_ttl = $this->plugin->get_cache_ttl();

		if ( ! in_array( $step, array( 'mobile', 'desktop' ), true ) ) {
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
			$strategy = isset( $_POST['strategy'] ) ? sanitize_text_field( wp_unslash( $_POST['strategy'] ) ) : 'both';
			$categories_select = isset( $_POST['categories'] ) ? sanitize_text_field( wp_unslash( $_POST['categories'] ) ) : 'full';

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
				'flow' => array(
					'strategy' => in_array( $strategy, array( 'mobile', 'desktop', 'both' ), true ) ? $strategy : 'both',
					'categories' => ( 'performance' === $categories_select ) ? array( 'performance' ) : array( 'performance', 'seo', 'accessibility', 'best-practices' ),
				),
				'results' => array(),
				'errors' => array(),
			);
		}

		$api_key = $this->plugin->get_api_key();

		$should_run = ( 'both' === ( $job['flow']['strategy'] ?? 'both' ) ) || ( $step === ( $job['flow']['strategy'] ?? 'both' ) );
		if ( ! $should_run ) {
			// Skip step not requested, return previous state
			$progress = ( 'mobile' === $step ) ? 50 : 100;
			wp_send_json_success( array(
				'job_id' => $job_id,
				'progress' => $progress,
				'step' => $step,
				'partial' => $job['results'],
				'errors' => $job['errors'],
			) );
		}

		$response = WPSA_PageSpeed::run_audit( $job['lead']['url'], $step, $api_key, $cache_ttl, (bool) $bypass_cache, (array) ( $job['flow']['categories'] ?? null ) );
		if ( is_wp_error( $response ) ) {
			$job['errors'][ $step ] = $response->get_error_message();
		} else {
			$job['results'][ $step ] = $response;
		}
		set_transient( $job_key, $job, max( 600, (int) $cache_ttl ) );

		$progress = ( 'mobile' === $step ) ? ( ( 'both' === ( $job['flow']['strategy'] ?? 'both' ) || 'desktop' === ( $job['flow']['strategy'] ?? 'both' ) ) ? 50 : 100 ) : 100;
		wp_send_json_success( array(
			'job_id' => $job_id,
			'progress' => $progress,
			'step' => $step,
			'partial' => $job['results'],
			'errors' => $job['errors'],
		) );
	}
}