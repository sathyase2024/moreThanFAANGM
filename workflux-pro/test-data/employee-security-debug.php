<?php
/**
 * Quick Employee Creation Security Debug
 * Simple script to identify and fix employee creation security issues
 */

// WordPress environment
if (!defined('ABSPATH')) {
    require_once('../../../../wp-config.php');
}

// Only allow admin users
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

// Auto-fix mode
$auto_fix = isset($_GET['auto_fix']) && $_GET['auto_fix'] === '1';
$fixes_applied = array();

if ($auto_fix) {
    echo "<h1>🔧 Auto-Fixing Employee Creation Security Issues...</h1>";
    
    // Fix 1: Refresh roles and capabilities
    if (class_exists('WorkFluxPro_Roles')) {
        WorkFluxPro_Roles::refresh_roles();
        $fixes_applied[] = "Refreshed WorkFlux roles and capabilities";
    }
    
    // Fix 2: Ensure current user has proper role
    $current_user_id = get_current_user_id();
    $current_workflux_role = WorkFluxPro_Roles::get_user_workflux_role($current_user_id);
    
    if (!$current_workflux_role) {
        $user = new WP_User($current_user_id);
        $user->add_role('wfp_super_admin');
        $fixes_applied[] = "Added Super Admin role to current user";
    }
    
    // Fix 3: Force AJAX re-registration
    if (class_exists('WorkFluxPro_Ajax')) {
        new WorkFluxPro_Ajax();
        $fixes_applied[] = "Re-registered AJAX actions";
    }
    
    // Fix 4: Clear WordPress cache
    wp_cache_flush();
    $fixes_applied[] = "Cleared WordPress cache";
    
    echo "<h2>✅ Fixes Applied:</h2>";
    foreach ($fixes_applied as $fix) {
        echo "<div style='padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; margin: 5px 0; border-radius: 4px;'>✓ $fix</div>";
    }
    
    echo "<div style='margin: 20px 0; padding: 15px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px;'>";
    echo "<strong>🎯 Next Step:</strong> <a href='?test=1' style='color: #0c5460; font-weight: bold;'>Test Employee Creation</a>";
    echo "</div>";
    
    exit;
}

// Test mode
if (isset($_GET['test']) && $_GET['test'] === '1') {
    echo "<h1>🧪 Testing Employee Creation...</h1>";
    
    // Test AJAX call directly
    $test_data = array(
        'user_login' => 'quicktest_' . time(),
        'user_email' => 'quicktest' . time() . '@example.com', 
        'user_pass' => 'TempPass123!',
        'first_name' => 'Quick',
        'last_name' => 'Test',
        'display_name' => 'Quick Test',
        'workflux_role' => 'wfp_employee',
        'employee_id' => 'QT' . time(),
        'department' => 'Testing',
        'designation' => 'Test Employee',
        'hire_date' => date('Y-m-d'),
        'manager_id' => get_current_user_id()
    );
    
    // Set up POST data
    $_POST = array_merge($_POST, $test_data);
    $_POST['nonce'] = wp_create_nonce('workflux_pro_nonce');
    
    echo "<div style='background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 4px;'>";
    echo "<strong>Test Data:</strong><br>";
    echo "Username: " . esc_html($test_data['user_login']) . "<br>";
    echo "Email: " . esc_html($test_data['user_email']) . "<br>";
    echo "Employee ID: " . esc_html($test_data['employee_id']) . "<br>";
    echo "</div>";
    
    echo "<h2>📋 Permission Check:</h2>";
    $can_manage = WorkFluxPro_Roles::user_can('wfp_manage_employees');
    if ($can_manage) {
        echo "<div style='padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; margin: 5px 0; border-radius: 4px;'>✅ User has wfp_manage_employees permission</div>";
    } else {
        echo "<div style='padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; margin: 5px 0; border-radius: 4px;'>❌ User lacks wfp_manage_employees permission</div>";
        echo "<div style='margin: 20px 0; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;'>";
        echo "<strong>⚠️ Fix Required:</strong> <a href='?auto_fix=1' style='color: #856404; font-weight: bold;'>Auto-Fix Security Issues</a>";
        echo "</div>";
        exit;
    }
    
    echo "<h2>🚀 Creating Test Employee:</h2>";
    
    ob_start();
    try {
        WorkFluxPro_Ajax::create_employee();
        $ajax_output = ob_get_clean();
        
        if (!empty($ajax_output)) {
            $response = json_decode($ajax_output, true);
            if ($response && isset($response['success'])) {
                if ($response['success']) {
                    echo "<div style='padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; margin: 10px 0; border-radius: 4px;'>";
                    echo "<strong>✅ SUCCESS!</strong> Employee created successfully!<br>";
                    if (isset($response['data']['user_id'])) {
                        echo "New User ID: " . esc_html($response['data']['user_id']);
                    }
                    echo "</div>";
                } else {
                    echo "<div style='padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; margin: 10px 0; border-radius: 4px;'>";
                    echo "<strong>❌ FAILED:</strong> " . esc_html($response['message']);
                    echo "</div>";
                }
            } else {
                echo "<div style='padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; margin: 10px 0; border-radius: 4px;'>";
                echo "<strong>❌ Invalid Response:</strong> " . esc_html($ajax_output);
                echo "</div>";
            }
        } else {
            echo "<div style='padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; margin: 10px 0; border-radius: 4px;'>";
            echo "<strong>❌ No Response:</strong> AJAX call produced no output";
            echo "</div>";
        }
    } catch (Exception $e) {
        ob_end_clean();
        echo "<div style='padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; margin: 10px 0; border-radius: 4px;'>";
        echo "<strong>❌ Exception:</strong> " . esc_html($e->getMessage());
        echo "</div>";
    }
    
    echo "<div style='margin: 20px 0; padding: 15px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px;'>";
    echo "<strong>🎯 Next Steps:</strong><br>";
    echo "• <a href='" . admin_url('admin.php?page=workflux-pro') . "'>Go to WorkFlux Dashboard</a><br>";
    echo "• <a href='" . admin_url('users.php') . "'>Check Users List</a><br>";
    echo "• <a href='?'>Run Another Test</a>";
    echo "</div>";
    
    exit;
}

// Default diagnostic mode
?>
<!DOCTYPE html>
<html>
<head>
    <title>Employee Creation Security Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .diagnostic { padding: 15px; margin: 10px 0; border-radius: 4px; }
        .pass { background: #d4edda; border: 1px solid #c3e6cb; }
        .fail { background: #f8d7da; border: 1px solid #f5c6cb; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; }
        .btn { padding: 12px 20px; margin: 10px 5px; background: #007cba; color: white; text-decoration: none; border-radius: 4px; display: inline-block; font-weight: bold; }
        .btn-success { background: #28a745; }
        .btn-warning { background: #ffc107; color: #333; }
        .btn-danger { background: #dc3545; }
        h1, h2 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
    </style>
</head>
<body>
    <h1>🔐 Employee Creation Security Diagnostic</h1>
    
    <div class="info">
        <strong>🎯 Purpose:</strong> This tool diagnoses and fixes security issues preventing employee creation in WorkFlux Pro.
    </div>
    
    <h2>📊 Current System Status</h2>
    
    <table>
        <tr>
            <th>Check</th>
            <th>Status</th>
            <th>Details</th>
        </tr>
        <tr>
            <td><strong>Current User</strong></td>
            <td><?php echo wp_get_current_user()->display_name; ?></td>
            <td>ID: <?php echo get_current_user_id(); ?></td>
        </tr>
        <tr>
            <td><strong>WordPress Roles</strong></td>
            <td><?php echo implode(', ', wp_get_current_user()->roles); ?></td>
            <td>Built-in WordPress roles</td>
        </tr>
        <tr>
            <td><strong>WorkFlux Role</strong></td>
            <td><?php 
                $wf_role = WorkFluxPro_Roles::get_user_workflux_role();
                echo $wf_role ? $wf_role : '<span style="color: #dc3545;">None assigned</span>';
            ?></td>
            <td>Custom WorkFlux Pro role</td>
        </tr>
        <tr>
            <td><strong>Employee Permission</strong></td>
            <td><?php 
                $can_manage = WorkFluxPro_Roles::user_can('wfp_manage_employees');
                echo $can_manage ? '<span style="color: #28a745;">✅ Granted</span>' : '<span style="color: #dc3545;">❌ Denied</span>';
            ?></td>
            <td>wfp_manage_employees capability</td>
        </tr>
        <tr>
            <td><strong>AJAX Registration</strong></td>
            <td><?php 
                global $wp_filter;
                $ajax_registered = isset($wp_filter['wp_ajax_wfp_create_employee']);
                echo $ajax_registered ? '<span style="color: #28a745;">✅ Registered</span>' : '<span style="color: #dc3545;">❌ Missing</span>';
            ?></td>
            <td>wp_ajax_wfp_create_employee hook</td>
        </tr>
        <tr>
            <td><strong>User Management Class</strong></td>
            <td><?php 
                $class_exists = class_exists('WorkFluxPro_User_Management');
                echo $class_exists ? '<span style="color: #28a745;">✅ Loaded</span>' : '<span style="color: #dc3545;">❌ Missing</span>';
            ?></td>
            <td>Required for employee creation</td>
        </tr>
        <tr>
            <td><strong>Database Table</strong></td>
            <td><?php 
                global $wpdb;
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}wfp_employees'");
                echo $table_exists ? '<span style="color: #28a745;">✅ Exists</span>' : '<span style="color: #dc3545;">❌ Missing</span>';
            ?></td>
            <td>wp_wfp_employees table</td>
        </tr>
    </table>
    
    <?php 
    $permission_ok = WorkFluxPro_Roles::user_can('wfp_manage_employees');
    $ajax_ok = isset($wp_filter['wp_ajax_wfp_create_employee']);
    $class_ok = class_exists('WorkFluxPro_User_Management');
    $table_ok = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}wfp_employees'");
    
    $all_ok = $permission_ok && $ajax_ok && $class_ok && $table_ok;
    ?>
    
    <?php if ($all_ok): ?>
        <div class="diagnostic pass">
            <strong>✅ All Checks Passed!</strong> Employee creation should work. Click the button below to test it.
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="?test=1" class="btn btn-success">🧪 Test Employee Creation</a>
        </div>
        
    <?php else: ?>
        <div class="diagnostic fail">
            <strong>❌ Issues Detected!</strong> Some checks failed. Employee creation will not work until these are resolved.
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="?auto_fix=1" class="btn btn-warning">🔧 Auto-Fix Issues</a>
        </div>
        
    <?php endif; ?>
    
    <h2>🛠️ Manual Actions</h2>
    
    <div class="info">
        <strong>Quick Links:</strong><br>
        • <a href="<?php echo admin_url('admin.php?page=workflux-pro'); ?>">WorkFlux Dashboard</a><br>
        • <a href="<?php echo admin_url('users.php'); ?>">WordPress Users</a><br>
        • <a href="activation-check.php">Full System Check</a><br>
        • <a href="ajax-debug.php">AJAX Debug Tool</a><br>
        • <a href="employee-creation-fix.php">Detailed Employee Fix Tool</a>
    </div>
    
    <h2>📝 Technical Details</h2>
    
    <div class="diagnostic info">
        <strong>Expected Behavior:</strong><br>
        1. User must have WorkFlux role (Super Admin, HR Manager, or Managing Head)<br>
        2. User must have 'wfp_manage_employees' capability<br>
        3. AJAX action 'wfp_create_employee' must be registered<br>
        4. Database table 'wp_wfp_employees' must exist<br>
        5. Proper nonce must be included in AJAX requests
    </div>
    
    <div class="diagnostic info">
        <strong>Common Causes:</strong><br>
        • WordPress roles cache not cleared after plugin updates<br>
        • User doesn't have proper WorkFlux role assigned<br>
        • AJAX actions not properly registered<br>
        • Database tables not created during activation<br>
        • Permission capabilities missing from role definitions
    </div>
    
</body>
</html>