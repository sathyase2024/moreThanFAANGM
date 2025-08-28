<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WFP_REST extends WP_REST_Controller {
    public function register_routes() {
        $this->namespace = 'wfp/v1';

        register_rest_route( $this->namespace, '/ping', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => [ $this, 'ping' ],
            'permission_callback' => '__return_true',
        ] );

        // Stubs for later expansion
        register_rest_route( $this->namespace, '/employees', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => [ $this, 'list_employees' ],
            'permission_callback' => function () { return current_user_can( 'read' ); },
        ] );
    }

    public function ping( WP_REST_Request $request ) {
        return new WP_REST_Response( [ 'ok' => true, 'version' => WFP_VERSION ], 200 );
    }

    public function list_employees( WP_REST_Request $request ) {
        global $wpdb;
        $table = $wpdb->prefix . 'wfp_employees';
        $rows  = $wpdb->get_results( "SELECT id, user_id, name, status FROM {$table} ORDER BY id DESC LIMIT 20", ARRAY_A );
        return new WP_REST_Response( [ 'data' => $rows ], 200 );
    }
}

