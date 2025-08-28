<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WFP_Activator {
    public static function activate() {
        // Ensure roles/capabilities
        if ( class_exists( 'WFP_Roles' ) ) {
            WFP_Roles::register_roles_and_capabilities();
        }

        // Run initial migrations
        if ( class_exists( 'WFP_Migrations' ) ) {
            ( new WFP_Migrations() )->install();
        }

        // Flush rewrite to register REST routes and pages correctly
        flush_rewrite_rules();
    }
}

