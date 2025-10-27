<?php
/**
 * Plugin Name: SH Process Flow
 * Description: Responsive, animated "Our Process" timeline (desktop) and accordion (mobile) using shortcodes.
 * Version: 1.0.0
 * Author: SH
 * License: GPL-2.0-or-later
 * Requires at least: 5.6
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) { exit; }

if (!class_exists('SH_Process_Flow')) {
    class SH_Process_Flow {
        const VERSION = '1.0.0';

        private static $instance = null;

        public static function instance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        private function __construct() {
            add_action('init', [$this, 'register_shortcodes']);
            add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        }

        public function register_assets() {
            $handle = 'sh-process';
            $base   = plugin_dir_url(__FILE__);

            wp_register_style($handle, $base . 'assets/process.css', [], self::VERSION);
            wp_register_script($handle, $base . 'assets/process.js', [], self::VERSION, true);
            wp_localize_script($handle, 'SHProcess', [
                'breakpoint' => 992,
            ]);
        }

        public function register_shortcodes() {
            add_shortcode('sh_process', [$this, 'shortcode_process']);
            add_shortcode('sh_step', [$this, 'shortcode_step']);
        }

        public function shortcode_process($atts, $content = null) {
            $atts = shortcode_atts([
                'id'     => 'process-' . wp_generate_uuid4(),
                'active' => '1',
                'class'  => '',
            ], $atts, 'sh_process');

            $container_id = esc_attr($atts['id']);
            $active       = max(1, intval($atts['active']));
            $extra_class  = sanitize_html_class($atts['class']);

            wp_enqueue_style('sh-process');
            wp_enqueue_script('sh-process');

            $inner = do_shortcode($content);

            $classes = trim('sh-process ' . $extra_class);

            return '<section id="' . $container_id . '" class="' . $classes . '" data-active="' . $active . '"><div class="sh-process__steps">' . $inner . '</div></section>';
        }

        public function shortcode_step($atts, $content = null) {
            $atts = shortcode_atts([
                'index'    => '1',
                'title'    => '',
                'subtitle' => '',
                'icon'     => '',
            ], $atts, 'sh_step');

            $index    = intval($atts['index']);
            $title    = esc_html($atts['title']);
            $subtitle = esc_html($atts['subtitle']);
            $icon     = esc_url($atts['icon']);
            $body     = wpautop(do_shortcode($content));

            $marker = $icon
                ? '<img src="' . $icon . '" alt="" class="sh-step__icon" loading="lazy" />'
                : '<span class="sh-step__dot" aria-hidden="true"></span>';

            return '\n<article class="sh-step" data-index="' . $index . '">\n\t<button class="sh-step__tab" role="tab" aria-selected="false" aria-controls="sh-step-panel-' . $index . '" tabindex="0">\n\t\t<span class="sh-step__marker">' . $marker . '<span class="sh-step__num">' . $index . '</span></span>\n\t\t<span class="sh-step__titles"><span class="sh-step__title">' . $title . '</span><span class="sh-step__subtitle">' . $subtitle . '</span></span>\n\t</button>\n\t<div class="sh-step__panel" id="sh-step-panel-' . $index . '" role="tabpanel">' . $body . '</div>\n</article>';
        }
    }
}

SH_Process_Flow::instance();

