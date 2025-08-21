<?php
/**
 * Theater Room Designer Uninstall
 * 
 * This file is executed when the plugin is uninstalled via WordPress admin.
 * It cleans up all plugin data from the database.
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('trd_settings');

// Remove database tables
global $wpdb;

$table_name = $wpdb->prefix . 'theater_room_designs';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

// Clear any cached data
wp_cache_flush();

// Remove any uploaded files or directories if they exist
$upload_dir = wp_upload_dir();
$plugin_upload_dir = $upload_dir['basedir'] . '/theater-room-designer';

if (is_dir($plugin_upload_dir)) {
    // Recursively delete directory and contents
    function trd_delete_directory($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? trd_delete_directory($path) : unlink($path);
        }
        
        return rmdir($dir);
    }
    
    trd_delete_directory($plugin_upload_dir);
}

// Remove any custom capabilities if added
$role = get_role('administrator');
if ($role) {
    $role->remove_cap('manage_theater_designs');
}

// Clear any scheduled cron jobs
wp_clear_scheduled_hook('trd_cleanup_old_designs');
wp_clear_scheduled_hook('trd_generate_usage_stats');

// Log uninstall for debugging (optional)
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Theater Room Designer plugin has been uninstalled and all data removed.');
}
?>