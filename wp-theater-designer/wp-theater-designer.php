<?php
/**
 * Plugin Name: WP Theater Designer
 * Description: Lightweight theater room designer with canvas visualization. Use shortcode [theater_designer].
 * Version: 0.1.0
 * Author: Cursor AI
 * License: GPLv2 or later
 * Text Domain: wp-theater-designer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WPTD_VERSION' ) ) {
	define( 'WPTD_VERSION', '0.1.0' );
}

if ( ! defined( 'WPTD_PLUGIN_FILE' ) ) {
	define( 'WPTD_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'WPTD_PLUGIN_DIR' ) ) {
	define( 'WPTD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'WPTD_PLUGIN_URL' ) ) {
	define( 'WPTD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Register and enqueue assets.
 */
function wptd_register_assets() {
	wp_register_style(
		'wptd-style',
		plugins_url( 'assets/css/designer.css', WPTD_PLUGIN_FILE ),
		[],
		WPTD_VERSION
	);

	wp_register_script(
		'wptd-script',
		plugins_url( 'assets/js/designer.js', WPTD_PLUGIN_FILE ),
		[],
		WPTD_VERSION,
		true
	);

	wp_localize_script( 'wptd-script', 'wptdSettings', [
		'pluginUrl' => WPTD_PLUGIN_URL,
		'version'   => WPTD_VERSION,
	] );
}
add_action( 'init', 'wptd_register_assets' );

/**
 * Shortcode renderer for [theater_designer]
 *
 * @param array $atts
 * @return string
 */
function wptd_render_shortcode( $atts ) {
	$atts = shortcode_atts( [
		'unit'           => 'ft',
		'room_width'     => '16',
		'room_depth'     => '20',
		'ceiling_height' => '9',
		'screen_width'   => '10',
	], $atts, 'theater_designer' );

	wp_enqueue_style( 'wptd-style' );
	wp_enqueue_script( 'wptd-script' );

	$container_id = 'wptd-root-' . wp_generate_uuid4();
	$config       = [
		'unit'           => $atts['unit'],
		'roomWidth'      => floatval( $atts['room_width'] ),
		'roomDepth'      => floatval( $atts['room_depth'] ),
		'ceilingHeight'  => floatval( $atts['ceiling_height'] ),
		'screenWidth'    => floatval( $atts['screen_width'] ),
	];

	$markup  = '<div class="wptd-wrapper">';
	$markup .= '<div id="' . esc_attr( $container_id ) . '" class="wptd-root" data-config="' . esc_attr( wp_json_encode( $config ) ) . '"></div>';
	$markup .= '<noscript>' . esc_html__( 'This tool requires JavaScript enabled.', 'wp-theater-designer' ) . '</noscript>';
	$markup .= '</div>';

	// Inline bootstrap to initialize on this container only.
	$inline = 'document.addEventListener("DOMContentLoaded",function(){ if(window.WPTheaterDesigner){ WPTheaterDesigner.mount("' . esc_js( $container_id ) . '"); }});';
	wp_add_inline_script( 'wptd-script', $inline );

	return $markup;
}
add_shortcode( 'theater_designer', 'wptd_render_shortcode' );