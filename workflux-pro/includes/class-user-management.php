<?php
/**
 * User management class
 *
 * @package WorkFluxPro
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WorkFlux Pro User Management Class
 */
class WorkFluxPro_User_Management {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Initialize
     */
    public function init() {
        add_action('user_register', array($this, 'on_user_register'));
        add_action('delete_user', array($this, 'on_user_delete'));
        add_action('profile_update', array($this, 'on_profile_update'));
    }
    
    /**
     * Handle user registration
     *
     * @param int $user_id
     */
    public function on_user_register($user_id) {
        // Auto-assign employee role if no WorkFlux role assigned
        $user_role = WorkFluxPro_Roles::get_user_workflux_role($user_id);
        if (!$user_role) {
            $user = get_user_by('id', $user_id);
            $user->add_role('wfp_employee');
        }
    }
    
    /**
     * Handle user deletion
     *
     * @param int $user_id
     */
    public function on_user_delete($user_id) {
        global $wpdb;
        
        $employee = self::get_employee_by_user_id($user_id);
        if ($employee) {
            // Soft delete - mark as terminated
            $employees_table = $wpdb->prefix . 'wfp_employees';
            $wpdb->update(
                $employees_table,
                array('status' => 'terminated'),
                array('user_id' => $user_id),
                array('%s'),
                array('%d')
            );
        }
    }
    
    /**
     * Handle profile update
     *
     * @param int $user_id
     */
    public function on_profile_update($user_id) {
        // Update employee record if needed
        $this->sync_employee_data($user_id);
    }
    
    /**
     * Create employee record
     *
     * @param array $data
     * @return int|false
     */
    public static function create_employee($data) {
        global $wpdb;
        
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        // Validate required fields
        if (empty($data['user_id']) || empty($data['employee_id'])) {
            return false;
        }
        
        // Check if employee ID already exists
        $existing = $wpdb->get_var($wpdb->prepare("
            SELECT id FROM $employees_table WHERE employee_id = %s
        ", $data['employee_id']));
        
        if ($existing) {
            return false; // Employee ID already exists
        }
        
        $defaults = array(
            'department' => '',
            'designation' => '',
            'hire_date' => current_time('mysql', true),
            'manager_id' => null,
            'status' => 'active'
        );
        
        $employee_data = wp_parse_args($data, $defaults);
        
        $result = $wpdb->insert(
            $employees_table,
            $employee_data,
            array('%d', '%s', '%s', '%s', '%s', '%d', '%s')
        );
        
        if ($result) {
            $employee_id = $wpdb->insert_id;
            
            // Set user meta
            update_user_meta($data['user_id'], 'wfp_employee_id', $employee_id);
            update_user_meta($data['user_id'], 'wfp_employee_status', 'active');
            
            // Log the action
            do_action('workflux_pro_employee_created', $employee_id, $data);
            
            return $employee_id;
        }
        
        return false;
    }
    
    /**
     * Update employee record
     *
     * @param int $employee_id
     * @param array $data
     * @return bool
     */
    public static function update_employee($employee_id, $data) {
        global $wpdb;
        
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        // Remove non-updatable fields
        unset($data['id'], $data['user_id'], $data['created_at']);
        
        if (empty($data)) {
            return false;
        }
        
        $result = $wpdb->update(
            $employees_table,
            $data,
            array('id' => $employee_id),
            null,
            array('%d')
        );
        
        if ($result !== false) {
            // Update user meta if status changed
            if (isset($data['status'])) {
                $employee = self::get_employee($employee_id);
                if ($employee) {
                    update_user_meta($employee->user_id, 'wfp_employee_status', $data['status']);
                }
            }
            
            // Log the action
            do_action('workflux_pro_employee_updated', $employee_id, $data);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get employee by ID
     *
     * @param int $employee_id
     * @return object|null
     */
    public static function get_employee($employee_id) {
        global $wpdb;
        
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT e.*, u.display_name, u.user_email, u.user_login
            FROM $employees_table e
            JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE e.id = %d
        ", $employee_id));
    }
    
    /**
     * Get employee by user ID
     *
     * @param int $user_id
     * @return object|null
     */
    public static function get_employee_by_user_id($user_id) {
        global $wpdb;
        
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT e.*, u.display_name, u.user_email, u.user_login
            FROM $employees_table e
            JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE e.user_id = %d
        ", $user_id));
    }
    
    /**
     * Get all employees
     *
     * @param array $args
     * @return array
     */
    public static function get_all_employees($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => 'active',
            'limit' => 100,
            'offset' => 0,
            'orderby' => 'display_name',
            'order' => 'ASC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        $where = '';
        if ($args['status']) {
            $where = $wpdb->prepare(" WHERE e.status = %s", $args['status']);
        }
        
        $order = sprintf(" ORDER BY u.%s %s", $args['orderby'], $args['order']);
        $limit = sprintf(" LIMIT %d OFFSET %d", $args['limit'], $args['offset']);
        
        $query = "
            SELECT e.*, u.display_name, u.user_email, u.user_login
            FROM $employees_table e
            JOIN {$wpdb->users} u ON e.user_id = u.ID
            $where
            $order
            $limit
        ";
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Get total employees count
     *
     * @param string $status
     * @return int
     */
    public static function get_total_employees($status = 'active') {
        global $wpdb;
        
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        if ($status) {
            $count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM $employees_table WHERE status = %s
            ", $status));
        } else {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $employees_table");
        }
        
        return intval($count);
    }
    
    /**
     * Get new employees count for current month
     *
     * @return int
     */
    public static function get_new_employees_count() {
        global $wpdb;
        
        $employees_table = $wpdb->prefix . 'wfp_employees';
        $current_month = date('Y-m');
        
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $employees_table 
            WHERE DATE_FORMAT(hire_date, '%%Y-%%m') = %s
        ", $current_month));
        
        return intval($count);
    }
    
    /**
     * Get employees by department
     *
     * @param string $department
     * @return array
     */
    public static function get_employees_by_department($department) {
        global $wpdb;
        
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT e.*, u.display_name, u.user_email, u.user_login
            FROM $employees_table e
            JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE e.department = %s AND e.status = 'active'
            ORDER BY u.display_name
        ", $department));
    }
    
    /**
     * Get employees by manager
     *
     * @param int $manager_id
     * @return array
     */
    public static function get_employees_by_manager($manager_id) {
        global $wpdb;
        
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT e.*, u.display_name, u.user_email, u.user_login
            FROM $employees_table e
            JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE e.manager_id = %d AND e.status = 'active'
            ORDER BY u.display_name
        ", $manager_id));
    }
    
    /**
     * Search employees
     *
     * @param string $search_term
     * @param array $filters
     * @return array
     */
    public static function search_employees($search_term, $filters = array()) {
        global $wpdb;
        
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        $where_conditions = array("e.status = 'active'");
        $search_params = array();
        
        if (!empty($search_term)) {
            $where_conditions[] = "(u.display_name LIKE %s OR u.user_email LIKE %s OR e.employee_id LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search_term) . '%';
            $search_params[] = $search_term;
            $search_params[] = $search_term;
            $search_params[] = $search_term;
        }
        
        if (!empty($filters['department'])) {
            $where_conditions[] = "e.department = %s";
            $search_params[] = $filters['department'];
        }
        
        if (!empty($filters['designation'])) {
            $where_conditions[] = "e.designation = %s";
            $search_params[] = $filters['designation'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $query = "
            SELECT e.*, u.display_name, u.user_email, u.user_login
            FROM $employees_table e
            JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE $where_clause
            ORDER BY u.display_name
            LIMIT 50
        ";
        
        if (!empty($search_params)) {
            return $wpdb->get_results($wpdb->prepare($query, $search_params));
        } else {
            return $wpdb->get_results($query);
        }
    }
    
    /**
     * Get upcoming birthdays
     *
     * @param int $days_ahead
     * @return array
     */
    public static function get_upcoming_birthdays($days_ahead = 30) {
        global $wpdb;
        
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        // This requires birthday field in user meta or employee table
        // For now, return empty array - can be implemented based on requirements
        return array();
    }
    
    /**
     * Get upcoming work anniversaries
     *
     * @param int $days_ahead
     * @return array
     */
    public static function get_upcoming_anniversaries($days_ahead = 30) {
        global $wpdb;
        
        $employees_table = $wpdb->prefix . 'wfp_employees';
        $today = date('Y-m-d');
        $future_date = date('Y-m-d', strtotime("+$days_ahead days"));
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT e.*, u.display_name, u.user_email,
                   YEAR(CURDATE()) - YEAR(e.hire_date) as years_of_service
            FROM $employees_table e
            JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE e.status = 'active'
            AND DATE_FORMAT(e.hire_date, '%%m-%%d') BETWEEN DATE_FORMAT(%s, '%%m-%%d') AND DATE_FORMAT(%s, '%%m-%%d')
            ORDER BY DATE_FORMAT(e.hire_date, '%%m-%%d')
        ", $today, $future_date));
    }
    
    /**
     * Get departments list
     *
     * @return array
     */
    public static function get_departments() {
        global $wpdb;
        
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        return $wpdb->get_col("
            SELECT DISTINCT department 
            FROM $employees_table 
            WHERE department IS NOT NULL AND department != ''
            ORDER BY department
        ");
    }
    
    /**
     * Get designations list
     *
     * @return array
     */
    public static function get_designations() {
        global $wpdb;
        
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        return $wpdb->get_col("
            SELECT DISTINCT designation 
            FROM $employees_table 
            WHERE designation IS NOT NULL AND designation != ''
            ORDER BY designation
        ");
    }
    
    /**
     * Sync employee data with WordPress user
     *
     * @param int $user_id
     */
    private function sync_employee_data($user_id) {
        $employee = self::get_employee_by_user_id($user_id);
        if (!$employee) {
            return;
        }
        
        // Sync any necessary data between WordPress user and employee record
        // This can be customized based on requirements
    }
    
    /**
     * Generate employee ID
     *
     * @param array $args
     * @return string
     */
    public static function generate_employee_id($args = array()) {
        $defaults = array(
            'prefix' => 'EMP',
            'year' => date('Y'),
            'padding' => 4
        );
        
        $args = wp_parse_args($args, $defaults);
        
        global $wpdb;
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        // Get next sequence number
        $pattern = $args['prefix'] . $args['year'] . '%';
        $last_id = $wpdb->get_var($wpdb->prepare("
            SELECT employee_id FROM $employees_table 
            WHERE employee_id LIKE %s 
            ORDER BY employee_id DESC 
            LIMIT 1
        ", $pattern));
        
        if ($last_id) {
            $number = intval(substr($last_id, strlen($args['prefix'] . $args['year']))) + 1;
        } else {
            $number = 1;
        }
        
        return $args['prefix'] . $args['year'] . str_pad($number, $args['padding'], '0', STR_PAD_LEFT);
    }
    
    /**
     * Onboard new employee
     *
     * @param array $user_data
     * @param array $employee_data
     * @return int|false
     */
    public static function onboard_employee($user_data, $employee_data) {
        // Create WordPress user
        $user_id = wp_insert_user($user_data);
        
        if (is_wp_error($user_id)) {
            return false;
        }
        
        // Generate employee ID if not provided
        if (empty($employee_data['employee_id'])) {
            $employee_data['employee_id'] = self::generate_employee_id();
        }
        
        $employee_data['user_id'] = $user_id;
        
        // Create employee record
        $employee_id = self::create_employee($employee_data);
        
        if (!$employee_id) {
            // Clean up user if employee creation failed
            wp_delete_user($user_id);
            return false;
        }
        
        // Assign role
        $user = get_user_by('id', $user_id);
        $user->set_role('wfp_employee');
        
        // Send welcome email
        self::send_welcome_email($user_id, $employee_data);
        
        // Log the action
        do_action('workflux_pro_employee_onboarded', $user_id, $employee_id);
        
        return $employee_id;
    }
    
    /**
     * Send welcome email to new employee
     *
     * @param int $user_id
     * @param array $employee_data
     */
    private static function send_welcome_email($user_id, $employee_data) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }
        
        $subject = __('Welcome to WorkFlux Pro', 'workflux-pro');
        $message = sprintf(
            __('Welcome %s! Your employee ID is: %s', 'workflux-pro'),
            $user->display_name,
            $employee_data['employee_id']
        );
        
        wp_mail($user->user_email, $subject, $message);
    }
}