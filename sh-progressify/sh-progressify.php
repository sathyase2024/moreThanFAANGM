<?php
/**
 * Plugin Name: SH Progressify – PWA Mock (Sri Hayavadhana)
 * Plugin URI: https://example.com/
 * Description: A demo WordPress plugin that provides a mock admin UI inspired by Progressify to configure a PWA. Built for Sri Hayavadhana.
 * Version: 0.1.0
 * Author: SH
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sh-progressify
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'SHP_PLUGIN_FILE', __FILE__ );
define( 'SHP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SHP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Autoload includes if needed in future
require_once SHP_PLUGIN_DIR . 'includes/class-shp-admin.php';

/**
 * Bootstrap the plugin.
 */
function shp_bootstrap_plugin() {
    // Initialize admin UI/controller.
    new SHP_Admin();
}
add_action( 'plugins_loaded', 'shp_bootstrap_plugin' );

/**
 * Basic activation hook (reserved for future use such as capabilities/rewrite).
 */
function shp_on_activation() {
    // Placeholder for activation tasks.
}
register_activation_hook( __FILE__, 'shp_on_activation' );

/**
 * Basic deactivation hook.
 */
function shp_on_deactivation() {
    // Placeholder for deactivation tasks.
}
register_deactivation_hook( __FILE__, 'shp_on_deactivation' );

