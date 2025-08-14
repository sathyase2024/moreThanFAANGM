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
			)
		) );

		ob_start();
		?>
		<div class="wpsa-container">
			<form id="wpsa-form" class="wpsa-form" novalidate>
				<label for="wpsa_url" class="screen-reader-text"><?php esc_html_e( 'Website URL', 'wp-seo-audit' ); ?></label>
				<input type="url" id="wpsa_url" name="url" class="wpsa-input" placeholder="https://example.com" required />
				<button type="submit" class="wpsa-button"><?php esc_html_e( 'Run Audit', 'wp-seo-audit' ); ?></button>
			</form>
			<div class="wpsa-message" aria-live="polite"></div>
			<div class="wpsa-results" hidden></div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * AJAX handler to run the audit.
	 *
	 * @return void
	 */
	public function ajax_run_audit() {
		check_ajax_referer( 'wpsa_run_audit', 'nonce' );

		$url = isset( $_POST['url'] ) ? (string) wp_unslash( $_POST['url'] ) : '';
		$url = trim( $url );

		if ( empty( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid URL.', 'wp-seo-audit' ) ), 400 );
		}

		// Force scheme to http/https only
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

		if ( empty( $results ) ) {
			wp_send_json_error( array( 'message' => __( 'Audit failed. Please try again later.', 'wp-seo-audit' ), 'errors' => $errors ), 500 );
		}

		wp_send_json_success( array(
			'url' => esc_url_raw( $url ),
			'results' => $results,
			'errors' => $errors,
		) );
	}
}