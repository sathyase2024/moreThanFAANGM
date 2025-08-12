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
            'https://cdn.jsdelivr.net/npm/three@0.148.0/build/three.min.js',
            [],
            '0.148.0',
            true
        );

        wp_register_script(
            'three-gltfloader',
            'https://cdn.jsdelivr.net/npm/three@0.148.0/examples/js/loaders/GLTFLoader.js',
            [ 'three' ],
            '0.148.0',
            true
        );

        wp_register_script(
            'three-orbitcontrols',
            'https://cdn.jsdelivr.net/npm/three@0.148.0/examples/js/controls/OrbitControls.js',
            [ 'three' ],
            '0.148.0',
            true
        );

        // Our viewer script
        wp_register_script(
            'aa3d-viewer',
            AA3D_PLUGIN_URL . 'assets/aa3d-viewer.js',
            [ 'three', 'three-gltfloader', 'three-orbitcontrols' ],
            filemtime( AA3D_PLUGIN_DIR . 'assets/aa3d-viewer.js' ),
            true
        );

        // Designer helper script for iframe fallback handling
        wp_register_script(
            'aa3d-designer',
            AA3D_PLUGIN_URL . 'assets/aa3d-designer.js',
            [],
            filemtime( AA3D_PLUGIN_DIR . 'assets/aa3d-designer.js' ),
            true
        );

        // Room designer script
        wp_register_script(
            'aa3d-room-designer',
            AA3D_PLUGIN_URL . 'assets/aa3d-room-designer.js',
            [ 'three', 'three-orbitcontrols' ],
            filemtime( AA3D_PLUGIN_DIR . 'assets/aa3d-room-designer.js' ),
            true
        );
    }

    public function register_shortcodes() {
        add_shortcode( 'aa3d', [ $this, 'render_viewer_shortcode' ] );
        add_shortcode( 'aa3d_designer', [ $this, 'render_designer_shortcode' ] );
        add_shortcode( 'aa3d_room_designer', [ $this, 'render_room_designer_shortcode' ] );
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
        wp_enqueue_script( 'aa3d-designer' );

        $height = preg_replace('/[^0-9]/', '', $atts['height']);
        $height_style = $height ? ' style="height:'. esc_attr( $height ) .'px"' : '';

        $safe_src = esc_url( $atts['src'] );
        $safe_title = esc_attr( $atts['title'] );
        $safe_allow = esc_attr( $atts['allow'] );
        $safe_loading = esc_attr( $atts['loading'] );

        $id = 'aa3d-designer-' . wp_generate_uuid4();

        $html  = '<div id="'. esc_attr($id) .'" class="aa3d-designer-wrapper"'. $height_style .' data-src="'. $safe_src .'">';
        $html .= '<iframe class="aa3d-designer-iframe" src="'. $safe_src .'" title="'. $safe_title .'" allow="'. $safe_allow .'" loading="'. $safe_loading .'" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>';
        $html .= '<div class="aa3d-designer-overlay" hidden>'; 
        $html .= '<div class="aa3d-designer-overlay-content">';
        $html .= '<p>Embedding is blocked by the source site. Open in a new window:</p>';
        $html .= '<a class="aa3d-designer-open" href="'. $safe_src .'" target="_blank" rel="noopener">Open Designer</a>';
        $html .= '</div></div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Shortcode: [aa3d_room_designer width_ft="15" length_ft="20" height_ft="9" screen_in="120" rows="2" seats_per_row="3" row_spacing_ft="4" riser_in="8" layout="5.1" shop_screens_url="" shop_seating_url="" shop_speakers_url=""]
     */
    public function render_room_designer_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'width_ft' => '15',
            'length_ft' => '20',
            'height_ft' => '9',
            'screen_in' => '120',
            'rows' => '2',
            'seats_per_row' => '3',
            'row_spacing_ft' => '4',
            'riser_in' => '8',
            'layout' => '5.1',
            'shop_screens_url' => '',
            'shop_seating_url' => '',
            'shop_speakers_url' => '',
        ], $atts, 'aa3d_room_designer' );

        wp_enqueue_style( 'aa3d-style' );
        wp_enqueue_script( 'aa3d-room-designer' );

        $id = 'aa3d-room-' . wp_generate_uuid4();

        $data_attr_html = ' data-width-ft="' . esc_attr( $atts['width_ft'] ) . '"';
        $data_attr_html .= ' data-length-ft="' . esc_attr( $atts['length_ft'] ) . '"';
        $data_attr_html .= ' data-height-ft="' . esc_attr( $atts['height_ft'] ) . '"';
        $data_attr_html .= ' data-screen-in="' . esc_attr( $atts['screen_in'] ) . '"';
        $data_attr_html .= ' data-rows="' . esc_attr( $atts['rows'] ) . '"';
        $data_attr_html .= ' data-seats-per-row="' . esc_attr( $atts['seats_per_row'] ) . '"';
        $data_attr_html .= ' data-row-spacing-ft="' . esc_attr( $atts['row_spacing_ft'] ) . '"';
        $data_attr_html .= ' data-riser-in="' . esc_attr( $atts['riser_in'] ) . '"';
        $data_attr_html .= ' data-layout="' . esc_attr( $atts['layout'] ) . '"';

        $html  = '<div class="aa3d-room-wrapper">';
        $html .= '<div class="aa3d-room-toolbar">';
        $html .= '<button type="button" class="aa3d-btn aa3d-btn-reset" title="Reset">Reset</button>';
        $html .= '<button type="button" class="aa3d-btn aa3d-btn-fullscreen" title="Fullscreen">Fullscreen</button>';
        $html .= '<button type="button" class="aa3d-btn aa3d-btn-screenshot" title="Screenshot">Screenshot</button>';
        $html .= '<button type="button" class="aa3d-btn aa3d-btn-autorotate" title="Toggle Auto-Rotate">Auto-Rotate</button>';
        $html .= '<button type="button" class="aa3d-btn aa3d-btn-report" title="Report">Report</button>';
        $html .= '<button type="button" class="aa3d-btn aa3d-btn-share" title="Share">Share</button>';
        $html .= '<label class="aa3d-preset-label">Preset <select class="aa3d-select aa3d-select-preset"><option value="">Custom</option><option value="small">Small</option><option value="medium" selected>Medium</option><option value="large">Large</option></select></label>';
        $html .= '</div>';
        $html .= '<div class="aa3d-room-ui">';
        $html .= '<label>Layout <select class="aa3d-room-input aa3d-select" data-key="layout"><option value="5.1"'. selected( $atts['layout'], '5.1', false ) .'>5.1</option><option value="7.1"'. selected( $atts['layout'], '7.1', false ) .'>7.1</option><option value="5.1.2"'. selected( $atts['layout'], '5.1.2', false ) .'>5.1.2</option></select></label>';
        $html .= '<label>Rows <input type="number" class="aa3d-room-input" data-key="rows" min="1" max="4" step="1" value="'. esc_attr( $atts['rows'] ) .'"></label>';
        $html .= '<label>Seats/Row <input type="number" class="aa3d-room-input" data-key="seats_per_row" min="1" max="6" step="1" value="'. esc_attr( $atts['seats_per_row'] ) .'"></label>';
        $html .= '<label>Row Spacing (ft) <input type="number" class="aa3d-room-input" data-key="row_spacing_ft" min="2" max="10" step="0.5" value="'. esc_attr( $atts['row_spacing_ft'] ) .'"></label>';
        $html .= '<label>Riser (in) <input type="number" class="aa3d-room-input" data-key="riser_in" min="0" max="18" step="0.5" value="'. esc_attr( $atts['riser_in'] ) .'"></label>';
        $html .= '<label>Width (ft) <input type="number" class="aa3d-room-input" data-key="width_ft" min="6" max="40" step="0.5" value="'. esc_attr( $atts['width_ft'] ) .'"></label>';
        $html .= '<label>Length (ft) <input type="number" class="aa3d-room-input" data-key="length_ft" min="8" max="60" step="0.5" value="'. esc_attr( $atts['length_ft'] ) .'"></label>';
        $html .= '<label>Height (ft) <input type="number" class="aa3d-room-input" data-key="height_ft" min="7" max="20" step="0.5" value="'. esc_attr( $atts['height_ft'] ) .'"></label>';
        $html .= '<label>Screen (in) <input type="number" class="aa3d-room-input" data-key="screen_in" min="60" max="200" step="1" value="'. esc_attr( $atts['screen_in'] ) .'"></label>';
        if ( ! empty( $atts['shop_screens_url'] ) || ! empty( $atts['shop_seating_url'] ) || ! empty( $atts['shop_speakers_url'] ) ) {
            $html .= '<div class="aa3d-room-shop">';
            if ( ! empty( $atts['shop_screens_url'] ) ) {
                $html .= '<a class="aa3d-btn" target="_blank" rel="noopener" href="' . esc_url( $atts['shop_screens_url'] ) . '">Shop Screens</a>';
            }
            if ( ! empty( $atts['shop_seating_url'] ) ) {
                $html .= '<a class="aa3d-btn" target="_blank" rel="noopener" href="' . esc_url( $atts['shop_seating_url'] ) . '">Shop Seating</a>';
            }
            if ( ! empty( $atts['shop_speakers_url'] ) ) {
                $html .= '<a class="aa3d-btn" target="_blank" rel="noopener" href="' . esc_url( $atts['shop_speakers_url'] ) . '">Shop Speakers</a>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';
        $html .= '<div class="aa3d-room-overlay" aria-live="polite"></div>';
        $html .= '<canvas id="'. esc_attr($id) .'" class="aa3d-canvas aa3d-room-canvas"'. $data_attr_html .'></canvas>';
        $html .= '</div>';

        return $html;
    }
}