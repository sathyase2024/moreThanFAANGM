<?php

namespace WFP\Shortcodes;

if (!defined('ABSPATH')) {
    exit;
}

class AdminDashboard
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
        <div class="wfp-admin-dashboard" id="wfp-admin-dashboard-shortcode">
            <div class="wfp-grid">
                <div class="wfp-card" data-card="attendance">Attendance</div>
                <div class="wfp-card" data-card="leaves">Leaves</div>
                <div class="wfp-card" data-card="projects">Projects</div>
                <div class="wfp-card" data-card="tasks">Tasks</div>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }
}

