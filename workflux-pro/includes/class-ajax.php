<?php
/**
 * AJAX handler class
 *
 * @package WorkFluxPro
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WorkFlux Pro AJAX Class
 */
class WorkFluxPro_Ajax {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Time tracking actions
        add_action('wp_ajax_wfp_clock_in', array($this, 'clock_in'));
        add_action('wp_ajax_wfp_clock_out', array($this, 'clock_out'));
        add_action('wp_ajax_wfp_start_project', array($this, 'start_project_timer'));
        add_action('wp_ajax_wfp_stop_project', array($this, 'stop_project_timer'));
        
        // Leave management actions
        add_action('wp_ajax_wfp_submit_leave_request', array($this, 'submit_leave_request'));
        add_action('wp_ajax_wfp_approve_leave', array($this, 'approve_leave'));
        
        // External duty actions
        add_action('wp_ajax_wfp_submit_external_duty', array($this, 'submit_external_duty'));
        add_action('wp_ajax_wfp_approve_external_duty', array($this, 'approve_external_duty'));
        
        // Project management actions
        add_action('wp_ajax_wfp_create_project', array($this, 'create_project'));
        add_action('wp_ajax_wfp_assign_project', array($this, 'assign_project'));
        
        // Employee management actions
        add_action('wp_ajax_wfp_create_employee', array($this, 'create_employee'));
        add_action('wp_ajax_wfp_update_employee', array($this, 'update_employee'));
        
        // Dashboard actions
        add_action('wp_ajax_wfp_get_dashboard_data', array($this, 'get_dashboard_data'));
        
        // Role management actions
        add_action('wp_ajax_wfp_update_user_role', array($this, 'update_user_role'));
        
        // Reports actions
        add_action('wp_ajax_wfp_get_reports', array($this, 'get_reports'));
        add_action('wp_ajax_wfp_export_report', array($this, 'export_report'));
    }
    
    /**
     * Verify nonce for security
     */
    public static function verify_nonce($action = 'workflux_pro_nonce') {
        // Check if nonce is provided
        $nonce = $_POST['nonce'] ?? $_POST['_wpnonce'] ?? $_REQUEST['_wpnonce'] ?? '';
        
        if (empty($nonce)) {
            self::send_response(false, null, __('Security token missing', 'workflux-pro'));
            wp_die();
        }
        
        // Verify nonce
        if (!wp_verify_nonce($nonce, $action)) {
            self::send_response(false, null, __('Security check failed', 'workflux-pro'));
            wp_die();
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            self::send_response(false, null, __('Please log in to continue', 'workflux-pro'));
            wp_die();
        }
    }
    
    /**
     * Send JSON response
     *
     * @param bool $success
     * @param mixed $data
     * @param string $message
     */
    private static function send_response($success, $data = null, $message = '') {
        wp_send_json(array(
            'success' => $success,
            'data' => $data,
            'message' => $message
        ));
    }
    
    /**
     * Clock in
     */
    public static function clock_in() {
        self::verify_nonce();
        
        if (!WorkFluxPro_Roles::user_can('wfp_clock_in_out')) {
            self::send_response(false, null, __('Permission denied', 'workflux-pro'));
            return;
        }
        
        $user_id = get_current_user_id();
        $location = sanitize_text_field($_POST['location'] ?? '');
        $ip_address = $_SERVER['REMOTE_ADDR'];
        
        $result = WorkFluxPro_Time_Tracking::clock_in($user_id, $location, $ip_address);
        
        if ($result) {
            self::send_response(true, $result, __('Clocked in successfully', 'workflux-pro'));
        } else {
            self::send_response(false, null, __('Failed to clock in', 'workflux-pro'));
        }
    }
    
    /**
     * Clock out
     */
    public static function clock_out() {
        self::verify_nonce();
        
        if (!WorkFluxPro_Roles::user_can('wfp_clock_in_out')) {
            self::send_response(false, null, __('Permission denied', 'workflux-pro'));
            return;
        }
        
        $user_id = get_current_user_id();
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        
        $result = WorkFluxPro_Time_Tracking::clock_out($user_id, $description);
        
        if ($result) {
            self::send_response(true, $result, __('Clocked out successfully', 'workflux-pro'));
        } else {
            self::send_response(false, null, __('Failed to clock out', 'workflux-pro'));
        }
    }
    
    /**
     * Start project timer
     */
    public static function start_project_timer() {
        self::verify_nonce();
        
        if (!WorkFluxPro_Roles::user_can('wfp_track_time')) {
            self::send_response(false, null, __('Permission denied', 'workflux-pro'));
            return;
        }
        
        $user_id = get_current_user_id();
        $project_id = intval($_POST['project_id'] ?? 0);
        $task_id = intval($_POST['task_id'] ?? 0);
        
        $result = WorkFluxPro_Time_Tracking::start_project_timer($user_id, $project_id, $task_id);
        
        if ($result) {
            self::send_response(true, $result, __('Project timer started', 'workflux-pro'));
        } else {
            self::send_response(false, null, __('Failed to start project timer', 'workflux-pro'));
        }
    }
    
    /**
     * Stop project timer
     */
    public static function stop_project_timer() {
        self::verify_nonce();
        
        if (!WorkFluxPro_Roles::user_can('wfp_track_time')) {
            self::send_response(false, null, __('Permission denied', 'workflux-pro'));
            return;
        }
        
        $user_id = get_current_user_id();
        $tracking_id = intval($_POST['tracking_id'] ?? 0);
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        
        $result = WorkFluxPro_Time_Tracking::stop_project_timer($user_id, $tracking_id, $description);
        
        if ($result) {
            self::send_response(true, $result, __('Project timer stopped', 'workflux-pro'));
        } else {
            self::send_response(false, null, __('Failed to stop project timer', 'workflux-pro'));
        }
    }
    
    /**
     * Start project tracking
     */
    public static function start_project() {
        self::verify_nonce();
        
        if (!WorkFluxPro_Roles::user_can('wfp_start_stop_projects')) {
            self::send_response(false, null, __('Permission denied', 'workflux-pro'));
            return;
        }
        
        $user_id = get_current_user_id();
        $project_id = intval($_POST['project_id'] ?? 0);
        $task_id = intval($_POST['task_id'] ?? 0);
        
        if (!$project_id) {
            self::send_response(false, null, __('Project ID is required', 'workflux-pro'));
            return;
        }
        
        $result = WorkFluxPro_Time_Tracking::start_project($user_id, $project_id, $task_id);
        
        if ($result) {
            self::send_response(true, $result, __('Project tracking started', 'workflux-pro'));
        } else {
            self::send_response(false, null, __('Failed to start project tracking', 'workflux-pro'));
        }
    }
    
    /**
     * Stop project tracking
     */
    public static function stop_project() {
        self::verify_nonce();
        
        if (!WorkFluxPro_Roles::user_can('wfp_start_stop_projects')) {
            self::send_response(false, null, __('Permission denied', 'workflux-pro'));
            return;
        }
        
        $user_id = get_current_user_id();
        $tracking_id = intval($_POST['tracking_id'] ?? 0);
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        
        $result = WorkFluxPro_Time_Tracking::stop_project($user_id, $tracking_id, $description);
        
        if ($result) {
            self::send_response(true, $result, __('Project tracking stopped', 'workflux-pro'));
        } else {
            self::send_response(false, null, __('Failed to stop project tracking', 'workflux-pro'));
        }
    }
    
    /**
     * Submit leave request
     */
    public static function submit_leave_request() {
        self::verify_nonce();
        
        if (!WorkFluxPro_Roles::user_can('wfp_submit_leave_requests')) {
            self::send_response(false, null, __('Permission denied', 'workflux-pro'));
            return;
        }
        
        $user_id = get_current_user_id();
        $data = array(
            'employee_id' => $user_id,
            'leave_type' => sanitize_text_field($_POST['leave_type'] ?? ''),
            'start_date' => sanitize_text_field($_POST['start_date'] ?? ''),
            'end_date' => sanitize_text_field($_POST['end_date'] ?? ''),
            'reason' => sanitize_textarea_field($_POST['reason'] ?? '')
        );
        
        $result = WorkFluxPro_Leave_Management::submit_request($data);
        
        if ($result) {
            self::send_response(true, $result, __('Leave request submitted successfully', 'workflux-pro'));
        } else {
            self::send_response(false, null, __('Failed to submit leave request', 'workflux-pro'));
        }
    }
    
    /**
     * Submit external duty request
     */
    public static function submit_external_duty() {
        self::verify_nonce();
        
        if (!WorkFluxPro_Roles::user_can('wfp_submit_external_duty')) {
            self::send_response(false, null, __('Permission denied', 'workflux-pro'));
            return;
        }
        
        $user_id = get_current_user_id();
        $data = array(
            'employee_id' => $user_id,
            'purpose' => sanitize_text_field($_POST['purpose'] ?? ''),
            'location' => sanitize_text_field($_POST['location'] ?? ''),
            'start_date' => sanitize_text_field($_POST['start_date'] ?? ''),
            'end_date' => sanitize_text_field($_POST['end_date'] ?? ''),
            'start_time' => sanitize_text_field($_POST['start_time'] ?? ''),
            'end_time' => sanitize_text_field($_POST['end_time'] ?? ''),
            'description' => sanitize_textarea_field($_POST['description'] ?? '')
        );
        
        $result = WorkFluxPro_External_Duty::submit_request($data);
        
        if ($result) {
            self::send_response(true, $result, __('External duty request submitted successfully', 'workflux-pro'));
        } else {
            self::send_response(false, null, __('Failed to submit external duty request', 'workflux-pro'));
        }
    }
    
    /**
     * Approve leave request
     */
    public static function approve_leave() {
        self::verify_nonce();
        
        if (!WorkFluxPro_Roles::user_can('wfp_approve_leaves')) {
            self::send_response(false, null, __('Permission denied', 'workflux-pro'));
            return;
        }
        
        $request_id = intval($_POST['request_id'] ?? 0);
        $action = sanitize_text_field($_POST['action'] ?? '');
        $comments = sanitize_textarea_field($_POST['comments'] ?? '');
        $approver_id = get_current_user_id();
        
        if (!in_array($action, array('approved', 'rejected'))) {
            self::send_response(false, null, __('Invalid action', 'workflux-pro'));
            return;
        }
        
        $result = WorkFluxPro_Leave_Management::approve_request($request_id, $action, $approver_id, $comments);
        
        if ($result) {
            $message = $action === 'approved' ? __('Leave request approved', 'workflux-pro') : __('Leave request rejected', 'workflux-pro');
            self::send_response(true, $result, $message);
        } else {
            self::send_response(false, null, __('Failed to process leave request', 'workflux-pro'));
        }
    }
    
    /**
     * Approve external duty request
     */
    public static function approve_external_duty() {
        self::verify_nonce();
        
        if (!WorkFluxPro_Roles::user_can('wfp_approve_external_duty')) {
            self::send_response(false, null, __('Permission denied', 'workflux-pro'));
            return;
        }
        
        $request_id = intval($_POST['request_id'] ?? 0);
        $action = sanitize_text_field($_POST['action'] ?? '');
        $comments = sanitize_textarea_field($_POST['comments'] ?? '');
        $approver_id = get_current_user_id();
        
        if (!in_array($action, array('approved', 'rejected'))) {
            self::send_response(false, null, __('Invalid action', 'workflux-pro'));
            return;
        }
        
        $result = WorkFluxPro_External_Duty::approve_request($request_id, $action, $approver_id, $comments);
        
        if ($result) {
            $message = $action === 'approved' ? __('External duty request approved', 'workflux-pro') : __('External duty request rejected', 'workflux-pro');
            self::send_response(true, $result, $message);
        } else {
            self::send_response(false, null, __('Failed to process external duty request', 'workflux-pro'));
        }
    }
    
    /**
     * Create project
     */
    public static function create_project() {
        self::verify_nonce();
        
        if (!WorkFluxPro_Roles::user_can('wfp_manage_projects')) {
            self::send_response(false, null, __('Permission denied', 'workflux-pro'));
            return;
        }
        
        $data = array(
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'description' => sanitize_textarea_field($_POST['description'] ?? ''),
            'project_code' => sanitize_text_field($_POST['project_code'] ?? ''),
            'client' => sanitize_text_field($_POST['client'] ?? ''),
            'start_date' => sanitize_text_field($_POST['start_date'] ?? ''),
            'end_date' => sanitize_text_field($_POST['end_date'] ?? ''),
            'estimated_hours' => floatval($_POST['estimated_hours'] ?? 0),
            'priority' => sanitize_text_field($_POST['priority'] ?? 'medium'),
            'created_by' => get_current_user_id()
        );
        
        $result = WorkFluxPro_Project_Management::create_project($data);
        
        if ($result) {
            self::send_response(true, $result, __('Project created successfully', 'workflux-pro'));
        } else {
            self::send_response(false, null, __('Failed to create project', 'workflux-pro'));
        }
    }
    
    /**
     * Assign project
     */
    public static function assign_project() {
        self::verify_nonce();
        
        if (!WorkFluxPro_Roles::user_can('wfp_assign_projects')) {
            self::send_response(false, null, __('Permission denied', 'workflux-pro'));
            return;
        }
        
        $project_id = intval($_POST['project_id'] ?? 0);
        $employee_id = intval($_POST['employee_id'] ?? 0);
        $role = sanitize_text_field($_POST['role'] ?? '');
        $assigned_by = get_current_user_id();
        
        $result = WorkFluxPro_Project_Management::assign_project($project_id, $employee_id, $role, $assigned_by);
        
        if ($result) {
            self::send_response(true, $result, __('Project assigned successfully', 'workflux-pro'));
        } else {
            self::send_response(false, null, __('Failed to assign project', 'workflux-pro'));
        }
    }
    
    /**
     * Get dashboard data
     */
    public static function get_dashboard_data() {
        self::verify_nonce();
        
        $user_id = get_current_user_id();
        $user_role = WorkFluxPro_Roles::get_user_workflux_role($user_id);
        
        $data = array();
        
        // Get role-specific dashboard data
        switch ($user_role) {
            case 'wfp_super_admin':
            case 'wfp_managing_head':
                $data = self::get_manager_dashboard_data($user_id);
                break;
            case 'wfp_hr_manager':
                $data = self::get_hr_dashboard_data($user_id);
                break;
            case 'wfp_project_admin':
                $data = self::get_project_admin_dashboard_data($user_id);
                break;
            case 'wfp_employee':
                $data = self::get_employee_dashboard_data($user_id);
                break;
        }
        
        self::send_response(true, $data);
    }
    
    /**
     * Get manager dashboard data
     */
    private static function get_manager_dashboard_data($user_id) {
        return array(
            'total_employees' => WorkFluxPro_User_Management::get_total_employees(),
            'active_projects' => WorkFluxPro_Project_Management::get_active_projects_count(),
            'pending_leaves' => WorkFluxPro_Leave_Management::get_pending_requests_count(),
            'pending_external_duties' => WorkFluxPro_External_Duty::get_pending_requests_count(),
            'today_attendance' => WorkFluxPro_Time_Tracking::get_today_attendance(),
            'recent_activities' => WorkFluxPro_Reports::get_recent_activities($user_id, 10)
        );
    }
    
    /**
     * Get HR dashboard data
     */
    private static function get_hr_dashboard_data($user_id) {
        return array(
            'new_employees_this_month' => WorkFluxPro_User_Management::get_new_employees_count(),
            'pending_leaves' => WorkFluxPro_Leave_Management::get_pending_requests_count(),
            'approved_leaves_this_month' => WorkFluxPro_Leave_Management::get_approved_requests_count(),
            'employee_birthdays' => WorkFluxPro_User_Management::get_upcoming_birthdays(),
            'work_anniversaries' => WorkFluxPro_User_Management::get_upcoming_anniversaries()
        );
    }
    
    /**
     * Get project admin dashboard data
     */
    private static function get_project_admin_dashboard_data($user_id) {
        return array(
            'my_projects' => WorkFluxPro_Project_Management::get_user_projects($user_id),
            'pending_tasks' => WorkFluxPro_Project_Management::get_pending_tasks($user_id),
            'overdue_tasks' => WorkFluxPro_Project_Management::get_overdue_tasks($user_id),
            'project_progress' => WorkFluxPro_Project_Management::get_projects_progress($user_id)
        );
    }
    
    /**
     * Get employee dashboard data
     */
    private static function get_employee_dashboard_data($user_id) {
        return array(
            'clock_status' => WorkFluxPro_Time_Tracking::get_current_status($user_id),
            'today_hours' => WorkFluxPro_Time_Tracking::get_today_hours($user_id),
            'assigned_projects' => WorkFluxPro_Project_Management::get_assigned_projects($user_id),
            'my_tasks' => WorkFluxPro_Project_Management::get_user_tasks($user_id),
            'leave_balance' => WorkFluxPro_Leave_Management::get_leave_balance($user_id),
            'recent_leaves' => WorkFluxPro_Leave_Management::get_user_requests($user_id, 5)
        );
    }
    
    /**
     * Create employee
     */
    public static function create_employee() {
        self::verify_nonce();
        
        if (!WorkFluxPro_Roles::user_can('wfp_manage_employees')) {
            self::send_response(false, null, __('Permission denied', 'workflux-pro'));
            return;
        }
        
        // Sanitize user data
        $user_data = array(
            'user_login' => sanitize_user($_POST['user_login'] ?? ''),
            'user_email' => sanitize_email($_POST['user_email'] ?? ''),
            'user_pass' => $_POST['user_pass'] ?? '',
            'first_name' => sanitize_text_field($_POST['first_name'] ?? ''),
            'last_name' => sanitize_text_field($_POST['last_name'] ?? ''),
            'display_name' => sanitize_text_field($_POST['display_name'] ?? ''),
            'role' => 'subscriber'
        );
        
        // Validate required fields
        if (empty($user_data['user_login']) || empty($user_data['user_email']) || empty($user_data['user_pass'])) {
            self::send_response(false, null, __('Username, email, and password are required', 'workflux-pro'));
            return;
        }
        
        // Check if username or email already exists
        if (username_exists($user_data['user_login']) || email_exists($user_data['user_email'])) {
            self::send_response(false, null, __('Username or email already exists', 'workflux-pro'));
            return;
        }
        
        // Create WordPress user
        $user_id = wp_insert_user($user_data);
        
        if (is_wp_error($user_id)) {
            self::send_response(false, null, $user_id->get_error_message());
            return;
        }
        
        // Assign WorkFlux role
        $workflux_role = sanitize_text_field($_POST['workflux_role'] ?? 'wfp_employee');
        $user = new WP_User($user_id);
        $user->remove_role('subscriber');
        $user->add_role($workflux_role);
        
        // Create employee record
        $employee_data = array(
            'user_id' => $user_id,
            'employee_id' => sanitize_text_field($_POST['employee_id'] ?? ''),
            'department' => sanitize_text_field($_POST['department'] ?? ''),
            'designation' => sanitize_text_field($_POST['designation'] ?? ''),
            'hire_date' => sanitize_text_field($_POST['hire_date'] ?? date('Y-m-d')),
            'manager_id' => intval($_POST['manager_id'] ?? 0),
            'status' => 'active'
        );
        
        $result = WorkFluxPro_User_Management::create_employee($employee_data);
        
        if ($result) {
            self::send_response(true, array('user_id' => $user_id), __('Employee created successfully', 'workflux-pro'));
        } else {
            // If employee creation fails, remove the WordPress user
            wp_delete_user($user_id);
            self::send_response(false, null, __('Failed to create employee record', 'workflux-pro'));
        }
    }
    
    /**
     * Update employee
     */
    public static function update_employee() {
        self::verify_nonce();
        
        if (!WorkFluxPro_Roles::user_can('wfp_manage_employees')) {
            self::send_response(false, null, __('Permission denied', 'workflux-pro'));
            return;
        }
        
        $user_id = intval($_POST['user_id'] ?? 0);
        
        if (!$user_id) {
            self::send_response(false, null, __('Invalid user ID', 'workflux-pro'));
            return;
        }
        
        // Update WordPress user data
        $user_data = array(
            'ID' => $user_id,
            'user_email' => sanitize_email($_POST['user_email'] ?? ''),
            'first_name' => sanitize_text_field($_POST['first_name'] ?? ''),
            'last_name' => sanitize_text_field($_POST['last_name'] ?? ''),
            'display_name' => sanitize_text_field($_POST['display_name'] ?? '')
        );
        
        // Update password if provided
        if (!empty($_POST['user_pass'])) {
            $user_data['user_pass'] = $_POST['user_pass'];
        }
        
        $wp_result = wp_update_user($user_data);
        
        if (is_wp_error($wp_result)) {
            self::send_response(false, null, $wp_result->get_error_message());
            return;
        }
        
        // Update WorkFlux role if provided
        if (!empty($_POST['workflux_role'])) {
            $workflux_role = sanitize_text_field($_POST['workflux_role']);
            $user = new WP_User($user_id);
            
            // Remove all WorkFlux roles
            $wfp_roles = array('wfp_super_admin', 'wfp_managing_head', 'wfp_hr_manager', 'wfp_project_admin', 'wfp_employee');
            foreach ($wfp_roles as $role) {
                $user->remove_role($role);
            }
            
            // Add new role
            $user->add_role($workflux_role);
        }
        
        // Update employee data
        $employee_data = array(
            'employee_id' => sanitize_text_field($_POST['employee_id'] ?? ''),
            'department' => sanitize_text_field($_POST['department'] ?? ''),
            'designation' => sanitize_text_field($_POST['designation'] ?? ''),
            'manager_id' => intval($_POST['manager_id'] ?? 0),
            'status' => sanitize_text_field($_POST['status'] ?? 'active')
        );
        
        $result = WorkFluxPro_User_Management::update_employee($user_id, $employee_data);
        
        if ($result) {
            self::send_response(true, null, __('Employee updated successfully', 'workflux-pro'));
        } else {
            self::send_response(false, null, __('Failed to update employee', 'workflux-pro'));
        }
    }
    
    /**
     * Update user role
     */
    public static function update_user_role() {
        self::verify_nonce();
        
        if (!WorkFluxPro_Roles::user_can('wfp_manage_roles')) {
            self::send_response(false, null, __('Permission denied', 'workflux-pro'));
            return;
        }
        
        $user_id = intval($_POST['user_id'] ?? 0);
        $new_role = sanitize_text_field($_POST['new_role'] ?? '');
        $updated_by = get_current_user_id();
        
        $result = WorkFluxPro_Roles::update_user_role($user_id, $new_role, $updated_by);
        
        if ($result) {
            self::send_response(true, null, __('User role updated successfully', 'workflux-pro'));
        } else {
            self::send_response(false, null, __('Failed to update user role', 'workflux-pro'));
        }
    }
    
    /**
     * Get reports
     */
    public static function get_reports() {
        self::verify_nonce();
        
        $report_type = sanitize_text_field($_POST['report_type'] ?? '');
        $date_from = sanitize_text_field($_POST['date_from'] ?? '');
        $date_to = sanitize_text_field($_POST['date_to'] ?? '');
        $user_id = get_current_user_id();
        
        if (!WorkFluxPro_Roles::user_can('wfp_view_own_reports') && !WorkFluxPro_Roles::user_can('wfp_view_team_reports')) {
            self::send_response(false, null, __('Permission denied', 'workflux-pro'));
            return;
        }
        
        $data = WorkFluxPro_Reports::generate_report($report_type, $date_from, $date_to, $user_id);
        
        if ($data) {
            self::send_response(true, $data);
        } else {
            self::send_response(false, null, __('Failed to generate report', 'workflux-pro'));
        }
    }
    
    /**
     * Export report
     */
    public static function export_report() {
        self::verify_nonce();
        
        $report_type = sanitize_text_field($_POST['report_type'] ?? '');
        $date_from = sanitize_text_field($_POST['date_from'] ?? '');
        $date_to = sanitize_text_field($_POST['date_to'] ?? '');
        $format = sanitize_text_field($_POST['format'] ?? 'csv');
        $user_id = get_current_user_id();
        
        if (!WorkFluxPro_Roles::user_can('wfp_view_own_reports') && !WorkFluxPro_Roles::user_can('wfp_view_team_reports')) {
            self::send_response(false, null, __('Permission denied', 'workflux-pro'));
            return;
        }
        
        $export_url = WorkFluxPro_Reports::export_report($report_type, $date_from, $date_to, $format, $user_id);
        
        if ($export_url) {
            self::send_response(true, array('download_url' => $export_url), __('Report exported successfully', 'workflux-pro'));
        } else {
            self::send_response(false, null, __('Failed to export report', 'workflux-pro'));
        }
    }
}