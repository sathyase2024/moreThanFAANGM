<?php
/**
 * Plugin Name: WorkFlux Pro
 * Plugin URI: https://example.com/workflux-pro
 * Description: All-in-one employee management, projects, attendance, leave approvals with hierarchical escalation, and reporting.
 * Version: 0.1.0
 * Author: Nagarajarao C R — Sri Hayavadhana Info-Tech
 * Author URI: https://example.com
 * Text Domain: workflux-pro
 * Domain Path: /languages
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Constants
define( 'WFP_VERSION', '0.1.0' );
define( 'WFP_PLUGIN_FILE', __FILE__ );
define( 'WFP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'WFP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WFP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Includes
require_once WFP_PLUGIN_DIR . 'includes/class-wfp-activator.php';
require_once WFP_PLUGIN_DIR . 'includes/class-wfp-deactivator.php';
require_once WFP_PLUGIN_DIR . 'includes/class-wfp-roles.php';
require_once WFP_PLUGIN_DIR . 'includes/class-wfp-migrations.php';
require_once WFP_PLUGIN_DIR . 'includes/class-wfp-rest.php';
require_once WFP_PLUGIN_DIR . 'includes/class-wfp-admin.php';
require_once WFP_PLUGIN_DIR . 'includes/class-wfp-shortcodes.php';

// Activation/Deactivation
register_activation_hook( __FILE__, [ 'WFP_Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'WFP_Deactivator', 'deactivate' ] );

// Bootstrap
function wfp_bootstrap() {
    // Roles and capabilities ensured every load (idempotent)
    WFP_Roles::register_roles_and_capabilities();

    // Migrations (safe, version-checked)
    ( new WFP_Migrations() )->maybe_run();

    // Admin hooks
    ( new WFP_Admin() )->register();

    // Shortcodes
    ( new WFP_Shortcodes() )->register();

    // REST API
    add_action( 'rest_api_init', function () {
        ( new WFP_REST() )->register_routes();
    } );
}
add_action( 'plugins_loaded', 'wfp_bootstrap' );

// Assets
function wfp_enqueue_assets() {
    $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
    wp_register_style( 'wfp-admin', WFP_PLUGIN_URL . 'assets/css/admin.css', [], WFP_VERSION );
    wp_register_script( 'wfp-admin', WFP_PLUGIN_URL . 'assets/js/admin.js', [ 'jquery' ], WFP_VERSION, true );
    wp_register_style( 'wfp-frontend', WFP_PLUGIN_URL . 'assets/css/frontend.css', [], WFP_VERSION );
    wp_register_script( 'wfp-frontend', WFP_PLUGIN_URL . 'assets/js/frontend.js', [ 'jquery' ], WFP_VERSION, true );
}
add_action( 'init', 'wfp_enqueue_assets' );

