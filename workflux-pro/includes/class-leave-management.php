<?php
/**
 * Leave management class
 *
 * @package WorkFluxPro
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WorkFlux Pro Leave Management Class
 */
class WorkFluxPro_Leave_Management {
    
    /**
     * Leave types
     */
    const LEAVE_TYPES = array(
        'annual' => 'Annual Leave',
        'sick' => 'Sick Leave',
        'personal' => 'Personal Leave',
        'maternity' => 'Maternity Leave',
        'paternity' => 'Paternity Leave',
        'emergency' => 'Emergency Leave'
    );
    
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
        // Add any initialization hooks here
    }
    
    /**
     * Submit leave request
     *
     * @param array $data
     * @return int|array int for success, array with error for failure
     */
    public static function submit_request($data) {
        global $wpdb;
        
        // Validate required fields
        if (empty($data['employee_id']) || empty($data['leave_type']) || 
            empty($data['start_date']) || empty($data['end_date'])) {
            return array('error' => __('Required fields are missing', 'workflux-pro'));
        }
        
        // Validate dates
        if (strtotime($data['start_date']) === false || strtotime($data['end_date']) === false) {
            return array('error' => __('Invalid date format', 'workflux-pro'));
        }
        
        if (strtotime($data['start_date']) > strtotime($data['end_date'])) {
            return array('error' => __('Start date cannot be after end date', 'workflux-pro'));
        }
        
        // Check if start date is in the past (allow same day)
        if (strtotime($data['start_date']) < strtotime('today')) {
            return array('error' => __('Cannot request leave for past dates', 'workflux-pro'));
        }
        
        // Get employee record
        $employee = WorkFluxPro_User_Management::get_employee_by_user_id($data['employee_id']);
        if (!$employee) {
            return array('error' => __('Employee record not found', 'workflux-pro'));
        }
        
        // Calculate days requested
        $start_date = new DateTime($data['start_date']);
        $end_date = new DateTime($data['end_date']);
        $interval = $start_date->diff($end_date);
        $days_requested = $interval->days + 1; // Include both start and end dates
        
        // Validate reasonable duration (max 365 days)
        if ($days_requested > 365) {
            return array('error' => __('Leave duration cannot exceed 365 days', 'workflux-pro'));
        }
        
        // Check for overlapping requests
        if (self::has_overlapping_request($employee->id, $data['start_date'], $data['end_date'])) {
            return array('error' => __('You already have a leave request for these dates', 'workflux-pro'));
        }
        
        // Check leave balance
        if (!self::has_sufficient_balance($employee->id, $data['leave_type'], $days_requested)) {
            $balance = self::get_leave_balance($employee->id, $data['leave_type']);
            return array('error' => sprintf(__('Insufficient leave balance. Available: %s days, Requested: %s days', 'workflux-pro'), $balance, $days_requested));
        }
        
        $leave_requests_table = $wpdb->prefix . 'wfp_leave_requests';
        
        $request_data = array(
            'employee_id' => $employee->id,
            'leave_type' => $data['leave_type'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'days_requested' => $days_requested,
            'reason' => $data['reason'] ?? '',
            'status' => 'pending'
        );
        
        $result = $wpdb->insert(
            $leave_requests_table,
            $request_data,
            array('%d', '%s', '%s', '%s', '%f', '%s', '%s')
        );
        
        if ($result) {
            $request_id = $wpdb->insert_id;
            
            // Send notification to approvers
            self::notify_approvers($request_id);
            
            // Log the action and trigger email notifications
            do_action('workflux_pro_leave_request_submitted', $request_id, $data);
            
            return $request_id;
        }
        
        // Log the error for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WorkFlux Pro: Failed to insert leave request - ' . $wpdb->last_error);
        }
        
        return false;
    }
    
    /**
     * Approve or reject leave request
     *
     * @param int $request_id
     * @param string $action
     * @param int $approver_id
     * @param string $comments
     * @return bool
     */
    public static function approve_request($request_id, $action, $approver_id, $comments = '') {
        global $wpdb;
        
        if (!in_array($action, array('approved', 'rejected'))) {
            return false;
        }
        
        $leave_requests_table = $wpdb->prefix . 'wfp_leave_requests';
        
        // Get request details
        $request = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM $leave_requests_table WHERE id = %d AND status = 'pending'
        ", $request_id));
        
        if (!$request) {
            return false;
        }
        
        // Get approver employee record
        $approver_employee = WorkFluxPro_User_Management::get_employee_by_user_id($approver_id);
        if (!$approver_employee) {
            return false;
        }
        
        $result = $wpdb->update(
            $leave_requests_table,
            array(
                'status' => $action,
                'approved_by' => $approver_employee->id,
                'approved_at' => current_time('mysql'),
                'comments' => $comments
            ),
            array('id' => $request_id),
            array('%s', '%d', '%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            // Update leave balance if approved
            if ($action === 'approved') {
                self::update_leave_balance($request->employee_id, $request->leave_type, $request->days_requested);
            }
            
            // Send notification to employee
            self::notify_employee($request_id, $action);
            
            // Log the action
            do_action('workflux_pro_leave_request_' . $action, $request_id, $approver_id);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get leave requests
     *
     * @param array $args
     * @return array
     */
    public static function get_requests($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'employee_id' => null,
            'status' => null,
            'leave_type' => null,
            'start_date' => null,
            'end_date' => null,
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $leave_requests_table = $wpdb->prefix . 'wfp_leave_requests';
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        $where_conditions = array('1=1');
        $query_params = array();
        
        if ($args['employee_id']) {
            $where_conditions[] = 'lr.employee_id = %d';
            $query_params[] = $args['employee_id'];
        }
        
        if ($args['status']) {
            $where_conditions[] = 'lr.status = %s';
            $query_params[] = $args['status'];
        }
        
        if ($args['leave_type']) {
            $where_conditions[] = 'lr.leave_type = %s';
            $query_params[] = $args['leave_type'];
        }
        
        if ($args['start_date']) {
            $where_conditions[] = 'lr.start_date >= %s';
            $query_params[] = $args['start_date'];
        }
        
        if ($args['end_date']) {
            $where_conditions[] = 'lr.end_date <= %s';
            $query_params[] = $args['end_date'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        $order_clause = sprintf('ORDER BY lr.%s %s', $args['orderby'], $args['order']);
        $limit_clause = sprintf('LIMIT %d OFFSET %d', $args['limit'], $args['offset']);
        
        $query = "
            SELECT lr.*, e.employee_id, u.display_name as employee_name,
                   ae.employee_id as approver_employee_id, au.display_name as approver_name
            FROM $leave_requests_table lr
            JOIN $employees_table e ON lr.employee_id = e.id
            JOIN {$wpdb->users} u ON e.user_id = u.ID
            LEFT JOIN $employees_table ae ON lr.approved_by = ae.id
            LEFT JOIN {$wpdb->users} au ON ae.user_id = au.ID
            WHERE $where_clause
            $order_clause
            $limit_clause
        ";
        
        if (!empty($query_params)) {
            return $wpdb->get_results($wpdb->prepare($query, $query_params));
        } else {
            return $wpdb->get_results($query);
        }
    }
    
    /**
     * Get user's leave requests
     *
     * @param int $user_id
     * @param int $limit
     * @return array
     */
    public static function get_user_requests($user_id, $limit = 10) {
        $employee = WorkFluxPro_User_Management::get_employee_by_user_id($user_id);
        if (!$employee) {
            return array();
        }
        
        return self::get_requests(array(
            'employee_id' => $employee->id,
            'limit' => $limit
        ));
    }
    
    /**
     * Get pending requests count
     *
     * @return int
     */
    public static function get_pending_requests_count() {
        global $wpdb;
        
        $leave_requests_table = $wpdb->prefix . 'wfp_leave_requests';
        
        $count = $wpdb->get_var("
            SELECT COUNT(*) FROM $leave_requests_table WHERE status = 'pending'
        ");
        
        return intval($count);
    }
    
    /**
     * Get approved requests count for current month
     *
     * @return int
     */
    public static function get_approved_requests_count() {
        global $wpdb;
        
        $leave_requests_table = $wpdb->prefix . 'wfp_leave_requests';
        $current_month = date('Y-m');
        
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $leave_requests_table 
            WHERE status = 'approved' 
            AND DATE_FORMAT(approved_at, '%%Y-%%m') = %s
        ", $current_month));
        
        return intval($count);
    }
    
    /**
     * Get leave balance for employee
     *
     * @param int $user_id
     * @return array
     */
    public static function get_leave_balance($user_id) {
        $employee = WorkFluxPro_User_Management::get_employee_by_user_id($user_id);
        if (!$employee) {
            return array();
        }
        
        $balance = array();
        
        foreach (self::LEAVE_TYPES as $type => $label) {
            $balance[$type] = array(
                'label' => $label,
                'total' => self::get_total_leave_days($employee->id, $type),
                'used' => self::get_used_leave_days($employee->id, $type),
                'remaining' => 0
            );
            
            $balance[$type]['remaining'] = $balance[$type]['total'] - $balance[$type]['used'];
        }
        
        return $balance;
    }
    
    /**
     * Check if employee has overlapping request
     *
     * @param int $employee_id
     * @param string $start_date
     * @param string $end_date
     * @param int $exclude_request_id
     * @return bool
     */
    private static function has_overlapping_request($employee_id, $start_date, $end_date, $exclude_request_id = null) {
        global $wpdb;
        
        $leave_requests_table = $wpdb->prefix . 'wfp_leave_requests';
        
        $where_conditions = array(
            'employee_id = %d',
            'status IN (\'pending\', \'approved\')',
            '(
                (start_date <= %s AND end_date >= %s) OR
                (start_date <= %s AND end_date >= %s) OR
                (start_date >= %s AND end_date <= %s)
            )'
        );
        
        $query_params = array($employee_id, $start_date, $start_date, $end_date, $end_date, $start_date, $end_date);
        
        if ($exclude_request_id) {
            $where_conditions[] = 'id != %d';
            $query_params[] = $exclude_request_id;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $leave_requests_table WHERE $where_clause
        ", $query_params));
        
        return $count > 0;
    }
    
    /**
     * Check if employee has sufficient leave balance
     *
     * @param int $employee_id
     * @param string $leave_type
     * @param float $days_requested
     * @return bool
     */
    private static function has_sufficient_balance($employee_id, $leave_type, $days_requested) {
        $total_days = self::get_total_leave_days($employee_id, $leave_type);
        $used_days = self::get_used_leave_days($employee_id, $leave_type);
        $remaining_days = $total_days - $used_days;
        
        return $remaining_days >= $days_requested;
    }
    
    /**
     * Get total leave days for employee and type
     *
     * @param int $employee_id
     * @param string $leave_type
     * @return float
     */
    private static function get_total_leave_days($employee_id, $leave_type) {
        // Default leave allocations - can be made configurable
        $default_allocations = array(
            'annual' => 21,
            'sick' => 10,
            'personal' => 5,
            'maternity' => 90,
            'paternity' => 15,
            'emergency' => 3
        );
        
        // Get custom allocation from employee settings or use default
        $allocation = get_option("wfp_leave_allocation_{$leave_type}", $default_allocations[$leave_type] ?? 0);
        
        // Apply filters for custom logic
        return apply_filters('workflux_pro_leave_allocation', $allocation, $employee_id, $leave_type);
    }
    
    /**
     * Get used leave days for employee and type
     *
     * @param int $employee_id
     * @param string $leave_type
     * @return float
     */
    private static function get_used_leave_days($employee_id, $leave_type) {
        global $wpdb;
        
        $leave_requests_table = $wpdb->prefix . 'wfp_leave_requests';
        $current_year = date('Y');
        
        $used_days = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(days_requested) FROM $leave_requests_table 
            WHERE employee_id = %d 
            AND leave_type = %s 
            AND status = 'approved'
            AND YEAR(start_date) = %d
        ", $employee_id, $leave_type, $current_year));
        
        return floatval($used_days);
    }
    
    /**
     * Update leave balance after approval
     *
     * @param int $employee_id
     * @param string $leave_type
     * @param float $days_used
     */
    private static function update_leave_balance($employee_id, $leave_type, $days_used) {
        // This method is called after approval to track usage
        // The balance calculation is done dynamically in get_leave_balance()
        // Additional logic can be added here if needed
        
        do_action('workflux_pro_leave_balance_updated', $employee_id, $leave_type, $days_used);
    }
    
    /**
     * Notify approvers about new leave request
     *
     * @param int $request_id
     */
    private static function notify_approvers($request_id) {
        global $wpdb;
        
        $leave_requests_table = $wpdb->prefix . 'wfp_leave_requests';
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        // Get request details
        $request = $wpdb->get_row($wpdb->prepare("
            SELECT lr.*, e.employee_id, u.display_name as employee_name
            FROM $leave_requests_table lr
            JOIN $employees_table e ON lr.employee_id = e.id
            JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE lr.id = %d
        ", $request_id));
        
        if (!$request) {
            return;
        }
        
        // Get approvers (users with approval capabilities)
        $approvers = get_users(array(
            'meta_key' => 'wp_capabilities',
            'meta_value' => 'wfp_approve_leaves',
            'meta_compare' => 'LIKE'
        ));
        
        foreach ($approvers as $approver) {
            self::send_notification(
                $approver->ID,
                __('New Leave Request', 'workflux-pro'),
                sprintf(
                    __('%s has submitted a leave request for %s', 'workflux-pro'),
                    $request->employee_name,
                    self::LEAVE_TYPES[$request->leave_type]
                ),
                'warning'
            );
        }
    }
    
    /**
     * Notify employee about request status
     *
     * @param int $request_id
     * @param string $status
     */
    private static function notify_employee($request_id, $status) {
        global $wpdb;
        
        $leave_requests_table = $wpdb->prefix . 'wfp_leave_requests';
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        $request = $wpdb->get_row($wpdb->prepare("
            SELECT lr.*, e.user_id
            FROM $leave_requests_table lr
            JOIN $employees_table e ON lr.employee_id = e.id
            WHERE lr.id = %d
        ", $request_id));
        
        if (!$request) {
            return;
        }
        
        $status_text = $status === 'approved' ? __('approved', 'workflux-pro') : __('rejected', 'workflux-pro');
        $notification_type = $status === 'approved' ? 'success' : 'error';
        
        self::send_notification(
            $request->user_id,
            __('Leave Request Update', 'workflux-pro'),
            sprintf(
                __('Your leave request has been %s', 'workflux-pro'),
                $status_text
            ),
            $notification_type
        );
    }
    
    /**
     * Send notification
     *
     * @param int $user_id
     * @param string $title
     * @param string $message
     * @param string $type
     */
    private static function send_notification($user_id, $title, $message, $type = 'info') {
        global $wpdb;
        
        $notifications_table = $wpdb->prefix . 'wfp_notifications';
        
        $wpdb->insert(
            $notifications_table,
            array(
                'user_id' => $user_id,
                'title' => $title,
                'message' => $message,
                'type' => $type
            ),
            array('%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Cancel leave request
     *
     * @param int $request_id
     * @param int $user_id
     * @return bool
     */
    public static function cancel_request($request_id, $user_id) {
        global $wpdb;
        
        $employee = WorkFluxPro_User_Management::get_employee_by_user_id($user_id);
        if (!$employee) {
            return false;
        }
        
        $leave_requests_table = $wpdb->prefix . 'wfp_leave_requests';
        
        $result = $wpdb->update(
            $leave_requests_table,
            array('status' => 'cancelled'),
            array(
                'id' => $request_id,
                'employee_id' => $employee->id,
                'status' => 'pending'
            ),
            array('%s'),
            array('%d', '%d', '%s')
        );
        
        if ($result) {
            do_action('workflux_pro_leave_request_cancelled', $request_id, $user_id);
            return true;
        }
        
        return false;
    }
    
    /**
     * Get leave types
     *
     * @return array
     */
    public static function get_leave_types() {
        return self::LEAVE_TYPES;
    }
    
    /**
     * Get leave statistics
     *
     * @param array $args
     * @return array
     */
    public static function get_leave_statistics($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'start_date' => date('Y-01-01'),
            'end_date' => date('Y-12-31'),
            'department' => null,
            'employee_id' => null
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $leave_requests_table = $wpdb->prefix . 'wfp_leave_requests';
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        $where_conditions = array(
            'lr.start_date >= %s',
            'lr.end_date <= %s',
            'lr.status = \'approved\''
        );
        $query_params = array($args['start_date'], $args['end_date']);
        
        if ($args['department']) {
            $where_conditions[] = 'e.department = %s';
            $query_params[] = $args['department'];
        }
        
        if ($args['employee_id']) {
            $where_conditions[] = 'lr.employee_id = %d';
            $query_params[] = $args['employee_id'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $stats = $wpdb->get_results($wpdb->prepare("
            SELECT lr.leave_type, COUNT(*) as request_count, SUM(lr.days_requested) as total_days
            FROM $leave_requests_table lr
            JOIN $employees_table e ON lr.employee_id = e.id
            WHERE $where_clause
            GROUP BY lr.leave_type
        ", $query_params));
        
        return $stats;
    }
}