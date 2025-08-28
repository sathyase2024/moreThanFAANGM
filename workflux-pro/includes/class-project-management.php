<?php
/**
 * Project management class
 *
 * @package WorkFluxPro
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WorkFlux Pro Project Management Class
 */
class WorkFluxPro_Project_Management {
    
    /**
     * Project statuses
     */
    const PROJECT_STATUSES = array(
        'planning' => 'Planning',
        'active' => 'Active',
        'on_hold' => 'On Hold',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled'
    );
    
    /**
     * Task statuses
     */
    const TASK_STATUSES = array(
        'todo' => 'To Do',
        'in_progress' => 'In Progress',
        'review' => 'In Review',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled'
    );
    
    /**
     * Priority levels
     */
    const PRIORITIES = array(
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent'
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
     * Create new project
     *
     * @param array $data
     * @return int|false
     */
    public static function create_project($data) {
        global $wpdb;
        
        // Validate required fields
        if (empty($data['name']) || empty($data['project_code'])) {
            return false;
        }
        
        // Check if project code already exists
        $projects_table = $wpdb->prefix . 'wfp_projects';
        $existing = $wpdb->get_var($wpdb->prepare("
            SELECT id FROM $projects_table WHERE project_code = %s
        ", $data['project_code']));
        
        if ($existing) {
            return false; // Project code already exists
        }
        
        // Get creator employee record
        $creator = WorkFluxPro_User_Management::get_employee_by_user_id($data['created_by']);
        if (!$creator) {
            return false;
        }
        
        $defaults = array(
            'description' => '',
            'client' => '',
            'start_date' => null,
            'end_date' => null,
            'estimated_hours' => 0,
            'status' => 'planning',
            'priority' => 'medium',
            'assigned_to' => null
        );
        
        $project_data = wp_parse_args($data, $defaults);
        $project_data['created_by'] = $creator->id;
        
        $result = $wpdb->insert(
            $projects_table,
            $project_data,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%d', '%d')
        );
        
        if ($result) {
            $project_id = $wpdb->insert_id;
            
            // Auto-assign creator to project
            self::assign_project($project_id, $creator->id, 'Project Manager', $creator->id);
            
            // Log the action
            do_action('workflux_pro_project_created', $project_id, $data);
            
            return $project_id;
        }
        
        return false;
    }
    
    /**
     * Update project
     *
     * @param int $project_id
     * @param array $data
     * @return bool
     */
    public static function update_project($project_id, $data) {
        global $wpdb;
        
        $projects_table = $wpdb->prefix . 'wfp_projects';
        
        // Remove non-updatable fields
        unset($data['id'], $data['created_by'], $data['created_at']);
        
        if (empty($data)) {
            return false;
        }
        
        $result = $wpdb->update(
            $projects_table,
            $data,
            array('id' => $project_id),
            null,
            array('%d')
        );
        
        if ($result !== false) {
            // Log the action
            do_action('workflux_pro_project_updated', $project_id, $data);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get project by ID
     *
     * @param int $project_id
     * @return object|null
     */
    public static function get_project($project_id) {
        global $wpdb;
        
        $projects_table = $wpdb->prefix . 'wfp_projects';
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT p.*, 
                   ce.employee_id as creator_employee_id, cu.display_name as creator_name,
                   ae.employee_id as assigned_employee_id, au.display_name as assigned_name
            FROM $projects_table p
            LEFT JOIN $employees_table ce ON p.created_by = ce.id
            LEFT JOIN {$wpdb->users} cu ON ce.user_id = cu.ID
            LEFT JOIN $employees_table ae ON p.assigned_to = ae.id
            LEFT JOIN {$wpdb->users} au ON ae.user_id = au.ID
            WHERE p.id = %d
        ", $project_id));
    }
    
    /**
     * Get all projects
     *
     * @param array $args
     * @return array
     */
    public static function get_projects($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => null,
            'created_by' => null,
            'assigned_to' => null,
            'priority' => null,
            'client' => null,
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $projects_table = $wpdb->prefix . 'wfp_projects';
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        $where_conditions = array('1=1');
        $query_params = array();
        
        if ($args['status']) {
            $where_conditions[] = 'p.status = %s';
            $query_params[] = $args['status'];
        }
        
        if ($args['created_by']) {
            $where_conditions[] = 'p.created_by = %d';
            $query_params[] = $args['created_by'];
        }
        
        if ($args['assigned_to']) {
            $where_conditions[] = 'p.assigned_to = %d';
            $query_params[] = $args['assigned_to'];
        }
        
        if ($args['priority']) {
            $where_conditions[] = 'p.priority = %s';
            $query_params[] = $args['priority'];
        }
        
        if ($args['client']) {
            $where_conditions[] = 'p.client LIKE %s';
            $query_params[] = '%' . $wpdb->esc_like($args['client']) . '%';
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        $order_clause = sprintf('ORDER BY p.%s %s', $args['orderby'], $args['order']);
        $limit_clause = sprintf('LIMIT %d OFFSET %d', $args['limit'], $args['offset']);
        
        $query = "
            SELECT p.*, 
                   ce.employee_id as creator_employee_id, cu.display_name as creator_name,
                   ae.employee_id as assigned_employee_id, au.display_name as assigned_name
            FROM $projects_table p
            LEFT JOIN $employees_table ce ON p.created_by = ce.id
            LEFT JOIN {$wpdb->users} cu ON ce.user_id = cu.ID
            LEFT JOIN $employees_table ae ON p.assigned_to = ae.id
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
     * Assign project to employee
     *
     * @param int $project_id
     * @param int $employee_id
     * @param string $role
     * @param int $assigned_by
     * @return bool
     */
    public static function assign_project($project_id, $employee_id, $role = '', $assigned_by = null) {
        global $wpdb;
        
        $assignments_table = $wpdb->prefix . 'wfp_project_assignments';
        
        // Check if assignment already exists
        $existing = $wpdb->get_var($wpdb->prepare("
            SELECT id FROM $assignments_table 
            WHERE project_id = %d AND employee_id = %d AND status = 'active'
        ", $project_id, $employee_id));
        
        if ($existing) {
            return false; // Already assigned
        }
        
        $result = $wpdb->insert(
            $assignments_table,
            array(
                'project_id' => $project_id,
                'employee_id' => $employee_id,
                'role' => $role,
                'assigned_by' => $assigned_by,
                'status' => 'active'
            ),
            array('%d', '%d', '%s', '%d', '%s')
        );
        
        if ($result) {
            // Log the action
            do_action('workflux_pro_project_assigned', $project_id, $employee_id, $assigned_by);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Remove project assignment
     *
     * @param int $project_id
     * @param int $employee_id
     * @return bool
     */
    public static function unassign_project($project_id, $employee_id) {
        global $wpdb;
        
        $assignments_table = $wpdb->prefix . 'wfp_project_assignments';
        
        $result = $wpdb->update(
            $assignments_table,
            array('status' => 'inactive'),
            array(
                'project_id' => $project_id,
                'employee_id' => $employee_id,
                'status' => 'active'
            ),
            array('%s'),
            array('%d', '%d', '%s')
        );
        
        if ($result) {
            do_action('workflux_pro_project_unassigned', $project_id, $employee_id);
            return true;
        }
        
        return false;
    }
    
    /**
     * Get project assignments
     *
     * @param int $project_id
     * @return array
     */
    public static function get_project_assignments($project_id) {
        global $wpdb;
        
        $assignments_table = $wpdb->prefix . 'wfp_project_assignments';
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT pa.*, e.employee_id, u.display_name as employee_name, u.user_email
            FROM $assignments_table pa
            JOIN $employees_table e ON pa.employee_id = e.id
            JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE pa.project_id = %d AND pa.status = 'active'
            ORDER BY pa.assigned_at
        ", $project_id));
    }
    
    /**
     * Get user's assigned projects
     *
     * @param int $user_id
     * @return array
     */
    public static function get_assigned_projects($user_id) {
        global $wpdb;
        
        $employee = WorkFluxPro_User_Management::get_employee_by_user_id($user_id);
        if (!$employee) {
            return array();
        }
        
        $projects_table = $wpdb->prefix . 'wfp_projects';
        $assignments_table = $wpdb->prefix . 'wfp_project_assignments';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT p.*, pa.role, pa.assigned_at
            FROM $projects_table p
            JOIN $assignments_table pa ON p.id = pa.project_id
            WHERE pa.employee_id = %d AND pa.status = 'active'
            ORDER BY pa.assigned_at DESC
        ", $employee->id));
    }
    
    /**
     * Get user's projects (created or assigned)
     *
     * @param int $user_id
     * @return array
     */
    public static function get_user_projects($user_id) {
        global $wpdb;
        
        $employee = WorkFluxPro_User_Management::get_employee_by_user_id($user_id);
        if (!$employee) {
            return array();
        }
        
        $projects_table = $wpdb->prefix . 'wfp_projects';
        $assignments_table = $wpdb->prefix . 'wfp_project_assignments';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT p.*, 
                   CASE WHEN p.created_by = %d THEN 'creator' ELSE pa.role END as user_role
            FROM $projects_table p
            LEFT JOIN $assignments_table pa ON p.id = pa.project_id AND pa.employee_id = %d AND pa.status = 'active'
            WHERE p.created_by = %d OR pa.employee_id = %d
            ORDER BY p.created_at DESC
        ", $employee->id, $employee->id, $employee->id, $employee->id));
    }
    
    /**
     * Create task
     *
     * @param array $data
     * @return int|false
     */
    public static function create_task($data) {
        global $wpdb;
        
        // Validate required fields
        if (empty($data['project_id']) || empty($data['title'])) {
            return false;
        }
        
        // Get creator employee record
        $creator = WorkFluxPro_User_Management::get_employee_by_user_id($data['created_by']);
        if (!$creator) {
            return false;
        }
        
        $tasks_table = $wpdb->prefix . 'wfp_tasks';
        
        $defaults = array(
            'description' => '',
            'assigned_to' => null,
            'estimated_hours' => 0,
            'status' => 'todo',
            'priority' => 'medium',
            'due_date' => null
        );
        
        $task_data = wp_parse_args($data, $defaults);
        $task_data['created_by'] = $creator->id;
        
        $result = $wpdb->insert(
            $tasks_table,
            $task_data,
            array('%d', '%s', '%s', '%d', '%f', '%f', '%s', '%s', '%s', '%d')
        );
        
        if ($result) {
            $task_id = $wpdb->insert_id;
            
            // Log the action
            do_action('workflux_pro_task_created', $task_id, $data);
            
            return $task_id;
        }
        
        return false;
    }
    
    /**
     * Update task
     *
     * @param int $task_id
     * @param array $data
     * @return bool
     */
    public static function update_task($task_id, $data) {
        global $wpdb;
        
        $tasks_table = $wpdb->prefix . 'wfp_tasks';
        
        // Remove non-updatable fields
        unset($data['id'], $data['project_id'], $data['created_by'], $data['created_at']);
        
        if (empty($data)) {
            return false;
        }
        
        $result = $wpdb->update(
            $tasks_table,
            $data,
            array('id' => $task_id),
            null,
            array('%d')
        );
        
        if ($result !== false) {
            // Log the action
            do_action('workflux_pro_task_updated', $task_id, $data);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get task by ID
     *
     * @param int $task_id
     * @return object|null
     */
    public static function get_task($task_id) {
        global $wpdb;
        
        $tasks_table = $wpdb->prefix . 'wfp_tasks';
        $projects_table = $wpdb->prefix . 'wfp_projects';
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT t.*, p.name as project_name, p.project_code,
                   ae.employee_id as assigned_employee_id, au.display_name as assigned_name,
                   ce.employee_id as creator_employee_id, cu.display_name as creator_name
            FROM $tasks_table t
            JOIN $projects_table p ON t.project_id = p.id
            LEFT JOIN $employees_table ae ON t.assigned_to = ae.id
            LEFT JOIN {$wpdb->users} au ON ae.user_id = au.ID
            LEFT JOIN $employees_table ce ON t.created_by = ce.id
            LEFT JOIN {$wpdb->users} cu ON ce.user_id = cu.ID
            WHERE t.id = %d
        ", $task_id));
    }
    
    /**
     * Get project tasks
     *
     * @param int $project_id
     * @param array $args
     * @return array
     */
    public static function get_project_tasks($project_id, $args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => null,
            'assigned_to' => null,
            'priority' => null,
            'limit' => 100,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $tasks_table = $wpdb->prefix . 'wfp_tasks';
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        $where_conditions = array('t.project_id = %d');
        $query_params = array($project_id);
        
        if ($args['status']) {
            $where_conditions[] = 't.status = %s';
            $query_params[] = $args['status'];
        }
        
        if ($args['assigned_to']) {
            $where_conditions[] = 't.assigned_to = %d';
            $query_params[] = $args['assigned_to'];
        }
        
        if ($args['priority']) {
            $where_conditions[] = 't.priority = %s';
            $query_params[] = $args['priority'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        $order_clause = sprintf('ORDER BY t.%s %s', $args['orderby'], $args['order']);
        $limit_clause = sprintf('LIMIT %d OFFSET %d', $args['limit'], $args['offset']);
        
        $query = "
            SELECT t.*,
                   ae.employee_id as assigned_employee_id, au.display_name as assigned_name
            FROM $tasks_table t
            LEFT JOIN $employees_table ae ON t.assigned_to = ae.id
            LEFT JOIN {$wpdb->users} au ON ae.user_id = au.ID
            WHERE $where_clause
            $order_clause
            $limit_clause
        ";
        
        return $wpdb->get_results($wpdb->prepare($query, $query_params));
    }
    
    /**
     * Get user's tasks
     *
     * @param int $user_id
     * @param array $args
     * @return array
     */
    public static function get_user_tasks($user_id, $args = array()) {
        global $wpdb;
        
        $employee = WorkFluxPro_User_Management::get_employee_by_user_id($user_id);
        if (!$employee) {
            return array();
        }
        
        $defaults = array(
            'status' => null,
            'limit' => 50,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $tasks_table = $wpdb->prefix . 'wfp_tasks';
        $projects_table = $wpdb->prefix . 'wfp_projects';
        
        $where_conditions = array('t.assigned_to = %d');
        $query_params = array($employee->id);
        
        if ($args['status']) {
            $where_conditions[] = 't.status = %s';
            $query_params[] = $args['status'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        $limit_clause = sprintf('LIMIT %d OFFSET %d', $args['limit'], $args['offset']);
        
        $query = "
            SELECT t.*, p.name as project_name, p.project_code
            FROM $tasks_table t
            JOIN $projects_table p ON t.project_id = p.id
            WHERE $where_clause
            ORDER BY t.due_date ASC, t.priority DESC, t.created_at DESC
            $limit_clause
        ";
        
        return $wpdb->get_results($wpdb->prepare($query, $query_params));
    }
    
    /**
     * Get active projects count
     *
     * @return int
     */
    public static function get_active_projects_count() {
        global $wpdb;
        
        $projects_table = $wpdb->prefix . 'wfp_projects';
        
        $count = $wpdb->get_var("
            SELECT COUNT(*) FROM $projects_table WHERE status = 'active'
        ");
        
        return intval($count);
    }
    
    /**
     * Get pending tasks for user
     *
     * @param int $user_id
     * @return array
     */
    public static function get_pending_tasks($user_id) {
        return self::get_user_tasks($user_id, array('status' => 'todo'));
    }
    
    /**
     * Get overdue tasks for user
     *
     * @param int $user_id
     * @return array
     */
    public static function get_overdue_tasks($user_id) {
        global $wpdb;
        
        $employee = WorkFluxPro_User_Management::get_employee_by_user_id($user_id);
        if (!$employee) {
            return array();
        }
        
        $tasks_table = $wpdb->prefix . 'wfp_tasks';
        $projects_table = $wpdb->prefix . 'wfp_projects';
        $today = date('Y-m-d');
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT t.*, p.name as project_name, p.project_code
            FROM $tasks_table t
            JOIN $projects_table p ON t.project_id = p.id
            WHERE t.assigned_to = %d 
            AND t.status NOT IN ('completed', 'cancelled')
            AND t.due_date < %s
            ORDER BY t.due_date ASC
        ", $employee->id, $today));
    }
    
    /**
     * Get projects progress for user
     *
     * @param int $user_id
     * @return array
     */
    public static function get_projects_progress($user_id) {
        $projects = self::get_user_projects($user_id);
        $progress_data = array();
        
        foreach ($projects as $project) {
            $total_tasks = self::get_project_task_count($project->id);
            $completed_tasks = self::get_project_task_count($project->id, 'completed');
            
            $progress_data[] = array(
                'project' => $project,
                'total_tasks' => $total_tasks,
                'completed_tasks' => $completed_tasks,
                'progress_percentage' => $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100, 2) : 0
            );
        }
        
        return $progress_data;
    }
    
    /**
     * Get project task count
     *
     * @param int $project_id
     * @param string $status
     * @return int
     */
    private static function get_project_task_count($project_id, $status = null) {
        global $wpdb;
        
        $tasks_table = $wpdb->prefix . 'wfp_tasks';
        
        if ($status) {
            $count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM $tasks_table 
                WHERE project_id = %d AND status = %s
            ", $project_id, $status));
        } else {
            $count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM $tasks_table 
                WHERE project_id = %d
            ", $project_id));
        }
        
        return intval($count);
    }
    
    /**
     * Get project statuses
     *
     * @return array
     */
    public static function get_project_statuses() {
        return self::PROJECT_STATUSES;
    }
    
    /**
     * Get task statuses
     *
     * @return array
     */
    public static function get_task_statuses() {
        return self::TASK_STATUSES;
    }
    
    /**
     * Get priorities
     *
     * @return array
     */
    public static function get_priorities() {
        return self::PRIORITIES;
    }
    
    /**
     * Generate project code
     *
     * @param array $args
     * @return string
     */
    public static function generate_project_code($args = array()) {
        $defaults = array(
            'prefix' => 'PRJ',
            'year' => date('Y'),
            'padding' => 3
        );
        
        $args = wp_parse_args($args, $defaults);
        
        global $wpdb;
        $projects_table = $wpdb->prefix . 'wfp_projects';
        
        // Get next sequence number
        $pattern = $args['prefix'] . $args['year'] . '%';
        $last_code = $wpdb->get_var($wpdb->prepare("
            SELECT project_code FROM $projects_table 
            WHERE project_code LIKE %s 
            ORDER BY project_code DESC 
            LIMIT 1
        ", $pattern));
        
        if ($last_code) {
            $number = intval(substr($last_code, strlen($args['prefix'] . $args['year']))) + 1;
        } else {
            $number = 1;
        }
        
        return $args['prefix'] . $args['year'] . str_pad($number, $args['padding'], '0', STR_PAD_LEFT);
    }
}