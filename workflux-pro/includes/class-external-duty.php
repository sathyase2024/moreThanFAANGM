<?php
/**
 * External duty management class
 *
 * @package WorkFluxPro
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WorkFlux Pro External Duty Class
 */
class WorkFluxPro_External_Duty {
    
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
     * Submit external duty request
     *
     * @param array $data
     * @return int|false
     */
    public static function submit_request($data) {
        global $wpdb;
        
        // Validate required fields
        if (empty($data['employee_id']) || empty($data['purpose']) || 
            empty($data['location']) || empty($data['start_date']) || empty($data['end_date'])) {
            return false;
        }
        
        // Get employee record
        $employee = WorkFluxPro_User_Management::get_employee_by_user_id($data['employee_id']);
        if (!$employee) {
            return false;
        }
        
        // Check for overlapping requests
        if (self::has_overlapping_request($employee->id, $data['start_date'], $data['end_date'])) {
            return false;
        }
        
        $external_duty_table = $wpdb->prefix . 'wfp_external_duty';
        
        $request_data = array(
            'employee_id' => $employee->id,
            'purpose' => $data['purpose'],
            'location' => $data['location'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'start_time' => $data['start_time'] ?? null,
            'end_time' => $data['end_time'] ?? null,
            'description' => $data['description'] ?? '',
            'status' => 'pending'
        );
        
        $result = $wpdb->insert(
            $external_duty_table,
            $request_data,
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            $request_id = $wpdb->insert_id;
            
            // Send notification to approvers
            self::notify_approvers($request_id);
            
            // Log the action
            do_action('workflux_pro_external_duty_submitted', $request_id, $data);
            
            return $request_id;
        }
        
        return false;
    }
    
    /**
     * Approve or reject external duty request
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
        
        $external_duty_table = $wpdb->prefix . 'wfp_external_duty';
        
        // Get request details
        $request = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM $external_duty_table WHERE id = %d AND status = 'pending'
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
            $external_duty_table,
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
            // Send notification to employee
            self::notify_employee($request_id, $action);
            
            // Log the action
            do_action('workflux_pro_external_duty_' . $action, $request_id, $approver_id);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get external duty requests
     *
     * @param array $args
     * @return array
     */
    public static function get_requests($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'employee_id' => null,
            'status' => null,
            'start_date' => null,
            'end_date' => null,
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $external_duty_table = $wpdb->prefix . 'wfp_external_duty';
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        $where_conditions = array('1=1');
        $query_params = array();
        
        if ($args['employee_id']) {
            $where_conditions[] = 'ed.employee_id = %d';
            $query_params[] = $args['employee_id'];
        }
        
        if ($args['status']) {
            $where_conditions[] = 'ed.status = %s';
            $query_params[] = $args['status'];
        }
        
        if ($args['start_date']) {
            $where_conditions[] = 'ed.start_date >= %s';
            $query_params[] = $args['start_date'];
        }
        
        if ($args['end_date']) {
            $where_conditions[] = 'ed.end_date <= %s';
            $query_params[] = $args['end_date'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        $order_clause = sprintf('ORDER BY ed.%s %s', $args['orderby'], $args['order']);
        $limit_clause = sprintf('LIMIT %d OFFSET %d', $args['limit'], $args['offset']);
        
        $query = "
            SELECT ed.*, e.employee_id, u.display_name as employee_name,
                   ae.employee_id as approver_employee_id, au.display_name as approver_name
            FROM $external_duty_table ed
            JOIN $employees_table e ON ed.employee_id = e.id
            JOIN {$wpdb->users} u ON e.user_id = u.ID
            LEFT JOIN $employees_table ae ON ed.approved_by = ae.id
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
     * Get user's external duty requests
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
        
        $external_duty_table = $wpdb->prefix . 'wfp_external_duty';
        
        $count = $wpdb->get_var("
            SELECT COUNT(*) FROM $external_duty_table WHERE status = 'pending'
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
        
        $external_duty_table = $wpdb->prefix . 'wfp_external_duty';
        $current_month = date('Y-m');
        
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $external_duty_table 
            WHERE status = 'approved' 
            AND DATE_FORMAT(approved_at, '%%Y-%%m') = %s
        ", $current_month));
        
        return intval($count);
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
        
        $external_duty_table = $wpdb->prefix . 'wfp_external_duty';
        
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
            SELECT COUNT(*) FROM $external_duty_table WHERE $where_clause
        ", $query_params));
        
        return $count > 0;
    }
    
    /**
     * Notify approvers about new external duty request
     *
     * @param int $request_id
     */
    private static function notify_approvers($request_id) {
        global $wpdb;
        
        $external_duty_table = $wpdb->prefix . 'wfp_external_duty';
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        // Get request details
        $request = $wpdb->get_row($wpdb->prepare("
            SELECT ed.*, e.employee_id, u.display_name as employee_name
            FROM $external_duty_table ed
            JOIN $employees_table e ON ed.employee_id = e.id
            JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE ed.id = %d
        ", $request_id));
        
        if (!$request) {
            return;
        }
        
        // Get approvers (users with approval capabilities)
        $approvers = get_users(array(
            'meta_key' => 'wp_capabilities',
            'meta_value' => 'wfp_approve_external_duty',
            'meta_compare' => 'LIKE'
        ));
        
        foreach ($approvers as $approver) {
            self::send_notification(
                $approver->ID,
                __('New External Duty Request', 'workflux-pro'),
                sprintf(
                    __('%s has submitted an external duty request for %s', 'workflux-pro'),
                    $request->employee_name,
                    $request->purpose
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
        
        $external_duty_table = $wpdb->prefix . 'wfp_external_duty';
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        $request = $wpdb->get_row($wpdb->prepare("
            SELECT ed.*, e.user_id
            FROM $external_duty_table ed
            JOIN $employees_table e ON ed.employee_id = e.id
            WHERE ed.id = %d
        ", $request_id));
        
        if (!$request) {
            return;
        }
        
        $status_text = $status === 'approved' ? __('approved', 'workflux-pro') : __('rejected', 'workflux-pro');
        $notification_type = $status === 'approved' ? 'success' : 'error';
        
        self::send_notification(
            $request->user_id,
            __('External Duty Request Update', 'workflux-pro'),
            sprintf(
                __('Your external duty request has been %s', 'workflux-pro'),
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
     * Cancel external duty request
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
        
        $external_duty_table = $wpdb->prefix . 'wfp_external_duty';
        
        $result = $wpdb->update(
            $external_duty_table,
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
            do_action('workflux_pro_external_duty_cancelled', $request_id, $user_id);
            return true;
        }
        
        return false;
    }
    
    /**
     * Get external duty statistics
     *
     * @param array $args
     * @return array
     */
    public static function get_statistics($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'start_date' => date('Y-01-01'),
            'end_date' => date('Y-12-31'),
            'department' => null,
            'employee_id' => null
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $external_duty_table = $wpdb->prefix . 'wfp_external_duty';
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        $where_conditions = array(
            'ed.start_date >= %s',
            'ed.end_date <= %s',
            'ed.status = \'approved\''
        );
        $query_params = array($args['start_date'], $args['end_date']);
        
        if ($args['department']) {
            $where_conditions[] = 'e.department = %s';
            $query_params[] = $args['department'];
        }
        
        if ($args['employee_id']) {
            $where_conditions[] = 'ed.employee_id = %d';
            $query_params[] = $args['employee_id'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $stats = $wpdb->get_results($wpdb->prepare("
            SELECT ed.purpose, COUNT(*) as request_count,
                   AVG(DATEDIFF(ed.end_date, ed.start_date) + 1) as avg_duration
            FROM $external_duty_table ed
            JOIN $employees_table e ON ed.employee_id = e.id
            WHERE $where_clause
            GROUP BY ed.purpose
        ", $query_params));
        
        return $stats;
    }
    
    /**
     * Get upcoming external duties
     *
     * @param int $days_ahead
     * @return array
     */
    public static function get_upcoming_duties($days_ahead = 7) {
        global $wpdb;
        
        $external_duty_table = $wpdb->prefix . 'wfp_external_duty';
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        $today = date('Y-m-d');
        $future_date = date('Y-m-d', strtotime("+$days_ahead days"));
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT ed.*, e.employee_id, u.display_name as employee_name
            FROM $external_duty_table ed
            JOIN $employees_table e ON ed.employee_id = e.id
            JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE ed.status = 'approved'
            AND ed.start_date BETWEEN %s AND %s
            ORDER BY ed.start_date, ed.start_time
        ", $today, $future_date));
    }
    
    /**
     * Get current external duties
     *
     * @return array
     */
    public static function get_current_duties() {
        global $wpdb;
        
        $external_duty_table = $wpdb->prefix . 'wfp_external_duty';
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        $today = date('Y-m-d');
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT ed.*, e.employee_id, u.display_name as employee_name
            FROM $external_duty_table ed
            JOIN $employees_table e ON ed.employee_id = e.id
            JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE ed.status = 'approved'
            AND ed.start_date <= %s
            AND ed.end_date >= %s
            ORDER BY ed.start_date
        ", $today, $today));
    }
    
    /**
     * Update external duty request
     *
     * @param int $request_id
     * @param array $data
     * @param int $user_id
     * @return bool
     */
    public static function update_request($request_id, $data, $user_id) {
        global $wpdb;
        
        $employee = WorkFluxPro_User_Management::get_employee_by_user_id($user_id);
        if (!$employee) {
            return false;
        }
        
        $external_duty_table = $wpdb->prefix . 'wfp_external_duty';
        
        // Only allow updates to pending requests by the employee
        $request = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM $external_duty_table 
            WHERE id = %d AND employee_id = %d AND status = 'pending'
        ", $request_id, $employee->id));
        
        if (!$request) {
            return false;
        }
        
        // Remove non-updatable fields
        unset($data['id'], $data['employee_id'], $data['status'], $data['approved_by'], $data['approved_at']);
        
        if (empty($data)) {
            return false;
        }
        
        $result = $wpdb->update(
            $external_duty_table,
            $data,
            array('id' => $request_id),
            null,
            array('%d')
        );
        
        if ($result !== false) {
            do_action('workflux_pro_external_duty_updated', $request_id, $data, $user_id);
            return true;
        }
        
        return false;
    }
}