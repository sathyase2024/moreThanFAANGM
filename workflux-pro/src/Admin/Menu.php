<?php

namespace WFP\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class Menu
{
    public static function register(): void
    {
        // Show admin menus only to management-level users
        if (
            current_user_can('wfp_manage_settings') ||
            current_user_can('wfp_manage_projects') ||
            current_user_can('wfp_manage_employees') ||
            current_user_can('wfp_approve_leave') ||
            current_user_can('wfp_view_reports') ||
            current_user_can('manage_options')
        ) {
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
        }

        // Employee dashboard: only for users who are employees (no management caps)
        if (
            current_user_can('wfp_clock_attendance') &&
            !current_user_can('wfp_manage_settings') &&
            !current_user_can('manage_options') &&
            !current_user_can('wfp_manage_projects') &&
            !current_user_can('wfp_manage_employees') &&
            !current_user_can('wfp_approve_leave') &&
            !current_user_can('wfp_view_reports')
        ) {
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
        echo '<div class="wrap"><h1>Employees</h1>';
        echo '<table class="widefat fixed striped"><thead><tr><th>User</th><th>Email</th><th>Type</th><th>Status</th><th>Actions</th></tr></thead><tbody id="wfp-employees-body"><tr><td colspan="5">Loading...</td></tr></tbody></table>';
        echo '</div>';
    }

    public static function renderAttendance(): void
    {
        echo '<div class="wrap"><h1>Attendance</h1><div id="wfp-admin-attendance"></div></div>';
    }

    public static function renderLeaves(): void
    {
        echo '<div class="wrap"><h1>Leaves</h1>';
        if (current_user_can('wfp_approve_leave')) {
            echo '<h2>Pending Approvals</h2>';
            echo '<table class="widefat fixed striped"><thead><tr><th>Employee</th><th>Type</th><th>Dates</th><th>Reason</th><th>Status</th><th>Actions</th></tr></thead><tbody id="wfp-leaves-body"><tr><td colspan="6">Loading...</td></tr></tbody></table>';
        } else {
            echo '<h2>My Leave Requests</h2>';
            echo '<form id="wfp-leave-form" style="margin:12px 0;display:flex;gap:8px;flex-wrap:wrap">'
               + '<input type="text" name="type" placeholder="Type (e.g., Casual)" required />'
               + '<input type="date" name="start_date" required />'
               + '<input type="date" name="end_date" required />'
               + '<input type="text" name="reason" placeholder="Reason (optional)" />'
               + '<button class="button button-primary" type="submit">Submit</button>'
               + '</form>';
            echo '<table class="widefat fixed striped"><thead><tr><th>Type</th><th>Dates</th><th>Reason</th><th>Status</th></tr></thead><tbody id="wfp-my-leaves-body"><tr><td colspan="4">Loading...</td></tr></tbody></table>';
        }
        echo '</div>';
    }

    public static function renderProjects(): void
    {
        echo '<div class="wrap"><h1>Projects</h1>';
        if (current_user_can('wfp_manage_projects')) {
            echo '<form id="wfp-project-form" style="margin:12px 0;display:flex;gap:8px;flex-wrap:wrap">'
                . '<input type="text" name="name" placeholder="Project name" required />'
                . '<input type="date" name="deadline" />'
                . '<input type="text" name="description" placeholder="Description" />'
                . '<button class="button button-primary" type="submit">Create</button>'
                . '</form>';
        }
        echo '<table class="widefat fixed striped"><thead><tr><th>Name</th><th>Deadline</th><th>Status</th></tr></thead><tbody id="wfp-projects-body"><tr><td colspan="3">Loading...</td></tr></tbody></table>';
        echo '<h2 style="margin-top:20px">Tasks</h2>';
        if (current_user_can('wfp_manage_tasks') || current_user_can('wfp_manage_projects')) {
            echo '<form id="wfp-task-form" style="margin:12px 0;display:flex;gap:8px;flex-wrap:wrap">'
                . '<input type="number" name="project_id" placeholder="Project ID" required />'
                . '<input type="text" name="title" placeholder="Task title" required />'
                . '<input type="text" name="description" placeholder="Description" />'
                . '<input type="text" name="priority" placeholder="Priority (low/normal/high)" />'
                . '<input type="date" name="due_date" />'
                . '<input type="number" name="assignee_id" placeholder="Assignee ID (optional)" />'
                . '<button class="button" type="submit">Add Task</button>'
                . '</form>';
        }
        echo '<table class="widefat fixed striped"><thead><tr><th>ID</th><th>Project</th><th>Title</th><th>Assignee</th><th>Status</th></tr></thead><tbody id="wfp-tasks-body"><tr><td colspan="5">Loading...</td></tr></tbody></table>';
        echo '<h2 style="margin-top:20px">Members</h2>';
        echo '<form id="wfp-member-form" style="margin:12px 0;display:flex;gap:8px;flex-wrap:wrap">'
            . '<input type="number" name="project_id" placeholder="Project ID" required />'
            . '<input type="number" name="user_id" placeholder="User ID" required />'
            . '<input type="text" name="role" placeholder="Role (optional)" />'
            . '<button class="button" type="submit">Add Member</button>'
            . '</form>';
        echo '<table class="widefat fixed striped"><thead><tr><th>Project</th><th>User</th><th>Role</th><th>Action</th></tr></thead><tbody id="wfp-members-body"><tr><td colspan="4">Enter a Project ID to load</td></tr></tbody></table>';
        echo '</div>';
    }

    public static function renderReports(): void
    {
        echo '<div class="wrap"><h1>Reports</h1>';
        echo '<div id="wfp-reports-filters" style="margin:12px 0;display:flex;gap:8px;flex-wrap:wrap">'
           . '<select id="wfp-filter-user"><option value="">Select user</option></select>'
           . '<input type="date" id="wfp-filter-from" />'
           . '<input type="date" id="wfp-filter-to" />'
           . '<button class="button" id="wfp-run-report">Run</button>'
           . '</div>';
        echo '<h2>Attendance</h2>';
        echo '<table class="widefat fixed striped"><thead><tr><th>User</th><th>Clock In</th><th>Clock Out</th><th>Activity</th></tr></thead><tbody id="wfp-report-attendance"><tr><td colspan="4">Use filters and run</td></tr></tbody></table>';
        echo '<h2 style="margin-top:20px">Project Time Logs</h2>';
        echo '<table class="widefat fixed striped"><thead><tr><th>User</th><th>Project</th><th>Task</th><th>Start</th><th>End</th><th>Minutes</th></tr></thead><tbody id="wfp-report-time"><tr><td colspan="6">Use filters and run</td></tr></tbody></table>';
        echo '</div>';
    }

    public static function renderSettings(): void
    {
        echo '<div class="wrap"><h1>Settings</h1>';
        echo '<form id="wfp-settings-form" style="display:flex;gap:12px;align-items:center;margin:12px 0">'
           . '<label>Workweek: <select name="workweek_days"><option value="6">6 days</option><option value="7">7 days</option></select></label>'
           . '<label>Leave categories (comma separated): <input type="text" name="leave_categories" placeholder="Casual,Sick" /></label>'
           . '<button class="button button-primary" type="submit">Save</button>'
           . '</form>';
        echo '<div id="wfp-settings-status"></div>';
        echo '</div>';
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
        echo '<h2>My Attendance</h2>';
        echo '<table class="widefat fixed striped"><thead><tr><th>Date In</th><th>Date Out</th><th>Activity</th></tr></thead><tbody id="wfp-attendance-body"><tr><td colspan="3">Loading...</td></tr></tbody></table>';
        // Reuse frontend script for actions
        wp_enqueue_script('wfp-admin');
        echo '</div>';
    }
}

