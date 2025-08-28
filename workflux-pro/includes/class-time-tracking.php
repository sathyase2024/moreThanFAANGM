<?php
/**
 * Time tracking management class
 *
 * @package WorkFluxPro
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WorkFlux Pro Time Tracking Class
 */
class WorkFluxPro_Time_Tracking {
    
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
     * Clock in employee
     *
     * @param int $user_id
     * @param string $location
     * @param string $ip_address
     * @return array|false
     */
    public static function clock_in($user_id, $location = '', $ip_address = '') {
        global $wpdb;
        
        // Check if user is already clocked in
        if (self::is_clocked_in($user_id)) {
            return false;
        }
        
        $employee = self::get_employee_by_user_id($user_id);
        if (!$employee) {
            return false;
        }
        
        $time_tracking_table = $wpdb->prefix . 'wfp_time_tracking';
        
        $result = $wpdb->insert(
            $time_tracking_table,
            array(
                'employee_id' => $employee->id,
                'clock_in' => current_time('mysql'),
                'location' => $location,
                'ip_address' => $ip_address,
                'status' => 'active'
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            $tracking_id = $wpdb->insert_id;
            
            // Log the action
            do_action('workflux_pro_clock_in', $user_id, $tracking_id);
            
            // Send notification
            self::send_notification($user_id, 'Clock In', 'You have successfully clocked in.');
            
            return array(
                'id' => $tracking_id,
                'clock_in' => current_time('mysql'),
                'location' => $location
            );
        }
        
        return false;
    }
    
    /**
     * Clock out employee
     *
     * @param int $user_id
     * @param string $description
     * @return array|false
     */
    public static function clock_out($user_id, $description = '') {
        global $wpdb;
        
        $employee = self::get_employee_by_user_id($user_id);
        if (!$employee) {
            return false;
        }
        
        $time_tracking_table = $wpdb->prefix . 'wfp_time_tracking';
        
        // Get active tracking record
        $tracking = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM $time_tracking_table 
            WHERE employee_id = %d AND status = 'active' AND clock_out IS NULL
            ORDER BY clock_in DESC LIMIT 1
        ", $employee->id));
        
        if (!$tracking) {
            return false;
        }
        
        $clock_out = current_time('mysql');
        $clock_in = $tracking->clock_in;
        
        // Calculate total hours
        $total_hours = self::calculate_hours($clock_in, $clock_out, $tracking->break_time);
        
        $result = $wpdb->update(
            $time_tracking_table,
            array(
                'clock_out' => $clock_out,
                'total_hours' => $total_hours,
                'description' => $description,
                'status' => 'completed'
            ),
            array('id' => $tracking->id),
            array('%s', '%f', '%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            // Log the action
            do_action('workflux_pro_clock_out', $user_id, $tracking->id, $total_hours);
            
            // Send notification
            self::send_notification($user_id, 'Clock Out', sprintf('You have clocked out. Total hours: %.2f', $total_hours));
            
            return array(
                'id' => $tracking->id,
                'clock_in' => $clock_in,
                'clock_out' => $clock_out,
                'total_hours' => $total_hours,
                'description' => $description
            );
        }
        
        return false;
    }
    
    /**
     * Start project tracking
     *
     * @param int $user_id
     * @param int $project_id
     * @param int $task_id
     * @return array|false
     */
    public static function start_project($user_id, $project_id, $task_id = 0) {
        global $wpdb;
        
        $employee = self::get_employee_by_user_id($user_id);
        if (!$employee) {
            return false;
        }
        
        // Check if user is clocked in
        if (!self::is_clocked_in($user_id)) {
            return false;
        }
        
        // Stop any currently active project tracking
        self::stop_active_project_tracking($user_id);
        
        $time_tracking_table = $wpdb->prefix . 'wfp_time_tracking';
        
        $result = $wpdb->insert(
            $time_tracking_table,
            array(
                'employee_id' => $employee->id,
                'project_id' => $project_id,
                'task_id' => $task_id ?: null,
                'clock_in' => current_time('mysql'),
                'status' => 'active'
            ),
            array('%d', '%d', '%d', '%s', '%s')
        );
        
        if ($result) {
            $tracking_id = $wpdb->insert_id;
            
            // Log the action
            do_action('workflux_pro_project_start', $user_id, $project_id, $task_id, $tracking_id);
            
            return array(
                'id' => $tracking_id,
                'project_id' => $project_id,
                'task_id' => $task_id,
                'started_at' => current_time('mysql')
            );
        }
        
        return false;
    }
    
    /**
     * Stop project tracking
     *
     * @param int $user_id
     * @param int $tracking_id
     * @param string $description
     * @return array|false
     */
    public static function stop_project($user_id, $tracking_id, $description = '') {
        global $wpdb;
        
        $employee = self::get_employee_by_user_id($user_id);
        if (!$employee) {
            return false;
        }
        
        $time_tracking_table = $wpdb->prefix . 'wfp_time_tracking';
        
        // Get tracking record
        $tracking = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM $time_tracking_table 
            WHERE id = %d AND employee_id = %d AND status = 'active' AND project_id IS NOT NULL
        ", $tracking_id, $employee->id));
        
        if (!$tracking) {
            return false;
        }
        
        $clock_out = current_time('mysql');
        $total_hours = self::calculate_hours($tracking->clock_in, $clock_out, $tracking->break_time);
        
        $result = $wpdb->update(
            $time_tracking_table,
            array(
                'clock_out' => $clock_out,
                'total_hours' => $total_hours,
                'description' => $description,
                'status' => 'completed'
            ),
            array('id' => $tracking->id),
            array('%s', '%f', '%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            // Update project/task hours
            self::update_project_hours($tracking->project_id, $total_hours);
            if ($tracking->task_id) {
                self::update_task_hours($tracking->task_id, $total_hours);
            }
            
            // Log the action
            do_action('workflux_pro_project_stop', $user_id, $tracking->project_id, $tracking->task_id, $total_hours);
            
            return array(
                'id' => $tracking->id,
                'project_id' => $tracking->project_id,
                'task_id' => $tracking->task_id,
                'started_at' => $tracking->clock_in,
                'stopped_at' => $clock_out,
                'total_hours' => $total_hours,
                'description' => $description
            );
        }
        
        return false;
    }
    
    /**
     * Check if user is clocked in
     *
     * @param int $user_id
     * @return bool
     */
    public static function is_clocked_in($user_id) {
        global $wpdb;
        
        $employee = self::get_employee_by_user_id($user_id);
        if (!$employee) {
            return false;
        }
        
        $time_tracking_table = $wpdb->prefix . 'wfp_time_tracking';
        
        $active_session = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $time_tracking_table 
            WHERE employee_id = %d AND status = 'active' AND clock_out IS NULL
        ", $employee->id));
        
        return $active_session > 0;
    }
    
    /**
     * Get current status for user
     *
     * @param int $user_id
     * @return array
     */
    public static function get_current_status($user_id) {
        global $wpdb;
        
        $employee = self::get_employee_by_user_id($user_id);
        if (!$employee) {
            return array('status' => 'not_employee');
        }
        
        $time_tracking_table = $wpdb->prefix . 'wfp_time_tracking';
        $projects_table = $wpdb->prefix . 'wfp_projects';
        $tasks_table = $wpdb->prefix . 'wfp_tasks';
        
        // Get active clock session
        $clock_session = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM $time_tracking_table 
            WHERE employee_id = %d AND status = 'active' AND clock_out IS NULL AND project_id IS NULL
            ORDER BY clock_in DESC LIMIT 1
        ", $employee->id));
        
        // Get active project session
        $project_session = $wpdb->get_row($wpdb->prepare("
            SELECT tt.*, p.name as project_name, t.title as task_title
            FROM $time_tracking_table tt
            LEFT JOIN $projects_table p ON tt.project_id = p.id
            LEFT JOIN $tasks_table t ON tt.task_id = t.id
            WHERE tt.employee_id = %d AND tt.status = 'active' AND tt.clock_out IS NULL AND tt.project_id IS NOT NULL
            ORDER BY tt.clock_in DESC LIMIT 1
        ", $employee->id));
        
        $status = array(
            'is_clocked_in' => !empty($clock_session),
            'is_on_project' => !empty($project_session),
            'clock_session' => $clock_session,
            'project_session' => $project_session
        );
        
        if ($clock_session) {
            $status['clocked_in_since'] = $clock_session->clock_in;
            $status['current_duration'] = self::calculate_hours($clock_session->clock_in, current_time('mysql'));
        }
        
        if ($project_session) {
            $status['project_name'] = $project_session->project_name;
            $status['task_title'] = $project_session->task_title;
            $status['project_duration'] = self::calculate_hours($project_session->clock_in, current_time('mysql'));
        }
        
        return $status;
    }
    
    /**
     * Get today's hours for user
     *
     * @param int $user_id
     * @return float
     */
    public static function get_today_hours($user_id) {
        global $wpdb;
        
        $employee = self::get_employee_by_user_id($user_id);
        if (!$employee) {
            return 0;
        }
        
        $time_tracking_table = $wpdb->prefix . 'wfp_time_tracking';
        $today = date('Y-m-d');
        
        $total_hours = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(total_hours) FROM $time_tracking_table 
            WHERE employee_id = %d AND DATE(clock_in) = %s AND status = 'completed'
        ", $employee->id, $today));
        
        // Add current active session time
        $active_session = $wpdb->get_row($wpdb->prepare("
            SELECT clock_in FROM $time_tracking_table 
            WHERE employee_id = %d AND status = 'active' AND clock_out IS NULL AND DATE(clock_in) = %s
            ORDER BY clock_in DESC LIMIT 1
        ", $employee->id, $today));
        
        if ($active_session) {
            $current_hours = self::calculate_hours($active_session->clock_in, current_time('mysql'));
            $total_hours += $current_hours;
        }
        
        return floatval($total_hours);
    }
    
    /**
     * Get today's attendance count
     *
     * @return int
     */
    public static function get_today_attendance() {
        global $wpdb;
        
        $time_tracking_table = $wpdb->prefix . 'wfp_time_tracking';
        $today = date('Y-m-d');
        
        $attendance = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT employee_id) FROM $time_tracking_table 
            WHERE DATE(clock_in) = %s
        ", $today));
        
        return intval($attendance);
    }
    
    /**
     * Get employee hours for date range
     *
     * @param int $user_id
     * @param string $start_date
     * @param string $end_date
     * @return array
     */
    public static function get_employee_hours($user_id, $start_date, $end_date) {
        global $wpdb;
        
        $employee = self::get_employee_by_user_id($user_id);
        if (!$employee) {
            return array();
        }
        
        $time_tracking_table = $wpdb->prefix . 'wfp_time_tracking';
        $projects_table = $wpdb->prefix . 'wfp_projects';
        $tasks_table = $wpdb->prefix . 'wfp_tasks';
        
        $records = $wpdb->get_results($wpdb->prepare("
            SELECT tt.*, p.name as project_name, t.title as task_title
            FROM $time_tracking_table tt
            LEFT JOIN $projects_table p ON tt.project_id = p.id
            LEFT JOIN $tasks_table t ON tt.task_id = t.id
            WHERE tt.employee_id = %d 
            AND DATE(tt.clock_in) BETWEEN %s AND %s
            AND tt.status = 'completed'
            ORDER BY tt.clock_in DESC
        ", $employee->id, $start_date, $end_date));
        
        return $records;
    }
    
    /**
     * Calculate hours between two timestamps
     *
     * @param string $start
     * @param string $end
     * @param int $break_time_minutes
     * @return float
     */
    private static function calculate_hours($start, $end, $break_time_minutes = 0) {
        $start_time = strtotime($start);
        $end_time = strtotime($end);
        
        $seconds = $end_time - $start_time;
        $minutes = $seconds / 60;
        $minutes -= $break_time_minutes;
        
        return round($minutes / 60, 2);
    }
    
    /**
     * Stop any active project tracking for user
     *
     * @param int $user_id
     */
    private static function stop_active_project_tracking($user_id) {
        global $wpdb;
        
        $employee = self::get_employee_by_user_id($user_id);
        if (!$employee) {
            return;
        }
        
        $time_tracking_table = $wpdb->prefix . 'wfp_time_tracking';
        
        // Get active project tracking
        $active_tracking = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $time_tracking_table 
            WHERE employee_id = %d AND status = 'active' AND clock_out IS NULL AND project_id IS NOT NULL
        ", $employee->id));
        
        foreach ($active_tracking as $tracking) {
            $clock_out = current_time('mysql');
            $total_hours = self::calculate_hours($tracking->clock_in, $clock_out, $tracking->break_time);
            
            $wpdb->update(
                $time_tracking_table,
                array(
                    'clock_out' => $clock_out,
                    'total_hours' => $total_hours,
                    'status' => 'completed'
                ),
                array('id' => $tracking->id),
                array('%s', '%f', '%s'),
                array('%d')
            );
            
            // Update project/task hours
            self::update_project_hours($tracking->project_id, $total_hours);
            if ($tracking->task_id) {
                self::update_task_hours($tracking->task_id, $total_hours);
            }
        }
    }
    
    /**
     * Update project hours
     *
     * @param int $project_id
     * @param float $hours
     */
    private static function update_project_hours($project_id, $hours) {
        global $wpdb;
        
        $projects_table = $wpdb->prefix . 'wfp_projects';
        
        $wpdb->query($wpdb->prepare("
            UPDATE $projects_table 
            SET actual_hours = actual_hours + %f 
            WHERE id = %d
        ", $hours, $project_id));
    }
    
    /**
     * Update task hours
     *
     * @param int $task_id
     * @param float $hours
     */
    private static function update_task_hours($task_id, $hours) {
        global $wpdb;
        
        $tasks_table = $wpdb->prefix . 'wfp_tasks';
        
        $wpdb->query($wpdb->prepare("
            UPDATE $tasks_table 
            SET actual_hours = actual_hours + %f 
            WHERE id = %d
        ", $hours, $task_id));
    }
    
    /**
     * Get employee by user ID
     *
     * @param int $user_id
     * @return object|null
     */
    private static function get_employee_by_user_id($user_id) {
        global $wpdb;
        
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT * FROM $employees_table WHERE user_id = %d AND status = 'active'
        ", $user_id));
    }
    
    /**
     * Send notification to user
     *
     * @param int $user_id
     * @param string $title
     * @param string $message
     */
    private static function send_notification($user_id, $title, $message) {
        global $wpdb;
        
        $notifications_table = $wpdb->prefix . 'wfp_notifications';
        
        $wpdb->insert(
            $notifications_table,
            array(
                'user_id' => $user_id,
                'title' => $title,
                'message' => $message,
                'type' => 'info'
            ),
            array('%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Add break time to current session
     *
     * @param int $user_id
     * @param int $minutes
     * @return bool
     */
    public static function add_break_time($user_id, $minutes) {
        global $wpdb;
        
        $employee = self::get_employee_by_user_id($user_id);
        if (!$employee) {
            return false;
        }
        
        $time_tracking_table = $wpdb->prefix . 'wfp_time_tracking';
        
        $result = $wpdb->query($wpdb->prepare("
            UPDATE $time_tracking_table 
            SET break_time = break_time + %d
            WHERE employee_id = %d AND status = 'active' AND clock_out IS NULL
        ", $minutes, $employee->id));
        
        return $result !== false;
    }
    
    /**
     * Get weekly hours summary
     *
     * @param int $user_id
     * @param string $week_start
     * @return array
     */
    public static function get_weekly_hours($user_id, $week_start = null) {
        if (!$week_start) {
            $week_start = date('Y-m-d', strtotime('monday this week'));
        }
        
        $week_end = date('Y-m-d', strtotime($week_start . ' +6 days'));
        
        $daily_hours = array();
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime($week_start . " +$i days"));
            $daily_hours[$date] = self::get_daily_hours($user_id, $date);
        }
        
        return array(
            'week_start' => $week_start,
            'week_end' => $week_end,
            'daily_hours' => $daily_hours,
            'total_hours' => array_sum($daily_hours)
        );
    }
    
    /**
     * Get daily hours for user
     *
     * @param int $user_id
     * @param string $date
     * @return float
     */
    public static function get_daily_hours($user_id, $date) {
        global $wpdb;
        
        $employee = self::get_employee_by_user_id($user_id);
        if (!$employee) {
            return 0;
        }
        
        $time_tracking_table = $wpdb->prefix . 'wfp_time_tracking';
        
        $total_hours = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(total_hours) FROM $time_tracking_table 
            WHERE employee_id = %d AND DATE(clock_in) = %s AND status = 'completed'
        ", $employee->id, $date));
        
        return floatval($total_hours);
    }
}