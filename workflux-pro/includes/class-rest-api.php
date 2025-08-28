<?php
/**
 * REST API class
 *
 * @package WorkFluxPro
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WorkFlux Pro REST API Class
 */
class WorkFluxPro_REST_API {
    
    /**
     * API namespace
     */
    const NAMESPACE = 'workflux-pro/v1';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Time tracking endpoints
        register_rest_route(self::NAMESPACE, '/time-tracking/clock-in', array(
            'methods' => 'POST',
            'callback' => array($this, 'clock_in'),
            'permission_callback' => array($this, 'check_clock_permissions'),
            'args' => array(
                'location' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
        
        register_rest_route(self::NAMESPACE, '/time-tracking/clock-out', array(
            'methods' => 'POST',
            'callback' => array($this, 'clock_out'),
            'permission_callback' => array($this, 'check_clock_permissions'),
            'args' => array(
                'description' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                )
            )
        ));
        
        register_rest_route(self::NAMESPACE, '/time-tracking/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_time_status'),
            'permission_callback' => array($this, 'check_authenticated')
        ));
        
        // Project endpoints
        register_rest_route(self::NAMESPACE, '/projects/start', array(
            'methods' => 'POST',
            'callback' => array($this, 'start_project'),
            'permission_callback' => array($this, 'check_project_permissions'),
            'args' => array(
                'project_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'validate_callback' => array($this, 'validate_positive_integer')
                ),
                'task_id' => array(
                    'type' => 'integer',
                    'validate_callback' => array($this, 'validate_positive_integer')
                )
            )
        ));
        
        register_rest_route(self::NAMESPACE, '/projects/stop', array(
            'methods' => 'POST',
            'callback' => array($this, 'stop_project'),
            'permission_callback' => array($this, 'check_project_permissions'),
            'args' => array(
                'tracking_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'validate_callback' => array($this, 'validate_positive_integer')
                ),
                'description' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                )
            )
        ));
        
        register_rest_route(self::NAMESPACE, '/projects/my-projects', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_my_projects'),
            'permission_callback' => array($this, 'check_authenticated')
        ));
        
        // Leave management endpoints
        register_rest_route(self::NAMESPACE, '/leaves/submit', array(
            'methods' => 'POST',
            'callback' => array($this, 'submit_leave_request'),
            'permission_callback' => array($this, 'check_leave_permissions'),
            'args' => array(
                'leave_type' => array(
                    'required' => true,
                    'type' => 'string',
                    'enum' => array_keys(WorkFluxPro_Leave_Management::get_leave_types())
                ),
                'start_date' => array(
                    'required' => true,
                    'type' => 'string',
                    'format' => 'date',
                    'validate_callback' => array($this, 'validate_date')
                ),
                'end_date' => array(
                    'required' => true,
                    'type' => 'string',
                    'format' => 'date',
                    'validate_callback' => array($this, 'validate_date')
                ),
                'reason' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                )
            )
        ));
        
        register_rest_route(self::NAMESPACE, '/leaves/balance', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_leave_balance'),
            'permission_callback' => array($this, 'check_authenticated')
        ));
        
        // External duty endpoints
        register_rest_route(self::NAMESPACE, '/external-duty/submit', array(
            'methods' => 'POST',
            'callback' => array($this, 'submit_external_duty'),
            'permission_callback' => array($this, 'check_external_duty_permissions'),
            'args' => array(
                'purpose' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'location' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'start_date' => array(
                    'required' => true,
                    'type' => 'string',
                    'format' => 'date',
                    'validate_callback' => array($this, 'validate_date')
                ),
                'end_date' => array(
                    'required' => true,
                    'type' => 'string',
                    'format' => 'date',
                    'validate_callback' => array($this, 'validate_date')
                ),
                'start_time' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'end_time' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'description' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                )
            )
        ));
        
        // Dashboard endpoints
        register_rest_route(self::NAMESPACE, '/dashboard/data', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_dashboard_data'),
            'permission_callback' => array($this, 'check_authenticated')
        ));
        
        // Reports endpoints
        register_rest_route(self::NAMESPACE, '/reports/generate', array(
            'methods' => 'POST',
            'callback' => array($this, 'generate_report'),
            'permission_callback' => array($this, 'check_reports_permissions'),
            'args' => array(
                'report_type' => array(
                    'required' => true,
                    'type' => 'string',
                    'enum' => array_keys(WorkFluxPro_Reports::get_report_types())
                ),
                'date_from' => array(
                    'required' => true,
                    'type' => 'string',
                    'format' => 'date',
                    'validate_callback' => array($this, 'validate_date')
                ),
                'date_to' => array(
                    'required' => true,
                    'type' => 'string',
                    'format' => 'date',
                    'validate_callback' => array($this, 'validate_date')
                )
            )
        ));
    }
    
    /**
     * Clock in endpoint
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function clock_in($request) {
        $user_id = get_current_user_id();
        $location = $request->get_param('location') ?: '';
        $ip_address = $request->get_header('X-Forwarded-For') ?: $_SERVER['REMOTE_ADDR'];
        
        $result = WorkFluxPro_Time_Tracking::clock_in($user_id, $location, $ip_address);
        
        if ($result) {
            return new WP_REST_Response(array(
                'success' => true,
                'data' => $result,
                'message' => __('Clocked in successfully', 'workflux-pro')
            ), 200);
        } else {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Failed to clock in', 'workflux-pro')
            ), 400);
        }
    }
    
    /**
     * Clock out endpoint
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function clock_out($request) {
        $user_id = get_current_user_id();
        $description = $request->get_param('description') ?: '';
        
        $result = WorkFluxPro_Time_Tracking::clock_out($user_id, $description);
        
        if ($result) {
            return new WP_REST_Response(array(
                'success' => true,
                'data' => $result,
                'message' => __('Clocked out successfully', 'workflux-pro')
            ), 200);
        } else {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Failed to clock out', 'workflux-pro')
            ), 400);
        }
    }
    
    /**
     * Get time status endpoint
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_time_status($request) {
        $user_id = get_current_user_id();
        $status = WorkFluxPro_Time_Tracking::get_current_status($user_id);
        $today_hours = WorkFluxPro_Time_Tracking::get_today_hours($user_id);
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'status' => $status,
                'today_hours' => $today_hours
            )
        ), 200);
    }
    
    /**
     * Start project endpoint
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function start_project($request) {
        $user_id = get_current_user_id();
        $project_id = $request->get_param('project_id');
        $task_id = $request->get_param('task_id') ?: 0;
        
        $result = WorkFluxPro_Time_Tracking::start_project($user_id, $project_id, $task_id);
        
        if ($result) {
            return new WP_REST_Response(array(
                'success' => true,
                'data' => $result,
                'message' => __('Project tracking started', 'workflux-pro')
            ), 200);
        } else {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Failed to start project tracking', 'workflux-pro')
            ), 400);
        }
    }
    
    /**
     * Stop project endpoint
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function stop_project($request) {
        $user_id = get_current_user_id();
        $tracking_id = $request->get_param('tracking_id');
        $description = $request->get_param('description') ?: '';
        
        $result = WorkFluxPro_Time_Tracking::stop_project($user_id, $tracking_id, $description);
        
        if ($result) {
            return new WP_REST_Response(array(
                'success' => true,
                'data' => $result,
                'message' => __('Project tracking stopped', 'workflux-pro')
            ), 200);
        } else {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Failed to stop project tracking', 'workflux-pro')
            ), 400);
        }
    }
    
    /**
     * Get my projects endpoint
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_my_projects($request) {
        $user_id = get_current_user_id();
        $projects = WorkFluxPro_Project_Management::get_assigned_projects($user_id);
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $projects
        ), 200);
    }
    
    /**
     * Submit leave request endpoint
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function submit_leave_request($request) {
        $user_id = get_current_user_id();
        $data = array(
            'employee_id' => $user_id,
            'leave_type' => $request->get_param('leave_type'),
            'start_date' => $request->get_param('start_date'),
            'end_date' => $request->get_param('end_date'),
            'reason' => $request->get_param('reason') ?: ''
        );
        
        $result = WorkFluxPro_Leave_Management::submit_request($data);
        
        if ($result) {
            return new WP_REST_Response(array(
                'success' => true,
                'data' => array('request_id' => $result),
                'message' => __('Leave request submitted successfully', 'workflux-pro')
            ), 201);
        } else {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Failed to submit leave request', 'workflux-pro')
            ), 400);
        }
    }
    
    /**
     * Get leave balance endpoint
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_leave_balance($request) {
        $user_id = get_current_user_id();
        $balance = WorkFluxPro_Leave_Management::get_leave_balance($user_id);
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $balance
        ), 200);
    }
    
    /**
     * Submit external duty endpoint
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function submit_external_duty($request) {
        $user_id = get_current_user_id();
        $data = array(
            'employee_id' => $user_id,
            'purpose' => $request->get_param('purpose'),
            'location' => $request->get_param('location'),
            'start_date' => $request->get_param('start_date'),
            'end_date' => $request->get_param('end_date'),
            'start_time' => $request->get_param('start_time') ?: '',
            'end_time' => $request->get_param('end_time') ?: '',
            'description' => $request->get_param('description') ?: ''
        );
        
        $result = WorkFluxPro_External_Duty::submit_request($data);
        
        if ($result) {
            return new WP_REST_Response(array(
                'success' => true,
                'data' => array('request_id' => $result),
                'message' => __('External duty request submitted successfully', 'workflux-pro')
            ), 201);
        } else {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Failed to submit external duty request', 'workflux-pro')
            ), 400);
        }
    }
    
    /**
     * Get dashboard data endpoint
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_dashboard_data($request) {
        $user_id = get_current_user_id();
        $user_role = WorkFluxPro_Roles::get_user_workflux_role($user_id);
        
        $data = array();
        
        // Get role-specific dashboard data
        switch ($user_role) {
            case 'wfp_super_admin':
            case 'wfp_managing_head':
                $data = array(
                    'total_employees' => WorkFluxPro_User_Management::get_total_employees(),
                    'active_projects' => WorkFluxPro_Project_Management::get_active_projects_count(),
                    'pending_leaves' => WorkFluxPro_Leave_Management::get_pending_requests_count(),
                    'today_attendance' => WorkFluxPro_Time_Tracking::get_today_attendance()
                );
                break;
            case 'wfp_employee':
                $data = array(
                    'clock_status' => WorkFluxPro_Time_Tracking::get_current_status($user_id),
                    'today_hours' => WorkFluxPro_Time_Tracking::get_today_hours($user_id),
                    'assigned_projects' => WorkFluxPro_Project_Management::get_assigned_projects($user_id),
                    'my_tasks' => WorkFluxPro_Project_Management::get_user_tasks($user_id, array('limit' => 5))
                );
                break;
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $data
        ), 200);
    }
    
    /**
     * Generate report endpoint
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function generate_report($request) {
        $user_id = get_current_user_id();
        $report_type = $request->get_param('report_type');
        $date_from = $request->get_param('date_from');
        $date_to = $request->get_param('date_to');
        
        $report = WorkFluxPro_Reports::generate_report($report_type, $date_from, $date_to, $user_id);
        
        if ($report) {
            return new WP_REST_Response(array(
                'success' => true,
                'data' => $report
            ), 200);
        } else {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Failed to generate report', 'workflux-pro')
            ), 400);
        }
    }
    
    /**
     * Check if user is authenticated
     *
     * @return bool
     */
    public function check_authenticated() {
        return is_user_logged_in();
    }
    
    /**
     * Check clock permissions
     *
     * @return bool
     */
    public function check_clock_permissions() {
        return is_user_logged_in() && WorkFluxPro_Roles::user_can('wfp_clock_in_out');
    }
    
    /**
     * Check project permissions
     *
     * @return bool
     */
    public function check_project_permissions() {
        return is_user_logged_in() && WorkFluxPro_Roles::user_can('wfp_start_stop_projects');
    }
    
    /**
     * Check leave permissions
     *
     * @return bool
     */
    public function check_leave_permissions() {
        return is_user_logged_in() && WorkFluxPro_Roles::user_can('wfp_submit_leave_requests');
    }
    
    /**
     * Check external duty permissions
     *
     * @return bool
     */
    public function check_external_duty_permissions() {
        return is_user_logged_in() && WorkFluxPro_Roles::user_can('wfp_submit_external_duty');
    }
    
    /**
     * Check reports permissions
     *
     * @return bool
     */
    public function check_reports_permissions() {
        return is_user_logged_in() && (
            WorkFluxPro_Roles::user_can('wfp_view_own_reports') ||
            WorkFluxPro_Roles::user_can('wfp_view_team_reports') ||
            WorkFluxPro_Roles::user_can('wfp_view_all_reports')
        );
    }
    
    /**
     * Validate positive integer
     *
     * @param mixed $value
     * @return bool
     */
    public function validate_positive_integer($value) {
        return is_numeric($value) && intval($value) > 0;
    }
    
    /**
     * Validate date format
     *
     * @param string $value
     * @return bool
     */
    public function validate_date($value) {
        $date = DateTime::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value;
    }
}