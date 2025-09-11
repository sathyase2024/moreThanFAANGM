<?php
/**
 * Plugin Name: SH Timeline (WPBakery + Shortcode)
 * Description: Alternating vertical timeline (Deejos-like) with shortcode [sh_timeline] and a WPBakery element.
 * Version: 1.0.0
 * Author: nagarajarao
 * Author URI: https://srihayavadhana.com
 * License: GPL-2.0-or-later
 * Text Domain: sh-timeline
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Sh_Timeline_Plugin' ) ) {
    class Sh_Timeline_Plugin {
        const VERSION = '1.0.0';

        public function __construct() {
            add_action( 'init', array( $this, 'register_shortcode' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
            add_action( 'vc_before_init', array( $this, 'register_vc_element' ) );

            // CPT + Meta + Shortcode for Construction Timeline
            add_action( 'init', array( $this, 'register_cpt_timeline_step' ) );
            add_action( 'init', array( $this, 'register_taxonomy_timeline_series' ) );
            add_action( 'add_meta_boxes', array( $this, 'register_step_meta_box' ) );
            add_action( 'save_post', array( $this, 'save_step_meta' ) );
            add_shortcode( 'construction_timeline', array( $this, 'render_construction_timeline' ) );
        }

        public function plugin_url() {
            return plugin_dir_url( __FILE__ );
        }

        public function register_assets() {
            wp_register_style(
                'sh-timeline-style',
                $this->plugin_url() . 'assets/css/style.css',
                array(),
                self::VERSION
            );
            wp_register_script(
                'sh-timeline-slider',
                $this->plugin_url() . 'assets/js/slider.js',
                array('jquery'),
                self::VERSION,
                true
            );
            wp_register_script(
                'sh-timeline-inview',
                $this->plugin_url() . 'assets/js/inview.js',
                array(),
                self::VERSION,
                true
            );
        }

        public function register_shortcode() {
            add_shortcode( 'sh_timeline', array( $this, 'render_shortcode' ) );
        }

        /**
         * Render the timeline shortcode.
         *
         * Attributes:
         * - items: VC param_group string or JSON array (fallback)
         * - accent_color: hex/rgb color (optional)
         */
        public function render_shortcode( $atts, $content = null ) {
            $atts = shortcode_atts(
                array(
                    'items'        => '',
                    'accent_color' => '',
                    'autoplay_ms'  => '3500',
                ),
                $atts,
                'sh_timeline'
            );

            // Parse items: support VC param_group and raw JSON.
            $items = array();

            if ( ! empty( $atts['items'] ) ) {
                if ( function_exists( 'vc_param_group_parse_atts' ) ) {
                    $parsed = vc_param_group_parse_atts( $atts['items'] );
                    if ( is_array( $parsed ) ) {
                        $items = $parsed;
                    }
                }

                if ( empty( $items ) ) {
                    // Fallback: try JSON
                    $decoded = json_decode( $atts['items'], true );
                    if ( is_array( $decoded ) ) {
                        $items = $decoded;
                    }
                }
            }

            if ( empty( $items ) ) {
                return '';
            }

            wp_enqueue_style( 'sh-timeline-style' );
            wp_enqueue_script( 'sh-timeline-slider' );

            $accent_style = '';
            if ( ! empty( $atts['accent_color'] ) ) {
                $accent = sanitize_text_field( $atts['accent_color'] );
                $accent_style = ' style="--tl-accent:' . esc_attr( $accent ) . '"';
            }

            $interval_ms = intval( $atts['autoplay_ms'] );
            if ( $interval_ms < 1000 ) {
                $interval_ms = 3500;
            }

            ob_start();
            echo '<div class="sh-timeline"' . $accent_style . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '<div class="sh-tl">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

            $index = 0;
            foreach ( $items as $item ) {
                $index++;
                $is_odd = ( $index % 2 ) === 1;

                $date        = isset( $item['date'] ) ? wp_kses_post( $item['date'] ) : '';
                $title       = isset( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '';
                $text        = isset( $item['text'] ) ? wp_kses_post( $item['text'] ) : '';
                $image_id    = isset( $item['image'] ) ? intval( $item['image'] ) : 0;
                $image_url   = '';
                $gallery_ids = array();
                $gallery_urls = array();

                // Parse gallery (WPBakery attach_images returns comma-separated IDs)
                if ( isset( $item['gallery'] ) && ! empty( $item['gallery'] ) ) {
                    $csv = trim( (string) $item['gallery'] );
                    if ( $csv !== '' ) {
                        $parts = array_filter( array_map( 'trim', explode( ',', $csv ) ) );
                        foreach ( $parts as $pid ) {
                            $int_id = intval( $pid );
                            if ( $int_id > 0 ) {
                                $gallery_ids[] = $int_id;
                            }
                        }
                    }
                }

                // Parse gallery URLs (shortcode-friendly). Accept JSON array or comma-separated URLs.
                if ( isset( $item['gallery_urls'] ) && ! empty( $item['gallery_urls'] ) ) {
                    $raw = trim( (string) $item['gallery_urls'] );
                    $decoded = json_decode( $raw, true );
                    if ( is_array( $decoded ) ) {
                        foreach ( $decoded as $u ) {
                            $u = trim( (string) $u );
                            if ( $u !== '' ) {
                                $gallery_urls[] = esc_url_raw( $u );
                            }
                        }
                    } else {
                        $parts = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
                        foreach ( $parts as $u ) {
                            if ( $u !== '' ) {
                                $gallery_urls[] = esc_url_raw( $u );
                            }
                        }
                    }
                }

                if ( $image_id > 0 ) {
                    $src = wp_get_attachment_image_src( $image_id, 'large' );
                    if ( $src && is_array( $src ) ) {
                        $image_url = $src[0];
                    }
                } else {
                    if ( isset( $item['image_url'] ) && ! empty( $item['image_url'] ) ) {
                        $image_url = esc_url_raw( $item['image_url'] );
                    }
                }

                $side_class = $is_odd ? 'sh-tl-item--odd' : 'sh-tl-item--even';

                echo '<div class="sh-tl-item ' . esc_attr( $side_class ) . '">';
                echo '<span class="sh-tl-dot"></span>';
                echo '<div class="sh-tl-card">';

                if ( ! empty( $date ) ) {
                    echo '<div class="sh-tl-date">' . $date . '</div>';
                }
                // Header: step number + title
                echo '<div class="sh-tl-header">';
                echo '<span class="sh-tl-step">' . esc_html( (string) $index ) . '</span>';
                if ( ! empty( $title ) ) {
                    echo '<h3 class="sh-tl-title">' . esc_html( $title ) . '</h3>';
                }
                echo '</div>';

                // Gallery (takes precedence if provided), else single image
                if ( ! empty( $gallery_ids ) || ! empty( $gallery_urls ) ) {
                    echo '<div class="sh-tl-gallery" data-autoplay="1" data-interval="' . esc_attr( (string) $interval_ms ) . '">';
                    foreach ( $gallery_ids as $g_id ) {
                        $src = wp_get_attachment_image_src( $g_id, 'large' );
                        if ( $src && is_array( $src ) ) {
                            $alt_text = get_post_meta( $g_id, '_wp_attachment_image_alt', true );
                            $alt_out  = ! empty( $alt_text ) ? $alt_text : ( ! empty( $title ) ? $title : 'Timeline image' );
                            echo '<div class="sh-tl-slide"><img src="' . esc_url( $src[0] ) . '" alt="' . esc_attr( $alt_out ) . '" loading="lazy"></div>';
                        }
                    }
                    foreach ( $gallery_urls as $g_url ) {
                        $alt_out  = ! empty( $title ) ? $title : 'Timeline image';
                        echo '<div class="sh-tl-slide"><img src="' . esc_url( $g_url ) . '" alt="' . esc_attr( $alt_out ) . '" loading="lazy"></div>';
                    }
                    echo '</div>';
                } elseif ( ! empty( $image_url ) ) {
                    $alt = ! empty( $title ) ? $title : 'Timeline image';
                    echo '<div class="sh-tl-image"><img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy"></div>';
                }
                if ( ! empty( $text ) ) {
                    echo '<div class="sh-tl-text">' . $text . '</div>';
                }

                echo '</div>';
                echo '</div>';
            }

            echo '</div>';
            echo '</div>';

            return ob_get_clean();
        }

        /**
         * Register WPBakery element if WPBakery is present.
         */
        public function register_vc_element() {
            if ( ! function_exists( 'vc_map' ) ) {
                return;
            }

            vc_map( array(
                'name'        => __( 'SH Timeline', 'sh-timeline' ),
                'base'        => 'sh_timeline',
                'icon'        => 'dashicons-backup',
                'category'    => __( 'Content', 'sh-timeline' ),
                'description' => __( 'Alternating vertical timeline.', 'sh-timeline' ),
                'params'      => array(
                    array(
                        'type'        => 'param_group',
                        'heading'     => __( 'Timeline Items', 'sh-timeline' ),
                        'param_name'  => 'items',
                        'description' => __( 'Add timeline items.', 'sh-timeline' ),
                        'params'      => array(
                            array(
                                'type'        => 'textfield',
                                'heading'     => __( 'Date', 'sh-timeline' ),
                                'param_name'  => 'date',
                                'admin_label' => true,
                            ),
                            array(
                                'type'        => 'textfield',
                                'heading'     => __( 'Title', 'sh-timeline' ),
                                'param_name'  => 'title',
                                'admin_label' => true,
                            ),
                            array(
                                'type'        => 'textarea',
                                'heading'     => __( 'Text', 'sh-timeline' ),
                                'param_name'  => 'text',
                            ),
                            array(
                                'type'        => 'attach_image',
                                'heading'     => __( 'Image (optional)', 'sh-timeline' ),
                                'param_name'  => 'image',
                            ),
                            array(
                                'type'        => 'attach_images',
                                'heading'     => __( 'Gallery (optional, multiple)', 'sh-timeline' ),
                                'param_name'  => 'gallery',
                                'description' => __( 'Select multiple images to show as an auto-sliding gallery.', 'sh-timeline' ),
                            ),
                            array(
                                'type'        => 'textfield',
                                'heading'     => __( 'Gallery URLs (optional)', 'sh-timeline' ),
                                'param_name'  => 'gallery_urls',
                                'description' => __( 'Comma-separated or JSON array of image URLs (for shortcode use).', 'sh-timeline' ),
                            ),
                        ),
                    ),
                    array(
                        'type'        => 'colorpicker',
                        'heading'     => __( 'Accent Color', 'sh-timeline' ),
                        'param_name'  => 'accent_color',
                        'description' => __( 'Set the timeline accent color.', 'sh-timeline' ),
                    ),
                    array(
                        'type'        => 'textfield',
                        'heading'     => __( 'Autoplay Interval (ms)', 'sh-timeline' ),
                        'param_name'  => 'autoplay_ms',
                        'description' => __( 'Controls gallery auto-rotation speed. Default 3500.', 'sh-timeline' ),
                    ),
                ),
            ) );

            // VC element for CPT-based Construction Timeline with Series filter
            $series_options = array( __( 'All Series', 'sh-timeline' ) => '' );
            $terms = get_terms( array(
                'taxonomy'   => 'timeline_series',
                'hide_empty' => false,
            ) );
            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                foreach ( $terms as $term ) {
                    $series_options[ $term->name ] = $term->slug;
                }
            }

            vc_map( array(
                'name'        => __( 'SH Construction Timeline', 'sh-timeline' ),
                'base'        => 'construction_timeline',
                'icon'        => 'dashicons-editor-ol',
                'category'    => __( 'Content', 'sh-timeline' ),
                'description' => __( 'Display Timeline Steps by Series.', 'sh-timeline' ),
                'params'      => array(
                    array(
                        'type'        => 'dropdown',
                        'heading'     => __( 'Series', 'sh-timeline' ),
                        'param_name'  => 'series',
                        'value'       => $series_options,
                        'description' => __( 'Choose a series to display. Leave empty to show all.', 'sh-timeline' ),
                    ),
                    array(
                        'type'        => 'textfield',
                        'heading'     => __( 'Autoplay Interval (ms)', 'sh-timeline' ),
                        'param_name'  => 'autoplay_ms',
                        'description' => __( 'Controls gallery auto-rotation speed. Default 3500.', 'sh-timeline' ),
                    ),
                ),
            ) );
        }

        /**
         * Register Custom Post Type: timeline_step
         */
        public function register_cpt_timeline_step() {
            $labels = array(
                'name'               => __( 'Timeline Steps', 'sh-timeline' ),
                'singular_name'      => __( 'Timeline Step', 'sh-timeline' ),
                'add_new'            => __( 'Add New', 'sh-timeline' ),
                'add_new_item'       => __( 'Add New Timeline Step', 'sh-timeline' ),
                'edit_item'          => __( 'Edit Timeline Step', 'sh-timeline' ),
                'new_item'           => __( 'New Timeline Step', 'sh-timeline' ),
                'view_item'          => __( 'View Timeline Step', 'sh-timeline' ),
                'search_items'       => __( 'Search Timeline Steps', 'sh-timeline' ),
                'not_found'          => __( 'No steps found', 'sh-timeline' ),
                'not_found_in_trash' => __( 'No steps found in Trash', 'sh-timeline' ),
                'menu_name'          => __( 'Timeline Steps', 'sh-timeline' ),
            );

            $args = array(
                'labels'             => $labels,
                'public'             => true,
                'show_in_rest'       => true,
                'supports'           => array( 'title', 'editor', 'thumbnail' ),
                'has_archive'        => false,
                'rewrite'            => array( 'slug' => 'timeline-step' ),
                'menu_position'      => 20,
                'menu_icon'          => 'dashicons-editor-ol',
                'taxonomies'         => array( 'timeline_series' ),
            );

            register_post_type( 'timeline_step', $args );
        }

        /**
         * Taxonomy: timeline_series
         */
        public function register_taxonomy_timeline_series() {
            $labels = array(
                'name'          => __( 'Timeline Series', 'sh-timeline' ),
                'singular_name' => __( 'Timeline Series', 'sh-timeline' ),
                'search_items'  => __( 'Search Series', 'sh-timeline' ),
                'all_items'     => __( 'All Series', 'sh-timeline' ),
                'edit_item'     => __( 'Edit Series', 'sh-timeline' ),
                'update_item'   => __( 'Update Series', 'sh-timeline' ),
                'add_new_item'  => __( 'Add New Series', 'sh-timeline' ),
                'new_item_name' => __( 'New Series Name', 'sh-timeline' ),
                'menu_name'     => __( 'Series', 'sh-timeline' ),
            );
            register_taxonomy( 'timeline_series', array( 'timeline_step' ), array(
                'labels'            => $labels,
                'public'            => true,
                'hierarchical'      => false,
                'show_ui'           => true,
                'show_admin_column' => true,
                'show_in_rest'      => true,
                'rewrite'           => array( 'slug' => 'timeline-series' ),
            ) );
        }

        /**
         * Meta box for Step Number
         */
        public function register_step_meta_box() {
            add_meta_box(
                'sh_tl_step_meta',
                __( 'Step Details', 'sh-timeline' ),
                array( $this, 'render_step_meta_box' ),
                'timeline_step',
                'side',
                'default'
            );
        }

        public function render_step_meta_box( $post ) {
            wp_nonce_field( 'sh_tl_save_step', 'sh_tl_step_nonce' );
            $step_num = get_post_meta( $post->ID, '_sh_tl_step_number', true );
            $step_num = is_numeric( $step_num ) ? intval( $step_num ) : '';
            echo '<p><label for="sh_tl_step_number"><strong>' . esc_html__( 'Step Number', 'sh-timeline' ) . '</strong></label></p>';
            echo '<input type="number" min="0" step="1" id="sh_tl_step_number" name="sh_tl_step_number" value="' . esc_attr( $step_num ) . '" style="width:100%">';
            echo '<p class="description">' . esc_html__( 'Used for ordering and display.', 'sh-timeline' ) . '</p>';

            // Gallery IDs (comma-separated attachment IDs)
            $gallery_ids = get_post_meta( $post->ID, '_sh_tl_gallery_ids', true );
            echo '<hr />';
            echo '<p><label for="sh_tl_gallery_ids"><strong>' . esc_html__( 'Gallery (Attachment IDs)', 'sh-timeline' ) . '</strong></label></p>';
            echo '<input type="text" id="sh_tl_gallery_ids" name="sh_tl_gallery_ids" value="' . esc_attr( $gallery_ids ) . '" placeholder="e.g. 123,456,789" style="width:100%">';
            echo '<p class="description">' . esc_html__( 'Optional: comma-separated media IDs. Use Media Library to find IDs.', 'sh-timeline' ) . '</p>';

            // Gallery URLs (comma-separated or JSON array)
            $gallery_urls = get_post_meta( $post->ID, '_sh_tl_gallery_urls', true );
            echo '<p><label for="sh_tl_gallery_urls"><strong>' . esc_html__( 'Gallery URLs', 'sh-timeline' ) . '</strong></label></p>';
            echo '<textarea id="sh_tl_gallery_urls" name="sh_tl_gallery_urls" rows="3" style="width:100%" placeholder="https://.../a.jpg, https://.../b.jpg">' . esc_textarea( $gallery_urls ) . '</textarea>';
            echo '<p class="description">' . esc_html__( 'Optional: comma-separated or JSON array of image URLs.', 'sh-timeline' ) . '</p>';
        }

        public function save_step_meta( $post_id ) {
            if ( ! isset( $_POST['sh_tl_step_nonce'] ) || ! wp_verify_nonce( $_POST['sh_tl_step_nonce'], 'sh_tl_save_step' ) ) {
                return;
            }
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                return;
            }
            if ( isset( $_POST['post_type'] ) && 'timeline_step' === $_POST['post_type'] ) {
                if ( ! current_user_can( 'edit_post', $post_id ) ) {
                    return;
                }
            }
            if ( isset( $_POST['sh_tl_step_number'] ) ) {
                $num = intval( $_POST['sh_tl_step_number'] );
                update_post_meta( $post_id, '_sh_tl_step_number', $num );
            }
            if ( isset( $_POST['sh_tl_gallery_ids'] ) ) {
                $ids = sanitize_text_field( wp_unslash( $_POST['sh_tl_gallery_ids'] ) );
                update_post_meta( $post_id, '_sh_tl_gallery_ids', $ids );
            }
            if ( isset( $_POST['sh_tl_gallery_urls'] ) ) {
                $urls = wp_kses_post( wp_unslash( $_POST['sh_tl_gallery_urls'] ) );
                update_post_meta( $post_id, '_sh_tl_gallery_urls', $urls );
            }
        }

        /**
         * Shortcode: [construction_timeline]
         */
        public function render_construction_timeline( $atts ) {
            $atts = shortcode_atts( array(
                'series'      => '',
                'autoplay_ms' => '3500',
            ), $atts, 'construction_timeline' );

            $args = array(
                'post_type'      => 'timeline_step',
                'posts_per_page' => -1,
                'meta_key'       => '_sh_tl_step_number',
                'orderby'        => 'meta_value_num',
                'order'          => 'ASC',
                'no_found_rows'  => true,
            );

            $series = trim( (string) $atts['series'] );
            if ( $series !== '' ) {
                $slugs = array_filter( array_map( 'trim', explode( ',', $series ) ) );
                if ( ! empty( $slugs ) ) {
                    $args['tax_query'] = array(
                        array(
                            'taxonomy' => 'timeline_series',
                            'field'    => 'slug',
                            'terms'    => $slugs,
                        ),
                    );
                }
            }

            // Query steps ordered by step number (ascending)
            $query = new WP_Query( $args );

            if ( ! $query->have_posts() ) {
                return '';
            }

            wp_enqueue_style( 'sh-timeline-style' );
            wp_enqueue_script( 'sh-timeline-inview' );
            wp_enqueue_script( 'sh-timeline-slider' );

            $interval_ms = intval( $atts['autoplay_ms'] );
            if ( $interval_ms < 500 ) {
                $interval_ms = 2000;
            }

            ob_start();
            echo '<section class="sh-ct" aria-label="Construction Timeline">';
            echo '<div class="sh-ct-line" aria-hidden="true"></div>';

            $i = 0;
            while ( $query->have_posts() ) {
                $query->the_post();
                $i++;
                $post_id   = get_the_ID();
                $step_num  = get_post_meta( $post_id, '_sh_tl_step_number', true );
                $step_num  = is_numeric( $step_num ) ? intval( $step_num ) : $i;
                $title     = get_the_title();
                $content   = apply_filters( 'the_content', get_the_content() );
                $thumb_id  = get_post_thumbnail_id( $post_id );
                $img_src   = '';
                $img_alt   = '';
                if ( $thumb_id ) {
                    $src = wp_get_attachment_image_src( $thumb_id, 'large' );
                    if ( $src && is_array( $src ) ) {
                        $img_src = $src[0];
                    }
                    $alt = get_post_meta( $thumb_id, '_wp_attachment_image_alt', true );
                    $img_alt = $alt ? $alt : $title;
                }

                // Build gallery arrays from meta
                $gallery_ids_meta  = get_post_meta( $post_id, '_sh_tl_gallery_ids', true );
                $gallery_urls_meta = get_post_meta( $post_id, '_sh_tl_gallery_urls', true );
                $gallery_ids_arr   = array();
                $gallery_urls_arr  = array();

                if ( ! empty( $gallery_ids_meta ) ) {
                    $parts = array_filter( array_map( 'trim', explode( ',', (string) $gallery_ids_meta ) ) );
                    foreach ( $parts as $pid ) {
                        $int_id = intval( $pid );
                        if ( $int_id > 0 ) {
                            $gallery_ids_arr[] = $int_id;
                        }
                    }
                }
                if ( ! empty( $gallery_urls_meta ) ) {
                    $raw = trim( (string) $gallery_urls_meta );
                    $decoded = json_decode( $raw, true );
                    if ( is_array( $decoded ) ) {
                        foreach ( $decoded as $u ) {
                            $u = trim( (string) $u );
                            if ( $u !== '' ) {
                                $gallery_urls_arr[] = esc_url_raw( $u );
                            }
                        }
                    } else {
                        $parts = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
                        foreach ( $parts as $u ) {
                            if ( $u !== '' ) {
                                $gallery_urls_arr[] = esc_url_raw( $u );
                            }
                        }
                    }
                }

                $side_class = ( $i % 2 === 1 ) ? 'sh-ct-item--odd' : 'sh-ct-item--even';

                echo '<article class="sh-ct-item ' . esc_attr( $side_class ) . '" aria-labelledby="ct-title-' . esc_attr( $post_id ) . '">';
                echo '<div class="sh-ct-card">';
                echo '<header class="sh-ct-header">';
                echo '<span class="sh-ct-step" aria-label="Step ' . esc_attr( (string) $step_num ) . '">' . esc_html( (string) $step_num ) . '</span>';
                echo '<h3 id="ct-title-' . esc_attr( $post_id ) . '" class="sh-ct-title">' . esc_html( $title ) . '</h3>';
                echo '</header>';

                // Render gallery first if available
                if ( ! empty( $gallery_ids_arr ) || ! empty( $gallery_urls_arr ) ) {
                    echo '<div class="sh-tl-gallery" data-autoplay="1" data-interval="' . esc_attr( (string) $interval_ms ) . '">';
                    foreach ( $gallery_ids_arr as $gid ) {
                        $gsrc = wp_get_attachment_image_src( $gid, 'large' );
                        if ( $gsrc && is_array( $gsrc ) ) {
                            $galt = get_post_meta( $gid, '_wp_attachment_image_alt', true );
                            $galt = $galt ? $galt : $title;
                            echo '<div class="sh-tl-slide"><img src="' . esc_url( $gsrc[0] ) . '" alt="' . esc_attr( $galt ) . '" loading="lazy"></div>';
                        }
                    }
                    foreach ( $gallery_urls_arr as $gurl ) {
                        $galt = $title ? $title : 'Timeline image';
                        echo '<div class="sh-tl-slide"><img src="' . esc_url( $gurl ) . '" alt="' . esc_attr( $galt ) . '" loading="lazy"></div>';
                    }
                    echo '</div>';
                } elseif ( ! empty( $img_src ) ) {
                    echo '<figure class="sh-ct-figure">';
                    echo '<img src="' . esc_url( $img_src ) . '" alt="' . esc_attr( $img_alt ) . '">';
                    echo '</figure>';
                }

                // Description: render only when non-empty
                $content_plain = trim( wp_strip_all_tags( $content ) );
                if ( ! empty( $content_plain ) ) {
                    echo '<div class="sh-ct-text">' . $content . '</div>';
                }
                echo '</div>';
                echo '</article>';
            }
            wp_reset_postdata();

            echo '</section>';
            return ob_get_clean();
        }
    }
}

new Sh_Timeline_Plugin();

