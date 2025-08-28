<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WFP_Roles {
    public static function register_roles_and_capabilities() {
        $roles = [
            'wfp_owner'         => __( 'Owner', 'workflux-pro' ),
            'wfp_managing_head' => __( 'Managing Head', 'workflux-pro' ),
            'wfp_hr_manager'    => __( 'HR / Manager', 'workflux-pro' ),
            'wfp_project_admin' => __( 'Project Admin', 'workflux-pro' ),
            'wfp_employee'      => __( 'Employee', 'workflux-pro' ),
        ];

        $caps = [
            'wfp_manage_all',
            'wfp_manage_settings',
            'wfp_view_reports',
            'wfp_manage_employees',
            'wfp_view_employees',
            'wfp_manage_projects',
            'wfp_view_projects',
            'wfp_assign_tasks',
            'wfp_approve_leave',
            'wfp_approve_external_duty',
            'wfp_log_attendance',
            'wfp_log_timesheet',
            'wfp_view_team_activity',
        ];

        // Ensure caps exist (WordPress stores caps on roles/users)
        foreach ( $roles as $role_key => $label ) {
            if ( ! get_role( $role_key ) ) {
                add_role( $role_key, $label, [] );
            }
        }

        $map = [
            'wfp_owner'         => $caps,
            'wfp_managing_head' => [ 'wfp_view_reports', 'wfp_view_team_activity', 'wfp_approve_leave', 'wfp_approve_external_duty', 'wfp_view_projects' ],
            'wfp_hr_manager'    => [ 'wfp_manage_employees', 'wfp_view_employees', 'wfp_approve_leave', 'wfp_approve_external_duty', 'wfp_manage_projects' ],
            'wfp_project_admin' => [ 'wfp_manage_projects', 'wfp_view_projects', 'wfp_assign_tasks' ],
            'wfp_employee'      => [ 'wfp_log_attendance', 'wfp_log_timesheet' ],
        ];

        foreach ( $map as $role_key => $role_caps ) {
            $role = get_role( $role_key );
            if ( ! $role ) {
                continue;
            }
            foreach ( $role_caps as $cap ) {
                $role->add_cap( $cap );
            }
        }
    }
}

