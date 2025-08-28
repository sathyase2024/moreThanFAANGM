<?php
/**
 * Database management class
 *
 * @package WorkFluxPro
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WorkFlux Pro Database Class
 */
class WorkFluxPro_Database {
    
    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Employees table
        $employees_table = $wpdb->prefix . 'wfp_employees';
        $employees_sql = "CREATE TABLE $employees_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            employee_id varchar(50) NOT NULL,
            department varchar(100),
            designation varchar(100),
            hire_date date,
            manager_id int(11),
            status enum('active', 'inactive', 'terminated') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY employee_id (employee_id),
            KEY user_id (user_id),
            KEY manager_id (manager_id)
        ) $charset_collate;";
        
        // Projects table
        $projects_table = $wpdb->prefix . 'wfp_projects';
        $projects_sql = "CREATE TABLE $projects_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            project_code varchar(50) NOT NULL,
            client varchar(255),
            start_date date,
            end_date date,
            estimated_hours decimal(10,2),
            actual_hours decimal(10,2) DEFAULT 0,
            status enum('planning', 'active', 'on_hold', 'completed', 'cancelled') DEFAULT 'planning',
            priority enum('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
            created_by int(11),
            assigned_to int(11),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY project_code (project_code),
            KEY created_by (created_by),
            KEY assigned_to (assigned_to)
        ) $charset_collate;";
        
        // Tasks table
        $tasks_table = $wpdb->prefix . 'wfp_tasks';
        $tasks_sql = "CREATE TABLE $tasks_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            project_id int(11) NOT NULL,
            title varchar(255) NOT NULL,
            description text,
            assigned_to int(11),
            estimated_hours decimal(8,2),
            actual_hours decimal(8,2) DEFAULT 0,
            status enum('todo', 'in_progress', 'review', 'completed', 'cancelled') DEFAULT 'todo',
            priority enum('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
            due_date date,
            created_by int(11),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY project_id (project_id),
            KEY assigned_to (assigned_to),
            KEY created_by (created_by)
        ) $charset_collate;";
        
        // Time tracking table
        $time_tracking_table = $wpdb->prefix . 'wfp_time_tracking';
        $time_tracking_sql = "CREATE TABLE $time_tracking_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            employee_id int(11) NOT NULL,
            project_id int(11),
            task_id int(11),
            clock_in datetime NOT NULL,
            clock_out datetime,
            break_time int(11) DEFAULT 0,
            total_hours decimal(8,2),
            description text,
            location varchar(255),
            ip_address varchar(45),
            status enum('active', 'completed', 'cancelled') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY employee_id (employee_id),
            KEY project_id (project_id),
            KEY task_id (task_id),
            KEY clock_in (clock_in)
        ) $charset_collate;";
        
        // Leave requests table
        $leave_requests_table = $wpdb->prefix . 'wfp_leave_requests';
        $leave_requests_sql = "CREATE TABLE $leave_requests_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            employee_id int(11) NOT NULL,
            leave_type enum('annual', 'sick', 'personal', 'maternity', 'paternity', 'emergency') NOT NULL,
            start_date date NOT NULL,
            end_date date NOT NULL,
            days_requested decimal(4,1) NOT NULL,
            reason text,
            status enum('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
            approved_by int(11),
            approved_at datetime,
            comments text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY employee_id (employee_id),
            KEY approved_by (approved_by),
            KEY start_date (start_date)
        ) $charset_collate;";
        
        // External duty requests table
        $external_duty_table = $wpdb->prefix . 'wfp_external_duty';
        $external_duty_sql = "CREATE TABLE $external_duty_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            employee_id int(11) NOT NULL,
            purpose varchar(255) NOT NULL,
            location varchar(255) NOT NULL,
            start_date date NOT NULL,
            end_date date NOT NULL,
            start_time time,
            end_time time,
            description text,
            status enum('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
            approved_by int(11),
            approved_at datetime,
            comments text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY employee_id (employee_id),
            KEY approved_by (approved_by),
            KEY start_date (start_date)
        ) $charset_collate;";
        
        // Project assignments table
        $project_assignments_table = $wpdb->prefix . 'wfp_project_assignments';
        $project_assignments_sql = "CREATE TABLE $project_assignments_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            project_id int(11) NOT NULL,
            employee_id int(11) NOT NULL,
            role varchar(100),
            assigned_by int(11),
            assigned_at datetime DEFAULT CURRENT_TIMESTAMP,
            status enum('active', 'inactive', 'completed') DEFAULT 'active',
            PRIMARY KEY (id),
            UNIQUE KEY project_employee (project_id, employee_id),
            KEY project_id (project_id),
            KEY employee_id (employee_id),
            KEY assigned_by (assigned_by)
        ) $charset_collate;";
        
        // Notifications table
        $notifications_table = $wpdb->prefix . 'wfp_notifications';
        $notifications_sql = "CREATE TABLE $notifications_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            title varchar(255) NOT NULL,
            message text NOT NULL,
            type enum('info', 'success', 'warning', 'error') DEFAULT 'info',
            action_url varchar(500),
            is_read tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY is_read (is_read),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Employee settings table
        $employee_settings_table = $wpdb->prefix . 'wfp_employee_settings';
        $employee_settings_sql = "CREATE TABLE $employee_settings_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            employee_id int(11) NOT NULL,
            setting_key varchar(100) NOT NULL,
            setting_value longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY employee_setting (employee_id, setting_key),
            KEY employee_id (employee_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Execute table creation
        dbDelta($employees_sql);
        dbDelta($projects_sql);
        dbDelta($tasks_sql);
        dbDelta($time_tracking_sql);
        dbDelta($leave_requests_sql);
        dbDelta($external_duty_sql);
        dbDelta($project_assignments_sql);
        dbDelta($notifications_sql);
        dbDelta($employee_settings_sql);
        
        // Add foreign key constraints if needed
        self::add_foreign_keys();
    }
    
    /**
     * Add foreign key constraints
     */
    private static function add_foreign_keys() {
        global $wpdb;
        
        // Note: WordPress doesn't typically use foreign key constraints
        // This is here for reference if needed in the future
        
        /*
        $wpdb->query("
            ALTER TABLE {$wpdb->prefix}wfp_employees 
            ADD CONSTRAINT fk_employee_user 
            FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
        ");
        */
    }
    
    /**
     * Drop tables (for uninstall)
     */
    public static function drop_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'wfp_employee_settings',
            $wpdb->prefix . 'wfp_notifications',
            $wpdb->prefix . 'wfp_project_assignments',
            $wpdb->prefix . 'wfp_external_duty',
            $wpdb->prefix . 'wfp_leave_requests',
            $wpdb->prefix . 'wfp_time_tracking',
            $wpdb->prefix . 'wfp_tasks',
            $wpdb->prefix . 'wfp_projects',
            $wpdb->prefix . 'wfp_employees'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }
    
    /**
     * Get table name with prefix
     *
     * @param string $table_name
     * @return string
     */
    public static function get_table_name($table_name) {
        global $wpdb;
        return $wpdb->prefix . 'wfp_' . $table_name;
    }
    
    /**
     * Check if tables exist
     *
     * @return bool
     */
    public static function tables_exist() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wfp_employees';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        
        return $table_exists === $table_name;
    }
    
    /**
     * Get database version
     *
     * @return string
     */
    public static function get_db_version() {
        return get_option('workflux_pro_db_version', '0.0.0');
    }
    
    /**
     * Update database version
     *
     * @param string $version
     */
    public static function update_db_version($version) {
        update_option('workflux_pro_db_version', $version);
    }
}