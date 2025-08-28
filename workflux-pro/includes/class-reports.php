<?php
/**
 * Reports management class
 *
 * @package WorkFluxPro
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WorkFlux Pro Reports Class
 */
class WorkFluxPro_Reports {
    
    /**
     * Report types
     */
    const REPORT_TYPES = array(
        'attendance' => 'Attendance Report',
        'timesheet' => 'Timesheet Report',
        'project_summary' => 'Project Summary Report',
        'employee_performance' => 'Employee Performance Report',
        'leave_summary' => 'Leave Summary Report',
        'project_time_tracking' => 'Project Time Tracking Report',
        'department_summary' => 'Department Summary Report',
        'productivity' => 'Productivity Report'
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
     * Generate report
     *
     * @param string $report_type
     * @param string $date_from
     * @param string $date_to
     * @param int $user_id
     * @param array $filters
     * @return array|false
     */
    public static function generate_report($report_type, $date_from, $date_to, $user_id, $filters = array()) {
        if (!isset(self::REPORT_TYPES[$report_type])) {
            return false;
        }
        
        // Check permissions
        if (!self::can_view_report($report_type, $user_id)) {
            return false;
        }
        
        $method = 'generate_' . $report_type . '_report';
        if (method_exists(__CLASS__, $method)) {
            return self::$method($date_from, $date_to, $user_id, $filters);
        }
        
        return false;
    }
    
    /**
     * Generate attendance report
     *
     * @param string $date_from
     * @param string $date_to
     * @param int $user_id
     * @param array $filters
     * @return array
     */
    private static function generate_attendance_report($date_from, $date_to, $user_id, $filters = array()) {
        global $wpdb;
        
        $time_tracking_table = $wpdb->prefix . 'wfp_time_tracking';
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        $where_conditions = array(
            'DATE(tt.clock_in) BETWEEN %s AND %s',
            'tt.status = "completed"'
        );
        $query_params = array($date_from, $date_to);
        
        // Apply filters based on user role
        $accessible_employees = WorkFluxPro_Permissions::get_accessible_employees($user_id);
        if (!empty($accessible_employees)) {
            $employee_ids = array_column($accessible_employees, 'id');
            $placeholders = implode(',', array_fill(0, count($employee_ids), '%d'));
            $where_conditions[] = "e.id IN ($placeholders)";
            $query_params = array_merge($query_params, $employee_ids);
        }
        
        if (!empty($filters['department'])) {
            $where_conditions[] = 'e.department = %s';
            $query_params[] = $filters['department'];
        }
        
        if (!empty($filters['employee_id'])) {
            $where_conditions[] = 'e.id = %d';
            $query_params[] = $filters['employee_id'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $attendance_data = $wpdb->get_results($wpdb->prepare("
            SELECT e.employee_id, u.display_name as employee_name, e.department,
                   DATE(tt.clock_in) as date,
                   MIN(tt.clock_in) as first_clock_in,
                   MAX(tt.clock_out) as last_clock_out,
                   SUM(tt.total_hours) as total_hours,
                   COUNT(*) as clock_sessions
            FROM $time_tracking_table tt
            JOIN $employees_table e ON tt.employee_id = e.id
            JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE $where_clause
            GROUP BY e.id, DATE(tt.clock_in)
            ORDER BY u.display_name, DATE(tt.clock_in)
        ", $query_params));
        
        // Calculate summary statistics
        $summary = self::calculate_attendance_summary($attendance_data, $date_from, $date_to);
        
        return array(
            'type' => 'attendance',
            'title' => 'Attendance Report',
            'date_range' => array('from' => $date_from, 'to' => $date_to),
            'data' => $attendance_data,
            'summary' => $summary,
            'generated_at' => current_time('mysql'),
            'generated_by' => $user_id
        );
    }
    
    /**
     * Generate timesheet report
     *
     * @param string $date_from
     * @param string $date_to
     * @param int $user_id
     * @param array $filters
     * @return array
     */
    private static function generate_timesheet_report($date_from, $date_to, $user_id, $filters = array()) {
        global $wpdb;
        
        $time_tracking_table = $wpdb->prefix . 'wfp_time_tracking';
        $employees_table = $wpdb->prefix . 'wfp_employees';
        $projects_table = $wpdb->prefix . 'wfp_projects';
        $tasks_table = $wpdb->prefix . 'wfp_tasks';
        
        $where_conditions = array(
            'DATE(tt.clock_in) BETWEEN %s AND %s',
            'tt.status = "completed"'
        );
        $query_params = array($date_from, $date_to);
        
        // Apply user-specific filters
        $accessible_employees = WorkFluxPro_Permissions::get_accessible_employees($user_id);
        if (!empty($accessible_employees)) {
            $employee_ids = array_column($accessible_employees, 'id');
            $placeholders = implode(',', array_fill(0, count($employee_ids), '%d'));
            $where_conditions[] = "e.id IN ($placeholders)";
            $query_params = array_merge($query_params, $employee_ids);
        }
        
        if (!empty($filters['project_id'])) {
            $where_conditions[] = 'tt.project_id = %d';
            $query_params[] = $filters['project_id'];
        }
        
        if (!empty($filters['employee_id'])) {
            $where_conditions[] = 'e.id = %d';
            $query_params[] = $filters['employee_id'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $timesheet_data = $wpdb->get_results($wpdb->prepare("
            SELECT e.employee_id, u.display_name as employee_name,
                   p.name as project_name, p.project_code,
                   t.title as task_title,
                   tt.clock_in, tt.clock_out, tt.total_hours, tt.description,
                   DATE(tt.clock_in) as date
            FROM $time_tracking_table tt
            JOIN $employees_table e ON tt.employee_id = e.id
            JOIN {$wpdb->users} u ON e.user_id = u.ID
            LEFT JOIN $projects_table p ON tt.project_id = p.id
            LEFT JOIN $tasks_table t ON tt.task_id = t.id
            WHERE $where_clause
            ORDER BY u.display_name, tt.clock_in
        ", $query_params));
        
        // Group by employee and calculate totals
        $grouped_data = self::group_timesheet_by_employee($timesheet_data);
        
        return array(
            'type' => 'timesheet',
            'title' => 'Timesheet Report',
            'date_range' => array('from' => $date_from, 'to' => $date_to),
            'data' => $grouped_data,
            'total_hours' => array_sum(array_column($timesheet_data, 'total_hours')),
            'generated_at' => current_time('mysql'),
            'generated_by' => $user_id
        );
    }
    
    /**
     * Generate project summary report
     *
     * @param string $date_from
     * @param string $date_to
     * @param int $user_id
     * @param array $filters
     * @return array
     */
    private static function generate_project_summary_report($date_from, $date_to, $user_id, $filters = array()) {
        global $wpdb;
        
        $projects_table = $wpdb->prefix . 'wfp_projects';
        $tasks_table = $wpdb->prefix . 'wfp_tasks';
        $assignments_table = $wpdb->prefix . 'wfp_project_assignments';
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        $where_conditions = array('1=1');
        $query_params = array();
        
        if (!empty($filters['status'])) {
            $where_conditions[] = 'p.status = %s';
            $query_params[] = $filters['status'];
        }
        
        if (!empty($filters['client'])) {
            $where_conditions[] = 'p.client LIKE %s';
            $query_params[] = '%' . $wpdb->esc_like($filters['client']) . '%';
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $projects_data = $wpdb->get_results($wpdb->prepare("
            SELECT p.*,
                   (SELECT COUNT(*) FROM $tasks_table WHERE project_id = p.id) as total_tasks,
                   (SELECT COUNT(*) FROM $tasks_table WHERE project_id = p.id AND status = 'completed') as completed_tasks,
                   (SELECT COUNT(*) FROM $assignments_table WHERE project_id = p.id AND status = 'active') as team_members
            FROM $projects_table p
            WHERE $where_clause
            ORDER BY p.created_at DESC
        ", $query_params));
        
        // Calculate project progress and statistics
        foreach ($projects_data as &$project) {
            $project->progress_percentage = $project->total_tasks > 0 ? 
                round(($project->completed_tasks / $project->total_tasks) * 100, 2) : 0;
            $project->hours_remaining = max(0, $project->estimated_hours - $project->actual_hours);
        }
        
        return array(
            'type' => 'project_summary',
            'title' => 'Project Summary Report',
            'date_range' => array('from' => $date_from, 'to' => $date_to),
            'data' => $projects_data,
            'summary' => self::calculate_project_summary($projects_data),
            'generated_at' => current_time('mysql'),
            'generated_by' => $user_id
        );
    }
    
    /**
     * Generate employee performance report
     *
     * @param string $date_from
     * @param string $date_to
     * @param int $user_id
     * @param array $filters
     * @return array
     */
    private static function generate_employee_performance_report($date_from, $date_to, $user_id, $filters = array()) {
        global $wpdb;
        
        $employees_table = $wpdb->prefix . 'wfp_employees';
        $time_tracking_table = $wpdb->prefix . 'wfp_time_tracking';
        $tasks_table = $wpdb->prefix . 'wfp_tasks';
        $projects_table = $wpdb->prefix . 'wfp_projects';
        
        $accessible_employees = WorkFluxPro_Permissions::get_accessible_employees($user_id);
        if (empty($accessible_employees)) {
            return array('data' => array());
        }
        
        $employee_ids = array_column($accessible_employees, 'id');
        $placeholders = implode(',', array_fill(0, count($employee_ids), '%d'));
        
        $performance_data = $wpdb->get_results($wpdb->prepare("
            SELECT e.employee_id, u.display_name as employee_name, e.department, e.designation,
                   COALESCE(SUM(tt.total_hours), 0) as total_hours_worked,
                   COUNT(DISTINCT DATE(tt.clock_in)) as days_worked,
                   (SELECT COUNT(*) FROM $tasks_table t WHERE t.assigned_to = e.id 
                    AND t.created_at BETWEEN %s AND %s) as total_tasks_assigned,
                   (SELECT COUNT(*) FROM $tasks_table t WHERE t.assigned_to = e.id 
                    AND t.status = 'completed' AND t.updated_at BETWEEN %s AND %s) as tasks_completed,
                   (SELECT COUNT(DISTINCT t.project_id) FROM $tasks_table t WHERE t.assigned_to = e.id 
                    AND t.created_at BETWEEN %s AND %s) as projects_involved
            FROM $employees_table e
            JOIN {$wpdb->users} u ON e.user_id = u.ID
            LEFT JOIN $time_tracking_table tt ON e.id = tt.employee_id 
                AND DATE(tt.clock_in) BETWEEN %s AND %s AND tt.status = 'completed'
            WHERE e.id IN ($placeholders) AND e.status = 'active'
            GROUP BY e.id
            ORDER BY u.display_name
        ", array_merge(
            array($date_from . ' 00:00:00', $date_to . ' 23:59:59'),
            array($date_from . ' 00:00:00', $date_to . ' 23:59:59'),
            array($date_from . ' 00:00:00', $date_to . ' 23:59:59'),
            array($date_from, $date_to),
            $employee_ids
        )));
        
        // Calculate performance metrics
        foreach ($performance_data as &$employee) {
            $employee->task_completion_rate = $employee->total_tasks_assigned > 0 ? 
                round(($employee->tasks_completed / $employee->total_tasks_assigned) * 100, 2) : 0;
            $employee->avg_hours_per_day = $employee->days_worked > 0 ? 
                round($employee->total_hours_worked / $employee->days_worked, 2) : 0;
        }
        
        return array(
            'type' => 'employee_performance',
            'title' => 'Employee Performance Report',
            'date_range' => array('from' => $date_from, 'to' => $date_to),
            'data' => $performance_data,
            'summary' => self::calculate_performance_summary($performance_data),
            'generated_at' => current_time('mysql'),
            'generated_by' => $user_id
        );
    }
    
    /**
     * Generate leave summary report
     *
     * @param string $date_from
     * @param string $date_to
     * @param int $user_id
     * @param array $filters
     * @return array
     */
    private static function generate_leave_summary_report($date_from, $date_to, $user_id, $filters = array()) {
        global $wpdb;
        
        $leave_requests_table = $wpdb->prefix . 'wfp_leave_requests';
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        $where_conditions = array(
            'lr.start_date BETWEEN %s AND %s',
            'lr.status = "approved"'
        );
        $query_params = array($date_from, $date_to);
        
        if (!empty($filters['department'])) {
            $where_conditions[] = 'e.department = %s';
            $query_params[] = $filters['department'];
        }
        
        if (!empty($filters['leave_type'])) {
            $where_conditions[] = 'lr.leave_type = %s';
            $query_params[] = $filters['leave_type'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $leave_data = $wpdb->get_results($wpdb->prepare("
            SELECT e.employee_id, u.display_name as employee_name, e.department,
                   lr.leave_type, lr.start_date, lr.end_date, lr.days_requested, lr.reason
            FROM $leave_requests_table lr
            JOIN $employees_table e ON lr.employee_id = e.id
            JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE $where_clause
            ORDER BY lr.start_date DESC
        ", $query_params));
        
        // Group by leave type and calculate statistics
        $leave_statistics = self::calculate_leave_statistics($leave_data);
        
        return array(
            'type' => 'leave_summary',
            'title' => 'Leave Summary Report',
            'date_range' => array('from' => $date_from, 'to' => $date_to),
            'data' => $leave_data,
            'statistics' => $leave_statistics,
            'generated_at' => current_time('mysql'),
            'generated_by' => $user_id
        );
    }
    
    /**
     * Get recent activities
     *
     * @param int $user_id
     * @param int $limit
     * @return array
     */
    public static function get_recent_activities($user_id, $limit = 10) {
        global $wpdb;
        
        $activities = array();
        
        // Recent time tracking
        $time_tracking_table = $wpdb->prefix . 'wfp_time_tracking';
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        $recent_time = $wpdb->get_results($wpdb->prepare("
            SELECT 'clock_in' as activity_type, tt.clock_in as activity_time,
                   u.display_name as employee_name, 'Clocked in' as description
            FROM $time_tracking_table tt
            JOIN $employees_table e ON tt.employee_id = e.id
            JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE tt.clock_in >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY tt.clock_in DESC
            LIMIT %d
        ", $limit));
        
        foreach ($recent_time as $activity) {
            $activities[] = array(
                'type' => $activity->activity_type,
                'time' => $activity->activity_time,
                'description' => $activity->employee_name . ' ' . $activity->description,
                'icon' => 'clock'
            );
        }
        
        // Recent leave requests
        $leave_requests_table = $wpdb->prefix . 'wfp_leave_requests';
        
        $recent_leaves = $wpdb->get_results($wpdb->prepare("
            SELECT 'leave_request' as activity_type, lr.created_at as activity_time,
                   u.display_name as employee_name, lr.leave_type
            FROM $leave_requests_table lr
            JOIN $employees_table e ON lr.employee_id = e.id
            JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE lr.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY lr.created_at DESC
            LIMIT %d
        ", $limit));
        
        foreach ($recent_leaves as $activity) {
            $activities[] = array(
                'type' => $activity->activity_type,
                'time' => $activity->activity_time,
                'description' => $activity->employee_name . ' submitted ' . $activity->leave_type . ' leave request',
                'icon' => 'calendar'
            );
        }
        
        // Sort all activities by time
        usort($activities, function($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });
        
        return array_slice($activities, 0, $limit);
    }
    
    /**
     * Check if user can view report
     *
     * @param string $report_type
     * @param int $user_id
     * @return bool
     */
    private static function can_view_report($report_type, $user_id) {
        $user_role = WorkFluxPro_Roles::get_user_workflux_role($user_id);
        
        switch ($report_type) {
            case 'attendance':
            case 'timesheet':
            case 'employee_performance':
                return WorkFluxPro_Roles::user_can('wfp_view_team_reports', $user_id) || 
                       WorkFluxPro_Roles::user_can('wfp_view_all_reports', $user_id);
                
            case 'project_summary':
            case 'project_time_tracking':
                return WorkFluxPro_Roles::user_can('wfp_view_project_reports', $user_id) || 
                       WorkFluxPro_Roles::user_can('wfp_view_all_reports', $user_id);
                
            case 'leave_summary':
                return WorkFluxPro_Roles::user_can('wfp_view_hr_reports', $user_id) || 
                       WorkFluxPro_Roles::user_can('wfp_view_all_reports', $user_id);
                
            default:
                return WorkFluxPro_Roles::user_can('wfp_view_own_reports', $user_id);
        }
    }
    
    /**
     * Calculate attendance summary
     *
     * @param array $attendance_data
     * @param string $date_from
     * @param string $date_to
     * @return array
     */
    private static function calculate_attendance_summary($attendance_data, $date_from, $date_to) {
        $total_hours = array_sum(array_column($attendance_data, 'total_hours'));
        $unique_employees = count(array_unique(array_column($attendance_data, 'employee_id')));
        $total_days = (strtotime($date_to) - strtotime($date_from)) / (60 * 60 * 24) + 1;
        
        return array(
            'total_hours' => round($total_hours, 2),
            'unique_employees' => $unique_employees,
            'average_hours_per_day' => $unique_employees > 0 ? round($total_hours / ($unique_employees * $total_days), 2) : 0,
            'total_attendance_records' => count($attendance_data)
        );
    }
    
    /**
     * Group timesheet data by employee
     *
     * @param array $timesheet_data
     * @return array
     */
    private static function group_timesheet_by_employee($timesheet_data) {
        $grouped = array();
        
        foreach ($timesheet_data as $entry) {
            $employee_id = $entry->employee_id;
            if (!isset($grouped[$employee_id])) {
                $grouped[$employee_id] = array(
                    'employee_name' => $entry->employee_name,
                    'employee_id' => $employee_id,
                    'total_hours' => 0,
                    'entries' => array()
                );
            }
            
            $grouped[$employee_id]['total_hours'] += $entry->total_hours;
            $grouped[$employee_id]['entries'][] = $entry;
        }
        
        return array_values($grouped);
    }
    
    /**
     * Calculate project summary statistics
     *
     * @param array $projects_data
     * @return array
     */
    private static function calculate_project_summary($projects_data) {
        $total_projects = count($projects_data);
        $completed_projects = count(array_filter($projects_data, function($p) { return $p->status === 'completed'; }));
        $active_projects = count(array_filter($projects_data, function($p) { return $p->status === 'active'; }));
        $total_estimated_hours = array_sum(array_column($projects_data, 'estimated_hours'));
        $total_actual_hours = array_sum(array_column($projects_data, 'actual_hours'));
        
        return array(
            'total_projects' => $total_projects,
            'completed_projects' => $completed_projects,
            'active_projects' => $active_projects,
            'completion_rate' => $total_projects > 0 ? round(($completed_projects / $total_projects) * 100, 2) : 0,
            'total_estimated_hours' => $total_estimated_hours,
            'total_actual_hours' => $total_actual_hours,
            'hours_variance' => $total_actual_hours - $total_estimated_hours
        );
    }
    
    /**
     * Calculate performance summary
     *
     * @param array $performance_data
     * @return array
     */
    private static function calculate_performance_summary($performance_data) {
        $total_employees = count($performance_data);
        $total_hours = array_sum(array_column($performance_data, 'total_hours_worked'));
        $total_tasks_completed = array_sum(array_column($performance_data, 'tasks_completed'));
        $completion_rates = array_column($performance_data, 'task_completion_rate');
        
        return array(
            'total_employees' => $total_employees,
            'total_hours_worked' => round($total_hours, 2),
            'total_tasks_completed' => $total_tasks_completed,
            'average_completion_rate' => $total_employees > 0 ? round(array_sum($completion_rates) / $total_employees, 2) : 0,
            'average_hours_per_employee' => $total_employees > 0 ? round($total_hours / $total_employees, 2) : 0
        );
    }
    
    /**
     * Calculate leave statistics
     *
     * @param array $leave_data
     * @return array
     */
    private static function calculate_leave_statistics($leave_data) {
        $statistics = array();
        $leave_types = WorkFluxPro_Leave_Management::get_leave_types();
        
        foreach ($leave_types as $type => $label) {
            $type_leaves = array_filter($leave_data, function($leave) use ($type) {
                return $leave->leave_type === $type;
            });
            
            $statistics[$type] = array(
                'label' => $label,
                'count' => count($type_leaves),
                'total_days' => array_sum(array_column($type_leaves, 'days_requested'))
            );
        }
        
        return $statistics;
    }
    
    /**
     * Export report to CSV
     *
     * @param array $report_data
     * @return string
     */
    public static function export_to_csv($report_data) {
        $output = fopen('php://temp', 'r+');
        
        if (!empty($report_data['data'])) {
            $first_row = reset($report_data['data']);
            if (is_object($first_row)) {
                $first_row = (array) $first_row;
            }
            
            // Write header
            fputcsv($output, array_keys($first_row));
            
            // Write data
            foreach ($report_data['data'] as $row) {
                if (is_object($row)) {
                    $row = (array) $row;
                }
                fputcsv($output, $row);
            }
        }
        
        rewind($output);
        $csv_content = stream_get_contents($output);
        fclose($output);
        
        return $csv_content;
    }
    
    /**
     * Get available report types
     *
     * @return array
     */
    public static function get_report_types() {
        return self::REPORT_TYPES;
    }
}