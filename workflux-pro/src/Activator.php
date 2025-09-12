<?php

namespace WFP;

if (!defined('ABSPATH')) {
    exit;
}

class Activator
{
    public static function activate(): void
    {
        self::createRolesAndCapabilities();
        self::createTables();
        $prev = get_option('wfp_version');
        $code = (int) get_option('wfp_version_code', 0);
        if ($prev !== WFP_VERSION) {
            $code = $code + 1;
            update_option('wfp_version_code', $code);
            update_option('wfp_version', WFP_VERSION);
        }
    }

    private static function createRolesAndCapabilities(): void
    {
        $capabilities = [
            'wfp_manage_employees',
            'wfp_approve_leave',
            'wfp_manage_projects',
            'wfp_manage_tasks',
            'wfp_view_reports',
            'wfp_clock_attendance',
            'wfp_submit_leave',
            'wfp_manage_settings',
        ];

        // Roles
        $roles = [
            'wfp_owner' => [
                'name' => __('WorkFlux Owner', 'workflux-pro'),
                'caps' => $capabilities,
            ],
            'wfp_managing_head' => [
                'name' => __('Managing Head', 'workflux-pro'),
                'caps' => [
                    'wfp_manage_employees', 'wfp_approve_leave', 'wfp_manage_projects', 'wfp_manage_tasks', 'wfp_view_reports'
                ],
            ],
            'wfp_manager' => [
                'name' => __('HR / Manager', 'workflux-pro'),
                'caps' => ['wfp_manage_employees', 'wfp_approve_leave', 'wfp_manage_projects', 'wfp_manage_tasks', 'wfp_view_reports'],
            ],
            'wfp_project_admin' => [
                'name' => __('Project Admin', 'workflux-pro'),
                'caps' => ['wfp_manage_projects', 'wfp_manage_tasks', 'wfp_view_reports'],
            ],
            'wfp_employee' => [
                'name' => __('Employee', 'workflux-pro'),
                'caps' => ['wfp_clock_attendance', 'wfp_submit_leave'],
            ],
        ];

        foreach ($roles as $key => $role) {
            if (!get_role($key)) {
                add_role($key, $role['name'], array_fill_keys($role['caps'], true));
            } else {
                $wp_role = get_role($key);
                foreach ($role['caps'] as $cap) {
                    if ($wp_role && !$wp_role->has_cap($cap)) {
                        $wp_role->add_cap($cap);
                    }
                }
            }
        }

        // Ensure administrators also get full capabilities
        $admin = get_role('administrator');
        if ($admin) {
            foreach ($capabilities as $cap) {
                if (!$admin->has_cap($cap)) {
                    $admin->add_cap($cap);
                }
            }
        }
    }

    public static function createTables(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        $attendance = $wpdb->prefix . 'wfp_attendance';
        $leaves = $wpdb->prefix . 'wfp_leaves';
        $projects = $wpdb->prefix . 'wfp_projects';
        $tasks = $wpdb->prefix . 'wfp_tasks';
        $time_logs = $wpdb->prefix . 'wfp_time_logs';
        $project_members = $wpdb->prefix . 'wfp_project_members';

        $sql = [];

        $sql[] = "CREATE TABLE $attendance (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            clock_in DATETIME NULL,
            clock_out DATETIME NULL,
            activity TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE $leaves (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            type VARCHAR(50) NOT NULL,
            reason TEXT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            approver_id BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY approver_id (approver_id)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE $projects (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL,
            description TEXT NULL,
            deadline DATE NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_by BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE $tasks (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            project_id BIGINT UNSIGNED NOT NULL,
            assignee_id BIGINT UNSIGNED NULL,
            title VARCHAR(191) NOT NULL,
            description TEXT NULL,
            priority VARCHAR(20) NOT NULL DEFAULT 'normal',
            due_date DATE NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'todo',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY project_id (project_id),
            KEY assignee_id (assignee_id)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE $time_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            task_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            started_at DATETIME NOT NULL,
            ended_at DATETIME NULL,
            duration_minutes INT UNSIGNED NULL,
            note TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY task_id (task_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE $project_members (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            project_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            role VARCHAR(50) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY project_user_unique (project_id, user_id),
            KEY project_id (project_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        foreach ($sql as $statement) {
            dbDelta($statement);
        }
    }
}

