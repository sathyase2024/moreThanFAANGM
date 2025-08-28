<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WFP_Migrations {
    const OPTION_KEY = 'wfp_db_version';
    const DB_VERSION = '0.1.0';

    public function install() {
        $this->create_tables();
        update_option( self::OPTION_KEY, self::DB_VERSION );
    }

    public function maybe_run() {
        $installed = get_option( self::OPTION_KEY );
        if ( version_compare( (string) $installed, self::DB_VERSION, '>=' ) ) {
            return;
        }
        $this->create_tables();
        update_option( self::OPTION_KEY, self::DB_VERSION );
    }

    private function create_tables() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        $tables_sql = [];

        $tables_sql[] = "CREATE TABLE {$wpdb->prefix}wfp_employees (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            emp_code VARCHAR(64) NULL,
            name VARCHAR(191) NOT NULL,
            department_id BIGINT UNSIGNED NULL,
            designation VARCHAR(191) NULL,
            manager_id BIGINT UNSIGNED NULL,
            status VARCHAR(32) NOT NULL DEFAULT 'active',
            join_date DATE NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY manager_id (manager_id)
        ) $charset_collate;";

        $tables_sql[] = "CREATE TABLE {$wpdb->prefix}wfp_projects (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL,
            code VARCHAR(64) NULL,
            client VARCHAR(191) NULL,
            status VARCHAR(32) NOT NULL DEFAULT 'active',
            priority TINYINT NOT NULL DEFAULT 0,
            start_date DATE NULL,
            end_date DATE NULL,
            owner_id BIGINT UNSIGNED NULL,
            billable TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            PRIMARY KEY  (id),
            KEY owner_id (owner_id)
        ) $charset_collate;";

        $tables_sql[] = "CREATE TABLE {$wpdb->prefix}wfp_project_members (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            project_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            role VARCHAR(64) NULL,
            hourly_rate DECIMAL(10,2) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY project_id (project_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        $tables_sql[] = "CREATE TABLE {$wpdb->prefix}wfp_tasks (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            project_id BIGINT UNSIGNED NOT NULL,
            parent_task_id BIGINT UNSIGNED NULL,
            title VARCHAR(191) NOT NULL,
            status VARCHAR(32) NOT NULL DEFAULT 'todo',
            priority VARCHAR(16) NULL,
            estimate_minutes INT NULL,
            assignee_id BIGINT UNSIGNED NULL,
            due_date DATE NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY project_id (project_id),
            KEY assignee_id (assignee_id)
        ) $charset_collate;";

        $tables_sql[] = "CREATE TABLE {$wpdb->prefix}wfp_timesheets (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            project_id BIGINT UNSIGNED NULL,
            task_id BIGINT UNSIGNED NULL,
            date DATE NOT NULL,
            start_time DATETIME NULL,
            end_time DATETIME NULL,
            duration_minutes INT NOT NULL DEFAULT 0,
            notes TEXT NULL,
            billable TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY project_id (project_id),
            KEY task_id (task_id)
        ) $charset_collate;";

        $tables_sql[] = "CREATE TABLE {$wpdb->prefix}wfp_attendance (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            date DATE NOT NULL,
            clock_in DATETIME NULL,
            clock_out DATETIME NULL,
            breaks_minutes INT NOT NULL DEFAULT 0,
            late_flag TINYINT(1) NOT NULL DEFAULT 0,
            geo_json TEXT NULL,
            ip VARCHAR(64) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY date (date)
        ) $charset_collate;";

        $tables_sql[] = "CREATE TABLE {$wpdb->prefix}wfp_leave_types (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(64) NOT NULL,
            accrual_rule VARCHAR(191) NULL,
            max_carry_forward INT NULL,
            requires_attachment TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        $tables_sql[] = "CREATE TABLE {$wpdb->prefix}wfp_leaves (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            type_id BIGINT UNSIGNED NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            days DECIMAL(5,2) NOT NULL DEFAULT 1.00,
            status VARCHAR(32) NOT NULL DEFAULT 'pending',
            approver_id BIGINT UNSIGNED NULL,
            notes TEXT NULL,
            attachment_id BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY approver_id (approver_id)
        ) $charset_collate;";

        $tables_sql[] = "CREATE TABLE {$wpdb->prefix}wfp_external_duty (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            date DATE NOT NULL,
            start_time DATETIME NULL,
            end_time DATETIME NULL,
            purpose VARCHAR(191) NULL,
            location VARCHAR(191) NULL,
            status VARCHAR(32) NOT NULL DEFAULT 'pending',
            approver_id BIGINT UNSIGNED NULL,
            geo_json TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY approver_id (approver_id)
        ) $charset_collate;";

        $tables_sql[] = "CREATE TABLE {$wpdb->prefix}wfp_approvals (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            entity_type VARCHAR(64) NOT NULL,
            entity_id BIGINT UNSIGNED NOT NULL,
            approver_id BIGINT UNSIGNED NOT NULL,
            level INT NOT NULL DEFAULT 1,
            status VARCHAR(32) NOT NULL DEFAULT 'pending',
            acted_at DATETIME NULL,
            comment TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY entity (entity_type, entity_id),
            KEY approver_id (approver_id)
        ) $charset_collate;";

        $tables_sql[] = "CREATE TABLE {$wpdb->prefix}wfp_notifications (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            channel VARCHAR(32) NOT NULL,
            to_user_id BIGINT UNSIGNED NOT NULL,
            template_key VARCHAR(64) NOT NULL,
            payload_json LONGTEXT NULL,
            status VARCHAR(32) NOT NULL DEFAULT 'queued',
            sent_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY to_user_id (to_user_id)
        ) $charset_collate;";

        $tables_sql[] = "CREATE TABLE {$wpdb->prefix}wfp_audit_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            actor_id BIGINT UNSIGNED NULL,
            action VARCHAR(64) NOT NULL,
            entity_type VARCHAR(64) NULL,
            entity_id BIGINT UNSIGNED NULL,
            meta_json LONGTEXT NULL,
            ip VARCHAR(64) NULL,
            user_agent VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY actor_id (actor_id)
        ) $charset_collate;";

        $tables_sql[] = "CREATE TABLE {$wpdb->prefix}wfp_documents (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            owner_user_id BIGINT UNSIGNED NULL,
            title VARCHAR(191) NOT NULL,
            file_id BIGINT UNSIGNED NULL,
            visibility VARCHAR(32) NOT NULL DEFAULT 'private',
            tags_json TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY owner_user_id (owner_user_id)
        ) $charset_collate;";

        foreach ( $tables_sql as $sql ) {
            dbDelta( $sql );
        }
    }
}

