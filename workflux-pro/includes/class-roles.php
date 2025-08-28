<?php
/**
 * Role management class
 *
 * @package WorkFluxPro
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WorkFlux Pro Roles Class
 */
class WorkFluxPro_Roles {
    
    /**
     * Custom role definitions
     */
    const ROLES = array(
        'wfp_super_admin' => array(
            'name' => 'WorkFlux Super Admin',
            'capabilities' => array(
                // All capabilities
                'read',
                'wfp_manage_all',
                'wfp_manage_employees',
                'wfp_manage_projects',
                'wfp_manage_leaves',
                'wfp_manage_external_duty',
                'wfp_view_all_reports',
                'wfp_manage_roles',
                'wfp_manage_settings',
                'wfp_approve_leaves',
                'wfp_approve_external_duty',
                'wfp_clock_management',
                'wfp_time_tracking',
                'wfp_project_assignment',
                'wfp_user_onboarding'
            )
        ),
        'wfp_managing_head' => array(
            'name' => 'WorkFlux Managing Head',
            'capabilities' => array(
                'read',
                'wfp_manage_team',
                'wfp_approve_leaves',
                'wfp_approve_external_duty',
                'wfp_view_team_reports',
                'wfp_manage_team_projects',
                'wfp_assign_team_tasks',
                'wfp_view_team_time_tracking',
                'wfp_manage_team_members'
            )
        ),
        'wfp_hr_manager' => array(
            'name' => 'WorkFlux HR Manager',
            'capabilities' => array(
                'read',
                'wfp_user_onboarding',
                'wfp_manage_employees',
                'wfp_approve_leaves',
                'wfp_approve_external_duty',
                'wfp_manage_employee_data',
                'wfp_assign_projects',
                'wfp_view_hr_reports',
                'wfp_manage_leave_policies',
                'wfp_employee_performance'
            )
        ),
        'wfp_project_admin' => array(
            'name' => 'WorkFlux Project Admin',
            'capabilities' => array(
                'read',
                'wfp_manage_projects',
                'wfp_assign_tasks',
                'wfp_approve_team_members',
                'wfp_view_project_reports',
                'wfp_project_time_tracking',
                'wfp_manage_project_timeline',
                'wfp_project_budget_view'
            )
        ),
        'wfp_employee' => array(
            'name' => 'WorkFlux Employee',
            'capabilities' => array(
                'read',
                'wfp_clock_in_out',
                'wfp_start_stop_projects',
                'wfp_submit_leave_requests',
                'wfp_submit_external_duty',
                'wfp_view_own_reports',
                'wfp_update_profile',
                'wfp_view_assigned_projects',
                'wfp_submit_timesheets'
            )
        )
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
        // Additional initialization if needed
    }
    
    /**
     * Add custom roles
     */
    public static function add_custom_roles() {
        foreach (self::ROLES as $role_key => $role_data) {
            if (!get_role($role_key)) {
                add_role($role_key, $role_data['name'], $role_data['capabilities']);
            }
        }
        
        // Add capabilities to administrator role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach (self::ROLES['wfp_super_admin']['capabilities'] as $cap) {
                $admin_role->add_cap($cap);
            }
        }
    }
    
    /**
     * Remove custom roles
     */
    public static function remove_custom_roles() {
        foreach (self::ROLES as $role_key => $role_data) {
            remove_role($role_key);
        }
    }
    
    /**
     * Refresh roles and capabilities
     */
    public static function refresh_roles() {
        // Remove and recreate all roles to ensure capabilities are updated
        self::remove_custom_roles();
        self::add_custom_roles();
        
        // Clear any cached capabilities
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        return true;
    }
    
    /**
     * Get user's WorkFlux role
     *
     * @param int $user_id
     * @return string|false
     */
    public static function get_user_workflux_role($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        // Check for WorkFlux roles first
        foreach (array_keys(self::ROLES) as $role) {
            if (in_array($role, $user->roles)) {
                return $role;
            }
        }
        
        // Check if user is administrator
        if (in_array('administrator', $user->roles)) {
            return 'wfp_super_admin';
        }
        
        return false;
    }
    
    /**
     * Check if user has specific WorkFlux capability
     *
     * @param string $capability
     * @param int $user_id
     * @return bool
     */
    public static function user_can($capability, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, $capability);
    }
    
    /**
     * Get role hierarchy level
     *
     * @param string $role
     * @return int
     */
    public static function get_role_level($role) {
        $levels = array(
            'wfp_super_admin' => 5,
            'wfp_managing_head' => 4,
            'wfp_hr_manager' => 3,
            'wfp_project_admin' => 2,
            'wfp_employee' => 1
        );
        
        return isset($levels[$role]) ? $levels[$role] : 0;
    }
    
    /**
     * Check if user can manage another user based on hierarchy
     *
     * @param int $manager_id
     * @param int $employee_id
     * @return bool
     */
    public static function can_manage_user($manager_id, $employee_id) {
        $manager_role = self::get_user_workflux_role($manager_id);
        $employee_role = self::get_user_workflux_role($employee_id);
        
        if (!$manager_role || !$employee_role) {
            return false;
        }
        
        $manager_level = self::get_role_level($manager_role);
        $employee_level = self::get_role_level($employee_role);
        
        return $manager_level > $employee_level;
    }
    
    /**
     * Get users by WorkFlux role
     *
     * @param string $role
     * @return array
     */
    public static function get_users_by_role($role) {
        $users = get_users(array(
            'role' => $role,
            'meta_query' => array(
                array(
                    'key' => 'wfp_employee_status',
                    'value' => 'active',
                    'compare' => '='
                )
            )
        ));
        
        return $users;
    }
    
    /**
     * Get subordinates for a user
     *
     * @param int $user_id
     * @return array
     */
    public static function get_subordinates($user_id) {
        global $wpdb;
        
        $user_role = self::get_user_workflux_role($user_id);
        if (!$user_role) {
            return array();
        }
        
        $user_level = self::get_role_level($user_role);
        $subordinate_roles = array();
        
        foreach (self::ROLES as $role_key => $role_data) {
            if (self::get_role_level($role_key) < $user_level) {
                $subordinate_roles[] = $role_key;
            }
        }
        
        if (empty($subordinate_roles)) {
            return array();
        }
        
        // Get employees table
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        // Direct subordinates (where manager_id = user_id)
        $direct_subordinates = $wpdb->get_results($wpdb->prepare("
            SELECT e.*, u.display_name, u.user_email 
            FROM $employees_table e
            JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE e.manager_id = %d AND e.status = 'active'
        ", $user_id));
        
        // Role-based subordinates
        $role_subordinates = get_users(array(
            'role__in' => $subordinate_roles,
            'meta_query' => array(
                array(
                    'key' => 'wfp_employee_status',
                    'value' => 'active',
                    'compare' => '='
                )
            )
        ));
        
        return array(
            'direct' => $direct_subordinates,
            'role_based' => $role_subordinates
        );
    }
    
    /**
     * Get role display name
     *
     * @param string $role
     * @return string
     */
    public static function get_role_display_name($role) {
        if (isset(self::ROLES[$role])) {
            return self::ROLES[$role]['name'];
        }
        
        // Handle WordPress default roles
        $wp_roles = wp_roles();
        $role_names = $wp_roles->role_names;
        
        return isset($role_names[$role]) ? translate_user_role($role_names[$role]) : $role;
    }
    
    /**
     * Get all WorkFlux roles
     *
     * @return array
     */
    public static function get_all_roles() {
        return self::ROLES;
    }
    
    /**
     * Update user role
     *
     * @param int $user_id
     * @param string $new_role
     * @param int $updated_by
     * @return bool
     */
    public static function update_user_role($user_id, $new_role, $updated_by = null) {
        if (!isset(self::ROLES[$new_role])) {
            return false;
        }
        
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        // Check permissions
        if ($updated_by && !self::user_can('wfp_manage_roles', $updated_by)) {
            return false;
        }
        
        // Remove old WorkFlux roles
        foreach (array_keys(self::ROLES) as $role) {
            $user->remove_role($role);
        }
        
        // Add new role
        $user->add_role($new_role);
        
        // Log the change
        do_action('workflux_pro_role_changed', $user_id, $new_role, $updated_by);
        
        return true;
    }
}