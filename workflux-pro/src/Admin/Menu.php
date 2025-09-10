<?php

namespace WFP\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class Menu
{
    public static function register(): void
    {
        add_menu_page(
            __('WorkFlux Pro', 'workflux-pro'),
            __('WorkFlux Pro', 'workflux-pro'),
            'wfp_manage_settings',
            'workflux-pro',
            [self::class, 'renderDashboard'],
            'dashicons-businessperson',
            58
        );

        add_submenu_page('workflux-pro', __('Employees', 'workflux-pro'), __('Employees', 'workflux-pro'), 'wfp_manage_employees', 'wfp-employees', [self::class, 'renderEmployees']);
        add_submenu_page('workflux-pro', __('Attendance', 'workflux-pro'), __('Attendance', 'workflux-pro'), 'wfp_view_reports', 'wfp-attendance', [self::class, 'renderAttendance']);
        add_submenu_page('workflux-pro', __('Leaves', 'workflux-pro'), __('Leaves', 'workflux-pro'), 'wfp_approve_leave', 'wfp-leaves', [self::class, 'renderLeaves']);
        add_submenu_page('workflux-pro', __('Projects', 'workflux-pro'), __('Projects', 'workflux-pro'), 'wfp_manage_projects', 'wfp-projects', [self::class, 'renderProjects']);
        add_submenu_page('workflux-pro', __('Reports', 'workflux-pro'), __('Reports', 'workflux-pro'), 'wfp_view_reports', 'wfp-reports', [self::class, 'renderReports']);
        add_submenu_page('workflux-pro', __('Settings', 'workflux-pro'), __('Settings', 'workflux-pro'), 'wfp_manage_settings', 'wfp-settings', [self::class, 'renderSettings']);

        // Employee dashboard (hidden from menu for non-employees)
        add_menu_page(
            __('My Dashboard', 'workflux-pro'),
            __('My Dashboard', 'workflux-pro'),
            'wfp_clock_attendance',
            'wfp-my-dashboard',
            [self::class, 'renderEmployeeDashboard'],
            'dashicons-clipboard',
            59
        );
    }

    public static function enqueueAdminAssets($hook): void
    {
        if (strpos($hook, 'workflux-pro') === false && strpos($hook, 'wfp-') === false) {
            return;
        }
        wp_enqueue_style('wfp-admin', plugins_url('assets/css/admin.css', WFP_PLUGIN_FILE), [], WFP_VERSION);
        wp_enqueue_script('wfp-admin', plugins_url('assets/js/admin.js', WFP_PLUGIN_FILE), ['wp-element'], WFP_VERSION, true);
        wp_localize_script('wfp-admin', 'WFP_ADMIN', [
            'rest' => [
                'root' => esc_url_raw(rest_url('wfp/v1/')),
                'nonce' => wp_create_nonce('wp_rest'),
            ],
        ]);
    }

    public static function renderDashboard(): void
    {
        echo '<div class="wrap"><h1>WorkFlux Pro</h1><div id="wfp-admin-dashboard"></div></div>';
    }

    public static function renderEmployees(): void
    {
        echo '<div class="wrap"><h1>Employees</h1><div id="wfp-admin-employees"></div></div>';
    }

    public static function renderAttendance(): void
    {
        echo '<div class="wrap"><h1>Attendance</h1><div id="wfp-admin-attendance"></div></div>';
    }

    public static function renderLeaves(): void
    {
        echo '<div class="wrap"><h1>Leaves</h1><div id="wfp-admin-leaves"></div></div>';
    }

    public static function renderProjects(): void
    {
        echo '<div class="wrap"><h1>Projects</h1><div id="wfp-admin-projects"></div></div>';
    }

    public static function renderReports(): void
    {
        echo '<div class="wrap"><h1>Reports</h1><div id="wfp-admin-reports"></div></div>';
    }

    public static function renderSettings(): void
    {
        echo '<div class="wrap"><h1>Settings</h1><div id="wfp-admin-settings"></div></div>';
    }

    public static function renderEmployeeDashboard(): void
    {
        // Enqueue frontend assets within admin for this page
        wp_enqueue_style('wfp-admin');
        echo '<div class="wrap"><h1>My Dashboard</h1>';
        echo '<div class="wfp-actions">'
            . '<button class="wfp-btn" data-action="clock-in">Clock In</button> '
            . '<button class="wfp-btn" data-action="clock-out">Clock Out</button>'
            . '</div>';
        echo '<div class="wfp-log" id="wfp-log"></div>';
        // Reuse frontend script for actions
        wp_enqueue_script('wfp-admin');
        echo '</div>';
    }
}

