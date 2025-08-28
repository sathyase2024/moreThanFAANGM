<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WFP_Admin {
    public function register() {
        add_action( 'admin_menu', [ $this, 'register_menus' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin' ] );
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
}

