<?php
/**
 * Plugin Name: SH Timeline (WPBakery + Shortcode)
 * Description: Alternating vertical timeline (Deejos-like) with shortcode [sh_timeline] and a WPBakery element.
 * Version: 1.0.0
 * Author: Your Name
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

            $accent_style = '';
            if ( ! empty( $atts['accent_color'] ) ) {
                $accent = sanitize_text_field( $atts['accent_color'] );
                $accent_style = ' style="--tl-accent:' . esc_attr( $accent ) . '"';
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
                if ( ! empty( $image_url ) ) {
                    $alt = ! empty( $title ) ? $title : 'Timeline image';
                    echo '<div class="sh-tl-image"><img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy"></div>';
                }
                if ( ! empty( $title ) ) {
                    echo '<h3 class="sh-tl-title">' . esc_html( $title ) . '</h3>';
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
                        ),
                    ),
                    array(
                        'type'        => 'colorpicker',
                        'heading'     => __( 'Accent Color', 'sh-timeline' ),
                        'param_name'  => 'accent_color',
                        'description' => __( 'Set the timeline accent color.', 'sh-timeline' ),
                    ),
                ),
            ) );
        }
    }
}

new Sh_Timeline_Plugin();

