<?php
/**
 * Plugin Name: SEO Audit by PageSpeed Insights
 * Description: Run free SEO and performance audits for any URL via Google PageSpeed Insights. Provides a shortcode [seo_audit] and a settings page to configure the API key.
 * Version: 0.1.0
 * Author: nagarajarao
 * Text Domain: wp-seo-audit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPSA_VERSION', '0.1.0' );
define( 'WPSA_PLUGIN_FILE', __FILE__ );
define( 'WPSA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPSA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once WPSA_PLUGIN_DIR . 'includes/class-wpsa-plugin.php';
require_once WPSA_PLUGIN_DIR . 'includes/class-wpsa-pagespeed.php';
require_once WPSA_PLUGIN_DIR . 'includes/class-wpsa-admin.php';
require_once WPSA_PLUGIN_DIR . 'includes/class-wpsa-frontend.php';
require_once WPSA_PLUGIN_DIR . 'includes/class-wpsa-mailer.php';

function wpsa_bootstrap() {
	$plugin = new WPSA_Plugin();
	$plugin->init();
}
add_action( 'plugins_loaded', 'wpsa_bootstrap' );

register_activation_hook( __FILE__, function() {
	if ( ! get_option( 'wpsa_settings' ) ) {
		add_option( 'wpsa_settings', array(
			'api_key' => '',
			'cache_ttl' => 1800,
			'recipient_email' => 'hello@digitalcruz.com',
			'from_email' => 'hello@digitalcruz.com',
			'autoresponder_enabled' => 1,
		) );
	}
} );