<?php

namespace WFP;

use WFP\Admin\Menu;
use WFP\Api\Routes;
use WFP\Shortcodes\AdminDashboard;
use WFP\Shortcodes\EmployeeDashboard;

if (!defined('ABSPATH')) {
    exit;
}

class Plugin
{
    public function run(): void
    {
        add_action('init', [$this, 'registerShortcodes']);
        add_action('rest_api_init', [Routes::class, 'register']);
        add_action('admin_menu', [Menu::class, 'register']);
        add_action('admin_enqueue_scripts', [Menu::class, 'enqueueAdminAssets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueFrontendAssets']);
    }

    public function registerShortcodes(): void
    {
        add_shortcode('wfp_employee_dashboard', [EmployeeDashboard::class, 'render']);
        add_shortcode('wfp_admin_dashboard', [AdminDashboard::class, 'render']);
    }

    public function enqueueFrontendAssets(): void
    {
        wp_register_style('wfp-frontend', WFP_PLUGIN_URL . 'assets/css/frontend.css', [], WFP_VERSION);
        wp_register_script('wfp-frontend', WFP_PLUGIN_URL . 'assets/js/frontend.js', ['wp-element'], WFP_VERSION, true);
    }
}

