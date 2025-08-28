<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WFP_Admin {
    public function register() {
        add_action( 'admin_menu', [ $this, 'register_menus' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public function register_menus() {
        add_menu_page(
            __( 'WorkFlux Pro', 'workflux-pro' ),
            __( 'WorkFlux Pro', 'workflux-pro' ),
            'read',
            'wfp-dashboard',
            [ $this, 'render_dashboard' ],
            'dashicons-chart-line',
            26
        );
        add_submenu_page( 'wfp-dashboard', __( 'Dashboard', 'workflux-pro' ), __( 'Dashboard', 'workflux-pro' ), 'read', 'wfp-dashboard', [ $this, 'render_dashboard' ] );

        add_submenu_page( 'wfp-dashboard', __( 'Employees', 'workflux-pro' ), __( 'Employees', 'workflux-pro' ), 'wfp_manage_employees', 'wfp-employees', [ $this, 'render_employees' ] );
        add_submenu_page( 'wfp-dashboard', __( 'Attendance', 'workflux-pro' ), __( 'Attendance', 'workflux-pro' ), 'wfp_view_team_activity', 'wfp-attendance', [ $this, 'render_attendance' ] );
        add_submenu_page( 'wfp-dashboard', __( 'Leaves', 'workflux-pro' ), __( 'Leaves', 'workflux-pro' ), 'wfp_approve_leave', 'wfp-leaves', [ $this, 'render_leaves' ] );
        add_submenu_page( 'wfp-dashboard', __( 'External Duty', 'workflux-pro' ), __( 'External Duty', 'workflux-pro' ), 'wfp_approve_external_duty', 'wfp-od', [ $this, 'render_od' ] );
        add_submenu_page( 'wfp-dashboard', __( 'Projects', 'workflux-pro' ), __( 'Projects', 'workflux-pro' ), 'wfp_manage_projects', 'wfp-projects', [ $this, 'render_projects' ] );
        add_submenu_page( 'wfp-dashboard', __( 'Reports', 'workflux-pro' ), __( 'Reports', 'workflux-pro' ), 'wfp_view_reports', 'wfp-reports', [ $this, 'render_reports' ] );
        add_submenu_page( 'wfp-dashboard', __( 'Settings', 'workflux-pro' ), __( 'Settings', 'workflux-pro' ), 'wfp_manage_settings', 'wfp-settings', [ $this, 'render_settings' ] );
    }

    public function enqueue_admin( $hook ) {
        if ( strpos( (string) $hook, 'wfp' ) === false ) {
            return;
        }
        wp_enqueue_style( 'wfp-admin' );
        wp_enqueue_script( 'wfp-admin' );
        wp_localize_script( 'wfp-admin', 'WFP', [
            'rest'  => [ 'url' => esc_url_raw( rest_url( 'wfp/v1' ) ) ],
            'nonce' => wp_create_nonce( 'wp_rest' ),
        ] );
    }

    public function render_dashboard() {
        echo '<div class="wrap"><h1>' . esc_html__( 'WorkFlux Pro', 'workflux-pro' ) . '</h1>';
        echo '<p>' . esc_html__( 'Welcome to WorkFlux Pro. Use the left menu to manage employees, projects, time, and approvals.', 'workflux-pro' ) . '</p>';
        echo '</div>';
    }

    public function render_employees() {
        echo '<div class="wrap"><h1>' . esc_html__( 'Employees', 'workflux-pro' ) . '</h1>';
        echo '<p>' . esc_html__( 'Manage employees (stub).', 'workflux-pro' ) . '</p>';
        echo '</div>';
    }

    public function render_attendance() {
        echo '<div class="wrap"><h1>' . esc_html__( 'Attendance', 'workflux-pro' ) . '</h1>';
        echo '<p>' . esc_html__( 'View attendance (stub).', 'workflux-pro' ) . '</p>';
        echo '</div>';
    }

    public function render_leaves() {
        echo '<div class="wrap"><h1>' . esc_html__( 'Leaves', 'workflux-pro' ) . '</h1>';
        echo '<p>' . esc_html__( 'Review and approve leaves (stub).', 'workflux-pro' ) . '</p>';
        echo '</div>';
    }

    public function render_od() {
        echo '<div class="wrap"><h1>' . esc_html__( 'External Duty', 'workflux-pro' ) . '</h1>';
        echo '<p>' . esc_html__( 'Review and approve external duty (stub).', 'workflux-pro' ) . '</p>';
        echo '</div>';
    }

    public function render_projects() {
        echo '<div class="wrap"><h1>' . esc_html__( 'Projects', 'workflux-pro' ) . '</h1>';
        echo '<p>' . esc_html__( 'Manage projects (stub).', 'workflux-pro' ) . '</p>';
        echo '</div>';
    }

    public function render_reports() {
        echo '<div class="wrap"><h1>' . esc_html__( 'Reports', 'workflux-pro' ) . '</h1>';
        echo '<p>' . esc_html__( 'View reports (stub).', 'workflux-pro' ) . '</p>';
        echo '</div>';
    }

    public function register_settings() {
        register_setting( 'wfp_settings', 'wfp_delete_data_on_uninstall', [
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 0,
        ] );

        add_settings_section( 'wfp_general', __( 'General', 'workflux-pro' ), '__return_null', 'wfp-settings' );

        add_settings_field(
            'wfp_delete_data_on_uninstall',
            __( 'Delete data on uninstall', 'workflux-pro' ),
            function () {
                $val = (int) get_option( 'wfp_delete_data_on_uninstall', 0 );
                echo '<label><input type="checkbox" name="wfp_delete_data_on_uninstall" value="1" ' . checked( 1, $val, false ) . ' /> ' . esc_html__( 'When uninstalling, remove all plugin data (tables/options).', 'workflux-pro' ) . '</label>';
            },
            'wfp-settings',
            'wfp_general'
        );
    }

    public function render_settings() {
        if ( ! current_user_can( 'wfp_manage_settings' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'workflux-pro' ) );
        }
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'WorkFlux Pro Settings', 'workflux-pro' ) . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields( 'wfp_settings' );
        do_settings_sections( 'wfp-settings' );
        submit_button();
        echo '</form>';
        echo '</div>';
    }
}

