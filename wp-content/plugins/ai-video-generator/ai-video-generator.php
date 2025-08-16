<?php
/**
 * Plugin Name: AI Video Generator (Sora-like)
 * Description: Front-end AI video generation via pluggable providers (Sora-like). Includes a shortcode for customers and REST API endpoints.
 * Version: 0.1.0
 * Author: Your Company
 * License: GPLv2 or later
 * Text Domain: ai-video-generator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
if ( ! defined( 'AIVID_VERSION' ) ) {
	define( 'AIVID_VERSION' , '0.1.0' );
}
if ( ! defined( 'AIVID_PLUGIN_FILE' ) ) {
	define( 'AIVID_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'AIVID_PLUGIN_DIR' ) ) {
	define( 'AIVID_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'AIVID_PLUGIN_URL' ) ) {
	define( 'AIVID_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Activation: ensure rewrite rules include our REST routes.
register_activation_hook( __FILE__, function () {
	// Trigger CPT registration before flushing.
	require_once AIVID_PLUGIN_DIR . 'includes/class-plugin.php';
	\AIVID\Plugin::instance()->register_post_types();
	flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function () {
	flush_rewrite_rules();
} );

// Bootstrap the plugin.
add_action( 'plugins_loaded', function () {
	require_once AIVID_PLUGIN_DIR . 'includes/class-plugin.php';
	\AIVID\Plugin::instance()->init();
} );