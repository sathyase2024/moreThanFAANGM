<?php
/**
 * WorkFlux Pro Activation Check
 * Verify plugin is properly activated and configured
 */

// WordPress environment
if (!defined('ABSPATH')) {
    require_once('../../../../wp-config.php');
}

// Only allow admin users
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

?><!DOCTYPE html>
<html>
<head>
    <title>WorkFlux Pro Activation Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .check-item { margin: 10px 0; padding: 10px; border-left: 4px solid #ddd; }
        .check-pass { border-left-color: #46b450; background: #f7fff7; }
        .check-fail { border-left-color: #dc3232; background: #fef7f7; }
        .check-warn { border-left-color: #ffba00; background: #fffbf0; }
        .status { font-weight: bold; }
        h1, h2 { color: #333; }
        pre { background: #f9f9f9; padding: 10px; overflow-x: auto; }
        .action-buttons { margin: 20px 0; }
        .btn { padding: 10px 15px; margin: 5px; background: #0073aa; color: white; border: none; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #005177; }
        .btn-success { background: #46b450; }
        .btn-warning { background: #ffba00; }
        .btn-danger { background: #dc3232; }
    </style>
</head>
<body>
    <h1>WorkFlux Pro Activation Check</h1>
    
    <?php
    
    $checks = array();
    
    // Check 1: Plugin Active
    if (is_plugin_active('workflux-pro/workflux-pro.php')) {
        $checks[] = array('status' => 'pass', 'title' => 'Plugin Status', 'message' => 'WorkFlux Pro is active');
    } else {
        $checks[] = array('status' => 'fail', 'title' => 'Plugin Status', 'message' => 'WorkFlux Pro is not active');
    }
    
    // Check 2: Classes Loaded
    $required_classes = array(
        'WorkFluxPro_Database',
        'WorkFluxPro_Roles', 
        'WorkFluxPro_Ajax',
        'WorkFluxPro_Admin',
        'WorkFluxPro_Time_Tracking',
        'WorkFluxPro_User_Management',
        'WorkFluxPro_Project_Management'
    );
    
    $missing_classes = array();
    foreach ($required_classes as $class) {
        if (!class_exists($class)) {
            $missing_classes[] = $class;
        }
    }
    
    if (empty($missing_classes)) {
        $checks[] = array('status' => 'pass', 'title' => 'Classes Loaded', 'message' => 'All required classes are loaded');
    } else {
        $checks[] = array('status' => 'fail', 'title' => 'Classes Loaded', 'message' => 'Missing classes: ' . implode(', ', $missing_classes));
    }
    
    // Check 3: Database Tables
    global $wpdb;
    $required_tables = array(
        $wpdb->prefix . 'wfp_employees',
        $wpdb->prefix . 'wfp_projects',
        $wpdb->prefix . 'wfp_tasks',
        $wpdb->prefix . 'wfp_time_tracking',
        $wpdb->prefix . 'wfp_leave_requests',
        $wpdb->prefix . 'wfp_external_duty'
    );
    
    $missing_tables = array();
    foreach ($required_tables as $table) {
        $result = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if ($result !== $table) {
            $missing_tables[] = $table;
        }
    }
    
    if (empty($missing_tables)) {
        $checks[] = array('status' => 'pass', 'title' => 'Database Tables', 'message' => 'All required tables exist');
    } else {
        $checks[] = array('status' => 'fail', 'title' => 'Database Tables', 'message' => 'Missing tables: ' . implode(', ', $missing_tables));
    }
    
    // Check 4: Custom Roles
    $wfp_roles = array('wfp_super_admin', 'wfp_managing_head', 'wfp_hr_manager', 'wfp_project_admin', 'wfp_employee');
    $missing_roles = array();
    
    foreach ($wfp_roles as $role) {
        if (!get_role($role)) {
            $missing_roles[] = $role;
        }
    }
    
    if (empty($missing_roles)) {
        $checks[] = array('status' => 'pass', 'title' => 'Custom Roles', 'message' => 'All WorkFlux roles are registered');
    } else {
        $checks[] = array('status' => 'fail', 'title' => 'Custom Roles', 'message' => 'Missing roles: ' . implode(', ', $missing_roles));
    }
    
    // Check 5: AJAX Actions
    global $wp_filter;
    $ajax_actions = array(
        'wp_ajax_wfp_create_employee',
        'wp_ajax_wfp_create_project',
        'wp_ajax_wfp_clock_in',
        'wp_ajax_wfp_clock_out',
        'wp_ajax_wfp_get_dashboard_data'
    );
    
    $missing_actions = array();
    foreach ($ajax_actions as $action) {
        if (!isset($wp_filter[$action]) || empty($wp_filter[$action]->callbacks)) {
            $missing_actions[] = $action;
        }
    }
    
    if (empty($missing_actions)) {
        $checks[] = array('status' => 'pass', 'title' => 'AJAX Actions', 'message' => 'All AJAX actions are registered');
    } else {
        $checks[] = array('status' => 'fail', 'title' => 'AJAX Actions', 'message' => 'Missing actions: ' . implode(', ', $missing_actions));
    }
    
    // Check 6: Current User Role
    $current_user_role = WorkFluxPro_Roles::get_user_workflux_role();
    if ($current_user_role) {
        $checks[] = array('status' => 'pass', 'title' => 'User Role', 'message' => "Current user has WorkFlux role: $current_user_role");
    } else {
        $checks[] = array('status' => 'warn', 'title' => 'User Role', 'message' => 'Current user does not have a WorkFlux role assigned');
    }
    
    // Check 7: Sample Data
    $employee_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wfp_employees");
    $project_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wfp_projects");
    
    if ($employee_count > 0 || $project_count > 0) {
        $checks[] = array('status' => 'pass', 'title' => 'Sample Data', 'message' => "Database contains $employee_count employees and $project_count projects");
    } else {
        $checks[] = array('status' => 'warn', 'title' => 'Sample Data', 'message' => 'No sample data found. Consider importing test data for demonstration.');
    }
    
    // Display results
    foreach ($checks as $check) {
        $class = 'check-' . $check['status'];
        $status = strtoupper($check['status']);
        echo "<div class='check-item $class'>";
        echo "<span class='status'>[$status]</span> <strong>{$check['title']}:</strong> {$check['message']}";
        echo "</div>";
    }
    
    // Count results
    $pass_count = count(array_filter($checks, function($c) { return $c['status'] === 'pass'; }));
    $fail_count = count(array_filter($checks, function($c) { return $c['status'] === 'fail'; }));
    $warn_count = count(array_filter($checks, function($c) { return $c['status'] === 'warn'; }));
    
    echo "<h2>Summary</h2>";
    echo "<p><strong>Passed:</strong> $pass_count | <strong>Failed:</strong> $fail_count | <strong>Warnings:</strong> $warn_count</p>";
    
    if ($fail_count > 0) {
        echo "<div class='check-item check-fail'>";
        echo "<strong>Action Required:</strong> Some checks failed. Please resolve the issues above before using WorkFlux Pro.";
        echo "</div>";
    } elseif ($warn_count > 0) {
        echo "<div class='check-item check-warn'>";
        echo "<strong>Recommendations:</strong> All critical checks passed, but there are some recommendations to address.";
        echo "</div>";
    } else {
        echo "<div class='check-item check-pass'>";
        echo "<strong>All Good!</strong> WorkFlux Pro is properly configured and ready to use.";
        echo "</div>";
    }
    ?>
    
    <div class="action-buttons">
        <h2>Quick Actions</h2>
        
        <?php if ($fail_count > 0): ?>
        <a href="<?php echo admin_url('plugins.php?action=deactivate&plugin=workflux-pro/workflux-pro.php&_wpnonce=' . wp_create_nonce('deactivate-plugin_workflux-pro/workflux-pro.php')); ?>" 
           class="btn btn-warning">Deactivate Plugin</a>
        <a href="<?php echo admin_url('plugins.php?action=activate&plugin=workflux-pro/workflux-pro.php&_wpnonce=' . wp_create_nonce('activate-plugin_workflux-pro/workflux-pro.php')); ?>" 
           class="btn btn-success">Reactivate Plugin</a>
        <?php endif; ?>
        
        <?php if (empty($missing_tables)): ?>
        <a href="ajax-debug.php" class="btn">AJAX Debug Tool</a>
        <?php endif; ?>
        
        <a href="<?php echo admin_url('admin.php?page=workflux-pro'); ?>" class="btn">WorkFlux Dashboard</a>
        
        <?php if ($employee_count == 0): ?>
        <button class="btn btn-success" onclick="importSampleData()">Import Sample Data</button>
        <?php endif; ?>
    </div>
    
    <h2>System Information</h2>
    <div class="check-item">
        <strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?><br>
        <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?><br>
        <strong>MySQL Version:</strong> <?php echo $wpdb->db_version(); ?><br>
        <strong>WorkFlux Pro Version:</strong> <?php echo defined('WORKFLUX_PRO_VERSION') ? WORKFLUX_PRO_VERSION : 'Unknown'; ?><br>
        <strong>Current User:</strong> <?php echo wp_get_current_user()->display_name; ?> (ID: <?php echo get_current_user_id(); ?>)<br>
        <strong>WordPress Roles:</strong> <?php echo implode(', ', wp_get_current_user()->roles); ?><br>
        <strong>WorkFlux Role:</strong> <?php echo $current_user_role ?: 'None'; ?>
    </div>
    
    <script>
        function importSampleData() {
            if (confirm('This will import sample employees, projects, and test data. Continue?')) {
                // You can implement sample data import here
                alert('Sample data import functionality can be implemented here.');
            }
        }
    </script>
</body>
</html>