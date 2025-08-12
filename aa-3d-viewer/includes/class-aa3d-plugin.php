<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AA3D_Plugin {
    public static function init() {
        $instance = new self();
        add_action( 'init', [ $instance, 'register_shortcodes' ] );
        add_action( 'wp_enqueue_scripts', [ $instance, 'register_assets' ] );
    }

    public function register_assets() {
        // Register styles
        wp_register_style(
            'aa3d-style',
            AA3D_PLUGIN_URL . 'assets/aa3d.css',
            [],
            filemtime( AA3D_PLUGIN_DIR . 'assets/aa3d.css' )
        );

        // Register Three.js + loaders from CDN for simplicity
        wp_register_script(
            'three',
            'https://unpkg.com/three@0.160.0/build/three.min.js',
            [],
            '0.160.0',
            true
        );

        wp_register_script(
            'three-gltfloader',
            'https://unpkg.com/three@0.160.0/examples/js/loaders/GLTFLoader.js',
            [ 'three' ],
            '0.160.0',
            true
        );

        wp_register_script(
            'three-orbitcontrols',
            'https://unpkg.com/three@0.160.0/examples/js/controls/OrbitControls.js',
            [ 'three' ],
            '0.160.0',
            true
        );

        // Our viewer script (ES module compiled to IIFE for WP compatibility)
        wp_register_script(
            'aa3d-viewer',
            AA3D_PLUGIN_URL . 'assets/aa3d-viewer.js',
            [ 'three', 'three-gltfloader', 'three-orbitcontrols' ],
            filemtime( AA3D_PLUGIN_DIR . 'assets/aa3d-viewer.js' ),
            true
        );
    }

    public function register_shortcodes() {
        add_shortcode( 'aa3d', [ $this, 'render_viewer_shortcode' ] );
        add_shortcode( 'aa3d_designer', [ $this, 'render_designer_shortcode' ] );
    }

    /**
     * Shortcode: [aa3d src="/path/model.glb" poster="/path/poster.jpg" ar="true" exposure="1.0" background="#000000" autoRotate="true" cameraFov="45" ]
     */
    public function render_viewer_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'src' => '',
            'poster' => '',
            'ar' => 'false',
            'exposure' => '1.0',
            'background' => '#111111',
            'autoRotate' => 'true',
            'cameraFov' => '45',
            'maxAzimuthAngle' => '',
            'minAzimuthAngle' => '',
            'maxPolarAngle' => '',
            'minPolarAngle' => '',
        ], $atts, 'aa3d' );

        if ( empty( $atts['src'] ) ) {
            return '<em>AA 3D Viewer: missing src attribute.</em>';
        }

        wp_enqueue_style( 'aa3d-style' );
        wp_enqueue_script( 'aa3d-viewer' );

        $id = 'aa3d-' . wp_generate_uuid4();

        $data_attrs = [
            'src' => esc_url( $atts['src'] ),
            'poster' => esc_url( $atts['poster'] ),
            'ar' => esc_attr( $atts['ar'] ),
            'exposure' => esc_attr( $atts['exposure'] ),
            'background' => esc_attr( $atts['background'] ),
            'autorotate' => esc_attr( $atts['autoRotate'] ),
            'camera-fov' => esc_attr( $atts['cameraFov'] ),
            'max-azimuth-angle' => esc_attr( $atts['maxAzimuthAngle'] ),
            'min-azimuth-angle' => esc_attr( $atts['minAzimuthAngle'] ),
            'max-polar-angle' => esc_attr( $atts['maxPolarAngle'] ),
            'min-polar-angle' => esc_attr( $atts['minPolarAngle'] ),
        ];

        $data_attr_html = '';
        foreach ( $data_attrs as $key => $value ) {
            if ( $value !== '' ) {
                $data_attr_html .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
            }
        }

        $html  = '<div class="aa3d-wrapper">';
        $html .= '<canvas id="' . esc_attr( $id ) . '" class="aa3d-canvas"' . $data_attr_html . '></canvas>';
        if ( ! empty( $atts['poster'] ) ) {
            $html .= '<img class="aa3d-poster" src="' . esc_url( $atts['poster'] ) . '" alt="" />';
        }
        $html .= '<button class="aa3d-ar-button" hidden>View in AR</button>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Shortcode: [aa3d_designer src="https://designer.example.com" height="720" title="3D Designer" allow="xr-spatial-tracking; fullscreen" loading="lazy"]
     */
    public function render_designer_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'src' => '',
            'height' => '720',
            'title' => '3D Designer',
            'allow' => 'accelerometer; magnetometer; gyroscope; xr-spatial-tracking; fullscreen',
            'loading' => 'lazy',
        ], $atts, 'aa3d_designer' );

        if ( empty( $atts['src'] ) ) {
            return '<em>AA 3D Designer: missing src attribute.</em>';
        }

        wp_enqueue_style( 'aa3d-style' );

        $height = preg_replace('/[^0-9]/', '', $atts['height']);
        $height_style = $height ? ' style="height:'. esc_attr( $height ) .'px"' : '';

        $html  = '<div class="aa3d-designer-wrapper"'. $height_style .'>';
        $html .= '<iframe class="aa3d-designer-iframe" src="'. esc_url( $atts['src'] ) .'" title="'. esc_attr( $atts['title'] ) .'" allow="'. esc_attr( $atts['allow'] ) .'" loading="'. esc_attr( $atts['loading'] ) .'" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>';
        $html .= '</div>';

        return $html;
    }
}