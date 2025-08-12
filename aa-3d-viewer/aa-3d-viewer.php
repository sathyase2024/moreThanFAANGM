<?php
/**
 * Plugin Name: AA 3D Viewer
 * Description: Lightweight 3D viewer for WordPress using Three.js (GLTF/GLB). Provides a shortcode [aa3d] to embed interactive 3D models.
 * Version: 0.1.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 * Text Domain: aa-3d-viewer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define plugin constants.
if ( ! defined( 'AA3D_PLUGIN_FILE' ) ) {
    define( 'AA3D_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'AA3D_PLUGIN_DIR' ) ) {
    define( 'AA3D_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'AA3D_PLUGIN_URL' ) ) {
    define( 'AA3D_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Include core class.
require_once AA3D_PLUGIN_DIR . 'includes/class-aa3d-plugin.php';

// Initialize plugin.
add_action( 'plugins_loaded', [ 'AA3D_Plugin', 'init' ] );