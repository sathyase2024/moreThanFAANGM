<?php
/**
 * Quick Security Fix Tool
 * Immediate diagnosis and resolution of security check failures
 */

// WordPress environment
if (!defined('ABSPATH')) {
    require_once('../../../../wp-config.php');
}

// Only allow admin users
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

$fixes_applied = array();
$current_user_id = get_current_user_id();

// Auto-fix mode
if (isset($_GET['auto_fix'])) {
    echo "<h1>🔧 Applying Quick Security Fixes...</h1>";
    
    // Fix 1: Refresh all roles and capabilities
    if (class_exists('WorkFluxPro_Roles')) {
        try {
            WorkFluxPro_Roles::refresh_roles();
            $fixes_applied[] = "✅ Refreshed WorkFlux roles and capabilities";
        } catch (Exception $e) {
            $fixes_applied[] = "❌ Failed to refresh roles: " . $e->getMessage();
        }
    }
    
    // Fix 2: Ensure current user has Super Admin role
    $current_workflux_role = WorkFluxPro_Roles::get_user_workflux_role($current_user_id);
    if (!$current_workflux_role || $current_workflux_role !== 'wfp_super_admin') {
        $user = new WP_User($current_user_id);
        $user->add_role('wfp_super_admin');
        $fixes_applied[] = "✅ Added Super Admin role to current user";
    }
    
    // Fix 3: Force AJAX re-registration
    if (class_exists('WorkFluxPro_Ajax')) {
        new WorkFluxPro_Ajax();
        $fixes_applied[] = "✅ Re-registered all AJAX actions";
    }
    
    // Fix 4: Clear all caches
    wp_cache_flush();
    if (function_exists('wp_cache_clear_cache')) {
        wp_cache_clear_cache();
    }
    $fixes_applied[] = "✅ Cleared WordPress caches";
    
    // Fix 5: Verify database tables
    global $wpdb;
    $tables_needed = array(
        $wpdb->prefix . 'wfp_employees',
        $wpdb->prefix . 'wfp_projects', 
        $wpdb->prefix . 'wfp_leave_requests'
    );
    
    $missing_tables = array();
    foreach ($tables_needed as $table) {
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            $missing_tables[] = $table;
        }
    }
    
    if (!empty($missing_tables)) {
        if (class_exists('WorkFluxPro_Database')) {
            $db = new WorkFluxPro_Database();
            $db->create_tables();
            $fixes_applied[] = "✅ Recreated missing database tables";
        }
    } else {
        $fixes_applied[] = "✅ All database tables verified";
    }
    
    echo "<h2>Applied Fixes:</h2>";
    foreach ($fixes_applied as $fix) {
        echo "<div style='padding: 10px; margin: 5px 0; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;'>$fix</div>";
    }
    
    echo "<div style='margin: 20px 0; padding: 15px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px;'>";
    echo "<strong>✅ Fixes Complete!</strong><br>";
    echo "Try your operation again. If it still fails, <a href='?' style='color: #0c5460;'>run diagnostics</a> to see detailed status.";
    echo "</div>";
    
    exit;
}

// Test a specific action
if (isset($_POST['test_action'])) {
    $action = sanitize_text_field($_POST['test_action']);
    
    echo "<h2>🧪 Testing: $action</h2>";
    
    // Set up test data
    $_POST['nonce'] = wp_create_nonce('workflux_pro_nonce');
    
    switch ($action) {
        case 'create_employee':
            $_POST = array_merge($_POST, array(
                'user_login' => 'test_emp_' . time(),
                'user_email' => 'test' . time() . '@example.com',
                'user_pass' => 'TempPass123!',
                'first_name' => 'Test',
                'last_name' => 'Employee',
                'display_name' => 'Test Employee',
                'workflux_role' => 'wfp_employee',
                'employee_id' => 'TEST' . time(),
                'department' => 'Testing',
                'designation' => 'Test Employee'
            ));
            break;
            
        case 'create_project':
            $_POST = array_merge($_POST, array(
                'name' => 'Test Project ' . time(),
                'description' => 'Test project description',
                'project_code' => 'TEST' . time(),
                'client' => 'Test Client',
                'start_date' => date('Y-m-d'),
                'end_date' => date('Y-m-d', strtotime('+1 month')),
                'estimated_hours' => 100,
                'priority' => 'medium'
            ));
            break;
            
        case 'submit_leave_request':
            $_POST = array_merge($_POST, array(
                'leave_type' => 'annual',
                'start_date' => date('Y-m-d', strtotime('+1 day')),
                'end_date' => date('Y-m-d', strtotime('+3 days')),
                'reason' => 'Test leave request'
            ));
            break;
    }
    
    ob_start();
    try {
        switch ($action) {
            case 'create_employee':
                WorkFluxPro_Ajax::create_employee();
                break;
            case 'create_project':
                WorkFluxPro_Ajax::create_project();
                break;
            case 'submit_leave_request':
                WorkFluxPro_Ajax::submit_leave_request();
                break;
            case 'clock_in':
                $_POST['location'] = 'Test Location';
                WorkFluxPro_Ajax::clock_in();
                break;
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage();
    }
    $result = ob_get_clean();
    
    if (!empty($result)) {
        $response = json_decode($result, true);
        if ($response && isset($response['success'])) {
            if ($response['success']) {
                echo "<div style='padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; color: #155724; border-radius: 5px;'>";
                echo "<strong>✅ SUCCESS!</strong> " . ($response['message'] ?? 'Operation completed successfully');
                echo "</div>";
            } else {
                echo "<div style='padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 5px;'>";
                echo "<strong>❌ FAILED:</strong> " . ($response['message'] ?? 'Unknown error');
                echo "</div>";
            }
        } else {
            echo "<div style='padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; border-radius: 5px;'>";
            echo "<strong>⚠️ UNEXPECTED RESPONSE:</strong> " . esc_html($result);
            echo "</div>";
        }
    } else {
        echo "<div style='padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 5px;'>";
        echo "<strong>❌ NO RESPONSE:</strong> The function did not return any output.";
        echo "</div>";
    }
    
    echo "<div style='margin: 20px 0;'>";
    echo "<a href='?' style='padding: 10px 15px; background: #007cba; color: white; text-decoration: none; border-radius: 4px;'>← Back to Diagnostics</a>";
    echo "</div>";
    
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Quick Security Fix Tool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .status-item { padding: 12px; margin: 8px 0; border-radius: 4px; }
        .status-pass { background: #d4edda; border-left: 4px solid #28a745; }
        .status-fail { background: #f8d7da; border-left: 4px solid #dc3545; }
        .status-warn { background: #fff3cd; border-left: 4px solid #ffc107; }
        .btn { padding: 10px 15px; margin: 5px; color: white; text-decoration: none; border-radius: 4px; display: inline-block; font-weight: bold; }
        .btn-danger { background: #dc3545; }
        .btn-success { background: #28a745; }
        .btn-primary { background: #007cba; }
        .btn-warning { background: #ffc107; color: #333; }
        h1, h2 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
    </style>
</head>
<body>
    <h1>🔒 Quick Security Fix Tool</h1>
    
    <div style="background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <strong>🎯 Purpose:</strong> Instant diagnosis and fix for "Security check failed" errors in WorkFlux Pro.
    </div>
    
    <h2>📊 Current System Status</h2>
    
    <table>
        <tr>
            <th>Component</th>
            <th>Status</th>
            <th>Details</th>
        </tr>
        <tr>
            <td><strong>Current User</strong></td>
            <td><?php echo wp_get_current_user()->display_name; ?></td>
            <td>WordPress ID: <?php echo get_current_user_id(); ?></td>
        </tr>
        <tr>
            <td><strong>WordPress Roles</strong></td>
            <td><?php echo implode(', ', wp_get_current_user()->roles); ?></td>
            <td>Built-in roles</td>
        </tr>
        <tr>
            <td><strong>WorkFlux Role</strong></td>
            <td><?php 
                $wf_role = WorkFluxPro_Roles::get_user_workflux_role();
                if ($wf_role) {
                    echo "<span style='color: #28a745; font-weight: bold;'>$wf_role</span>";
                } else {
                    echo "<span style='color: #dc3545; font-weight: bold;'>❌ None</span>";
                }
            ?></td>
            <td>Custom WorkFlux role</td>
        </tr>
        <tr>
            <td><strong>Can Manage Employees</strong></td>
            <td><?php 
                $can_manage_emp = WorkFluxPro_Roles::user_can('wfp_manage_employees');
                echo $can_manage_emp ? '<span style="color: #28a745;">✅ Yes</span>' : '<span style="color: #dc3545;">❌ No</span>';
            ?></td>
            <td>wfp_manage_employees capability</td>
        </tr>
        <tr>
            <td><strong>Can Manage Projects</strong></td>
            <td><?php 
                $can_manage_proj = WorkFluxPro_Roles::user_can('wfp_manage_projects');
                echo $can_manage_proj ? '<span style="color: #28a745;">✅ Yes</span>' : '<span style="color: #dc3545;">❌ No</span>';
            ?></td>
            <td>wfp_manage_projects capability</td>
        </tr>
        <tr>
            <td><strong>Nonce Generation</strong></td>
            <td><?php 
                $nonce = wp_create_nonce('workflux_pro_nonce');
                echo $nonce ? '<span style="color: #28a745;">✅ Working</span>' : '<span style="color: #dc3545;">❌ Failed</span>';
            ?></td>
            <td><?php echo $nonce ? substr($nonce, 0, 10) . '...' : 'Failed to generate'; ?></td>
        </tr>
    </table>
    
    <?php
    $issues = array();
    if (!WorkFluxPro_Roles::get_user_workflux_role()) {
        $issues[] = "No WorkFlux role assigned";
    }
    if (!WorkFluxPro_Roles::user_can('wfp_manage_employees')) {
        $issues[] = "Missing employee management permission";
    }
    if (!WorkFluxPro_Roles::user_can('wfp_manage_projects')) {
        $issues[] = "Missing project management permission";
    }
    
    if (!empty($issues)):
    ?>
        <div class="status-item status-fail">
            <strong>❌ Issues Detected:</strong>
            <ul style="margin: 10px 0;">
                <?php foreach ($issues as $issue): ?>
                    <li><?php echo esc_html($issue); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="?auto_fix=1" class="btn btn-danger">🔧 AUTO-FIX ALL ISSUES</a>
        </div>
        
    <?php else: ?>
        <div class="status-item status-pass">
            <strong>✅ All Security Checks Passed!</strong> The system appears to be configured correctly.
        </div>
    <?php endif; ?>
    
    <h2>🧪 Test Specific Functions</h2>
    <p>Test individual functions to identify which one is failing:</p>
    
    <div style="text-align: center; margin: 20px 0;">
        <form method="post" style="display: inline;">
            <button type="submit" name="test_action" value="create_employee" class="btn btn-primary">Test Employee Creation</button>
        </form>
        
        <form method="post" style="display: inline;">
            <button type="submit" name="test_action" value="create_project" class="btn btn-primary">Test Project Creation</button>
        </form>
        
        <form method="post" style="display: inline;">
            <button type="submit" name="test_action" value="submit_leave_request" class="btn btn-primary">Test Leave Submission</button>
        </form>
        
        <form method="post" style="display: inline;">
            <button type="submit" name="test_action" value="clock_in" class="btn btn-primary">Test Clock In</button>
        </form>
    </div>
    
    <h2>🔗 Additional Tools</h2>
    <div style="text-align: center;">
        <a href="employee-security-debug.php" class="btn btn-warning">Employee Creation Debug</a>
        <a href="ajax-debug.php" class="btn btn-primary">AJAX Debug Tool</a>
        <a href="activation-check.php" class="btn btn-success">Full System Check</a>
        <a href="<?php echo admin_url('admin.php?page=workflux-pro'); ?>" class="btn btn-primary">WorkFlux Dashboard</a>
    </div>
    
    <h2>📝 Quick Manual Fixes</h2>
    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px;">
        <strong>If auto-fix doesn't work, try these manual steps:</strong>
        <ol>
            <li><strong>Refresh Browser:</strong> Clear cache and refresh the page</li>
            <li><strong>Check User Role:</strong> Ensure you have Super Admin or appropriate WorkFlux role</li>
            <li><strong>Verify Nonce:</strong> Make sure the security token is being generated</li>
            <li><strong>Check Console:</strong> Look for JavaScript errors in browser console</li>
            <li><strong>Test Different Action:</strong> Try a different function to isolate the issue</li>
        </ol>
    </div>
    
</body>
</html>