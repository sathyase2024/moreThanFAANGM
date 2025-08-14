<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPSA_Plugin {
	/**
	 * @var WPSA_Admin
	 */
	protected $admin;

	/**
	 * @var WPSA_Frontend
	 */
	protected $frontend;

	/**
	 * Initialize hooks and services.
	 *
	 * @return void
	 */
	public function init() {
		$this->admin = new WPSA_Admin( $this );
		$this->frontend = new WPSA_Frontend( $this );

		add_action( 'init', array( $this, 'register_assets' ) );
	}

	/**
	 * Register scripts and styles.
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_style(
			'wpsa-frontend-css',
			WPSA_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			WPSA_VERSION
		);

		wp_register_script(
			'wpsa-frontend-js',
			WPSA_PLUGIN_URL . 'assets/js/frontend.js',
			array( 'jquery' ),
			WPSA_VERSION,
			true
		);
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = get_option( 'wpsa_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		$defaults = array(
			'api_key' => '',
			'cache_ttl' => 1800,
		);
		return wp_parse_args( $settings, $defaults );
	}

	/**
	 * Get the configured API key.
	 *
	 * @return string
	 */
	public function get_api_key() {
		$settings = $this->get_settings();
		return isset( $settings['api_key'] ) ? (string) $settings['api_key'] : '';
	}

	/**
	 * Get cache TTL seconds.
	 *
	 * @return int
	 */
	public function get_cache_ttl() {
		$settings = $this->get_settings();
		return isset( $settings['cache_ttl'] ) ? (int) $settings['cache_ttl'] : 1800;
	}
}