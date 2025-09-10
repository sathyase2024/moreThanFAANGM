<?php

namespace WFP\Shortcodes;

if (!defined('ABSPATH')) {
    exit;
}

class EmployeeDashboard
{
    public static function render($atts = []): string
    {
        wp_enqueue_style('wfp-frontend');
        wp_enqueue_script('wfp-frontend');
        wp_localize_script('wfp-frontend', 'WFP', [
            'rest' => [
                'root' => esc_url_raw(rest_url('wfp/v1/')),
                'nonce' => wp_create_nonce('wp_rest'),
            ],
        ]);

        ob_start();
        ?>
        <div class="wfp-employee-dashboard" id="wfp-employee-dashboard-shortcode">
            <div class="wfp-actions">
                <button class="wfp-btn" data-action="clock-in">Clock In</button>
                <button class="wfp-btn" data-action="clock-out">Clock Out</button>
            </div>
            <div class="wfp-log" id="wfp-log"></div>
        </div>
        <?php
        return (string) ob_get_clean();
    }
}

