<?php
/**
 * Permissions management class
 *
 * @package WorkFluxPro
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WorkFlux Pro Permissions Class
 */
class WorkFluxPro_Permissions {
    
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
        add_filter('user_has_cap', array($this, 'check_permissions'), 10, 3);
    }
    
    /**
     * Check user permissions
     *
     * @param array $allcaps
     * @param array $caps
     * @param array $args
     * @return array
     */
    public function check_permissions($allcaps, $caps, $args) {
        if (empty($caps)) {
            return $allcaps;
        }
        
        $user_id = isset($args[1]) ? $args[1] : get_current_user_id();
        $user_role = WorkFluxPro_Roles::get_user_workflux_role($user_id);
        
        if (!$user_role) {
            return $allcaps;
        }
        
        foreach ($caps as $cap) {
            if (strpos($cap, 'wfp_') === 0) {
                $allcaps[$cap] = $this->user_has_capability($user_id, $cap, $args);
            }
        }
        
        return $allcaps;
    }
    
    /**
     * Check if user has specific capability
     *
     * @param int $user_id
     * @param string $capability
     * @param array $args
     * @return bool
     */
    private function user_has_capability($user_id, $capability, $args = array()) {
        $user_role = WorkFluxPro_Roles::get_user_workflux_role($user_id);
        
        if (!$user_role) {
            return false;
        }
        
        // Super admin has all capabilities
        if ($user_role === 'wfp_super_admin') {
            return true;
        }
        
        // Check role-specific capabilities
        $role_caps = WorkFluxPro_Roles::ROLES[$user_role]['capabilities'] ?? array();
        
        if (in_array($capability, $role_caps)) {
            return $this->check_contextual_permissions($user_id, $capability, $args);
        }
        
        return false;
    }
    
    /**
     * Check contextual permissions
     *
     * @param int $user_id
     * @param string $capability
     * @param array $args
     * @return bool
     */
    private function check_contextual_permissions($user_id, $capability, $args) {
        switch ($capability) {
            case 'wfp_approve_leaves':
            case 'wfp_approve_external_duty':
                return $this->can_approve_requests($user_id, $args);
                
            case 'wfp_manage_team':
            case 'wfp_view_team_reports':
                return $this->can_manage_team($user_id, $args);
                
            case 'wfp_manage_projects':
                return $this->can_manage_projects($user_id, $args);
                
            case 'wfp_assign_projects':
                return $this->can_assign_projects($user_id, $args);
                
            default:
                return true;
        }
    }
    
    /**
     * Check if user can approve requests
     *
     * @param int $user_id
     * @param array $args
     * @return bool
     */
    private function can_approve_requests($user_id, $args) {
        $user_role = WorkFluxPro_Roles::get_user_workflux_role($user_id);
        
        // Super admin, managing head, and HR manager can approve all requests
        if (in_array($user_role, array('wfp_super_admin', 'wfp_managing_head', 'wfp_hr_manager'))) {
            return true;
        }
        
        // Project admin can approve requests from their project team members
        if ($user_role === 'wfp_project_admin') {
            // Additional logic to check if the requester is in the project admin's projects
            return $this->is_project_team_member($user_id, $args);
        }
        
        return false;
    }
    
    /**
     * Check if user can manage team
     *
     * @param int $user_id
     * @param array $args
     * @return bool
     */
    private function can_manage_team($user_id, $args) {
        $user_role = WorkFluxPro_Roles::get_user_workflux_role($user_id);
        
        // Super admin and managing head can manage all teams
        if (in_array($user_role, array('wfp_super_admin', 'wfp_managing_head'))) {
            return true;
        }
        
        // HR manager can manage employees
        if ($user_role === 'wfp_hr_manager') {
            return true;
        }
        
        // Project admin can manage their project teams
        if ($user_role === 'wfp_project_admin') {
            return $this->has_project_team($user_id);
        }
        
        return false;
    }
    
    /**
     * Check if user can manage projects
     *
     * @param int $user_id
     * @param array $args
     * @return bool
     */
    private function can_manage_projects($user_id, $args) {
        $user_role = WorkFluxPro_Roles::get_user_workflux_role($user_id);
        
        // Super admin can manage all projects
        if ($user_role === 'wfp_super_admin') {
            return true;
        }
        
        // Managing head can manage team projects
        if ($user_role === 'wfp_managing_head') {
            return true;
        }
        
        // Project admin can manage their assigned projects
        if ($user_role === 'wfp_project_admin') {
            if (isset($args[2])) {
                $project_id = $args[2];
                return $this->is_project_owner($user_id, $project_id);
            }
            return true; // Allow creating new projects
        }
        
        return false;
    }
    
    /**
     * Check if user can assign projects
     *
     * @param int $user_id
     * @param array $args
     * @return bool
     */
    private function can_assign_projects($user_id, $args) {
        $user_role = WorkFluxPro_Roles::get_user_workflux_role($user_id);
        
        // Super admin, managing head, and HR manager can assign any projects
        if (in_array($user_role, array('wfp_super_admin', 'wfp_managing_head', 'wfp_hr_manager'))) {
            return true;
        }
        
        // Project admin can assign their projects
        if ($user_role === 'wfp_project_admin') {
            if (isset($args[2])) {
                $project_id = $args[2];
                return $this->is_project_owner($user_id, $project_id);
            }
        }
        
        return false;
    }
    
    /**
     * Check if user is project owner
     *
     * @param int $user_id
     * @param int $project_id
     * @return bool
     */
    private function is_project_owner($user_id, $project_id) {
        global $wpdb;
        
        $projects_table = $wpdb->prefix . 'wfp_projects';
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        // Get employee record
        $employee = $wpdb->get_row($wpdb->prepare("
            SELECT id FROM $employees_table WHERE user_id = %d
        ", $user_id));
        
        if (!$employee) {
            return false;
        }
        
        // Check if user created the project or is assigned to it
        $project = $wpdb->get_row($wpdb->prepare("
            SELECT id FROM $projects_table 
            WHERE id = %d AND (created_by = %d OR assigned_to = %d)
        ", $project_id, $employee->id, $employee->id));
        
        return !empty($project);
    }
    
    /**
     * Check if requester is in project admin's team
     *
     * @param int $project_admin_id
     * @param array $args
     * @return bool
     */
    private function is_project_team_member($project_admin_id, $args) {
        global $wpdb;
        
        // This would require additional context about the request
        // For now, return true - in real implementation, check if the requester
        // is assigned to any projects managed by this project admin
        return true;
    }
    
    /**
     * Check if user has project team
     *
     * @param int $user_id
     * @return bool
     */
    private function has_project_team($user_id) {
        global $wpdb;
        
        $projects_table = $wpdb->prefix . 'wfp_projects';
        $assignments_table = $wpdb->prefix . 'wfp_project_assignments';
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        // Get employee record
        $employee = $wpdb->get_row($wpdb->prepare("
            SELECT id FROM $employees_table WHERE user_id = %d
        ", $user_id));
        
        if (!$employee) {
            return false;
        }
        
        // Check if user has any projects with team members
        $projects = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT p.id)
            FROM $projects_table p
            INNER JOIN $assignments_table pa ON p.id = pa.project_id
            WHERE (p.created_by = %d OR p.assigned_to = %d)
            AND pa.status = 'active'
        ", $employee->id, $employee->id));
        
        return $projects > 0;
    }
    
    /**
     * Get user's accessible employees
     *
     * @param int $user_id
     * @return array
     */
    public static function get_accessible_employees($user_id) {
        $user_role = WorkFluxPro_Roles::get_user_workflux_role($user_id);
        
        if (!$user_role) {
            return array();
        }
        
        switch ($user_role) {
            case 'wfp_super_admin':
                return WorkFluxPro_User_Management::get_all_employees();
                
            case 'wfp_managing_head':
                return WorkFluxPro_Roles::get_subordinates($user_id);
                
            case 'wfp_hr_manager':
                return WorkFluxPro_User_Management::get_all_employees();
                
            case 'wfp_project_admin':
                return self::get_project_team_members($user_id);
                
            case 'wfp_employee':
                return array($user_id); // Only self
                
            default:
                return array();
        }
    }
    
    /**
     * Get project team members for project admin
     *
     * @param int $user_id
     * @return array
     */
    private static function get_project_team_members($user_id) {
        global $wpdb;
        
        $projects_table = $wpdb->prefix . 'wfp_projects';
        $assignments_table = $wpdb->prefix . 'wfp_project_assignments';
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        // Get employee record
        $employee = $wpdb->get_row($wpdb->prepare("
            SELECT id FROM $employees_table WHERE user_id = %d
        ", $user_id));
        
        if (!$employee) {
            return array();
        }
        
        // Get all team members from projects managed by this user
        $team_members = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT e.user_id, e.employee_id, u.display_name, u.user_email
            FROM $assignments_table pa
            INNER JOIN $projects_table p ON pa.project_id = p.id
            INNER JOIN $employees_table e ON pa.employee_id = e.id
            INNER JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE (p.created_by = %d OR p.assigned_to = %d)
            AND pa.status = 'active'
            AND e.status = 'active'
        ", $employee->id, $employee->id));
        
        return $team_members;
    }
    
    /**
     * Check if user can view specific data
     *
     * @param int $user_id
     * @param string $data_type
     * @param int $target_user_id
     * @return bool
     */
    public static function can_view_user_data($user_id, $data_type, $target_user_id) {
        // Users can always view their own data
        if ($user_id === $target_user_id) {
            return true;
        }
        
        $user_role = WorkFluxPro_Roles::get_user_workflux_role($user_id);
        
        if (!$user_role) {
            return false;
        }
        
        // Super admin can view all data
        if ($user_role === 'wfp_super_admin') {
            return true;
        }
        
        // Check hierarchical permissions
        if (WorkFluxPro_Roles::can_manage_user($user_id, $target_user_id)) {
            return true;
        }
        
        // Check if target user is in accessible employees list
        $accessible_employees = self::get_accessible_employees($user_id);
        $accessible_user_ids = array_column($accessible_employees, 'user_id');
        
        return in_array($target_user_id, $accessible_user_ids);
    }
}