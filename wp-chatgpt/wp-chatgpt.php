<?php
/**
 * Plugin Name: WP ChatGPT AI Chatbot
 * Description: Adds a ChatGPT-like AI chatbot to your WordPress site via a floating widget and shortcode.
 * Version: 1.0.0
 * Author: Cursor AI
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-chatgpt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Constants
if ( ! defined( 'WP_CHATGPT_VERSION' ) ) {
	define( 'WP_CHATGPT_VERSION', '1.0.0' );
}
if ( ! defined( 'WP_CHATGPT_PLUGIN_FILE' ) ) {
	define( 'WP_CHATGPT_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'WP_CHATGPT_PLUGIN_DIR' ) ) {
	define( 'WP_CHATGPT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'WP_CHATGPT_PLUGIN_URL' ) ) {
	define( 'WP_CHATGPT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

require_once WP_CHATGPT_PLUGIN_DIR . 'includes/class-wp-chatgpt.php';

/**
 * Bootstrap the plugin
 */
function wp_chatgpt_bootstrap() {
	$plugin = \WP_ChatGPT_Plugin::instance();
	$plugin->init();
}
add_action( 'plugins_loaded', 'wp_chatgpt_bootstrap' );

/**
 * Activation: add default options
 */
function wp_chatgpt_activate() {
	$defaults = \WP_ChatGPT_Plugin::get_default_settings();
	$existing = get_option( 'wp_chatgpt_settings' );
	if ( ! is_array( $existing ) ) {
		add_option( 'wp_chatgpt_settings', $defaults, '', false );
	} else {
		update_option( 'wp_chatgpt_settings', wp_parse_args( $existing, $defaults ), false );
	}
}
register_activation_hook( __FILE__, 'wp_chatgpt_activate' );