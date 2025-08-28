<?php
// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Optional: full cleanup. Respect a flag to remove data.
$should_delete = (bool) get_option( 'wfp_delete_data_on_uninstall', false );
if ( ! $should_delete ) {
    return;
}

global $wpdb;
$tables = [
    'wfp_employees', 'wfp_projects', 'wfp_project_members', 'wfp_tasks', 'wfp_timesheets',
    'wfp_attendance', 'wfp_leave_types', 'wfp_leaves', 'wfp_external_duty', 'wfp_approvals',
    'wfp_notifications', 'wfp_audit_logs', 'wfp_documents'
];
foreach ( $tables as $t ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$t}" );
}
delete_option( 'wfp_db_version' );

