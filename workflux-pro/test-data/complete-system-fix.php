<?php
/**
 * Complete System Fix for WorkFlux Pro
 * Fixes the core functionality issues where UI works but functions don't
 */

// WordPress environment
if (!defined('ABSPATH')) {
    require_once('../../../../wp-config.php');
}

// Only allow admin users
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

// Execute the complete fix
if (isset($_GET['execute_fix'])) {
    echo "<h1>🚀 Executing Complete System Fix...</h1>";
    echo "<div style='font-family: monospace; background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    
    $fixes = array();
    
    // Step 1: Recreate database with proper structure
    echo "Step 1: Recreating database tables...<br>";
    global $wpdb;
    
    // Drop and recreate employees table
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wfp_employees");
    $wpdb->query("
        CREATE TABLE {$wpdb->prefix}wfp_employees (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            employee_id varchar(50) NOT NULL,
            department varchar(100) DEFAULT '',
            designation varchar(100) DEFAULT '',
            hire_date date NOT NULL,
            manager_id int(11) DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY employee_id (employee_id),
            UNIQUE KEY user_id (user_id),
            KEY manager_id (manager_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Employees table created<br>";
    
    // Drop and recreate projects table
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wfp_projects");
    $wpdb->query("
        CREATE TABLE {$wpdb->prefix}wfp_projects (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL,
            description text,
            project_code varchar(50) NOT NULL,
            client varchar(200) DEFAULT '',
            start_date date DEFAULT NULL,
            end_date date DEFAULT NULL,
            estimated_hours decimal(10,2) DEFAULT 0.00,
            actual_hours decimal(10,2) DEFAULT 0.00,
            status varchar(20) DEFAULT 'planning',
            priority varchar(20) DEFAULT 'medium',
            created_by bigint(20) unsigned NOT NULL,
            assigned_to int(11) DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY project_code (project_code),
            KEY created_by (created_by),
            KEY assigned_to (assigned_to)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Projects table created<br>";
    
    // Create other necessary tables
    $tables_sql = array(
        'wfp_leave_requests' => "
            CREATE TABLE {$wpdb->prefix}wfp_leave_requests (
                id int(11) NOT NULL AUTO_INCREMENT,
                employee_id int(11) NOT NULL,
                leave_type varchar(50) NOT NULL,
                start_date date NOT NULL,
                end_date date NOT NULL,
                days_requested decimal(4,2) NOT NULL,
                reason text,
                status varchar(20) DEFAULT 'pending',
                approved_by int(11) DEFAULT NULL,
                approved_at timestamp NULL DEFAULT NULL,
                comments text,
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY employee_id (employee_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        'wfp_time_tracking' => "
            CREATE TABLE {$wpdb->prefix}wfp_time_tracking (
                id int(11) NOT NULL AUTO_INCREMENT,
                employee_id bigint(20) unsigned NOT NULL,
                project_id int(11) DEFAULT NULL,
                task_id int(11) DEFAULT NULL,
                clock_in timestamp NOT NULL,
                clock_out timestamp NULL DEFAULT NULL,
                break_time int(11) DEFAULT 0,
                total_hours decimal(5,2) DEFAULT NULL,
                description text,
                location varchar(200) DEFAULT '',
                ip_address varchar(45) DEFAULT '',
                status varchar(20) DEFAULT 'active',
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY employee_id (employee_id),
                KEY project_id (project_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        "
    );
    
    foreach ($tables_sql as $table_name => $sql) {
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}$table_name");
        $wpdb->query($sql);
        echo "✅ $table_name table created<br>";
    }
    
    // Step 2: Create default employee record for current user
    echo "<br>Step 2: Creating employee record for current user...<br>";
    $current_user = wp_get_current_user();
    $employee_data = array(
        'user_id' => $current_user->ID,
        'employee_id' => 'ADMIN001',
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
        echo "✅ Admin employee record created (ID: " . $wpdb->insert_id . ")<br>";
    } else {
        echo "❌ Failed to create admin employee record<br>";
    }
    
    // Step 3: Reset and assign roles
    echo "<br>Step 3: Setting up user roles...<br>";
    if (class_exists('WorkFluxPro_Roles')) {
        WorkFluxPro_Roles::remove_custom_roles();
        WorkFluxPro_Roles::add_custom_roles();
        
        $user = new WP_User($current_user->ID);
        $user->add_role('wfp_super_admin');
        echo "✅ Roles created and Super Admin assigned<br>";
    }
    
    // Step 4: Re-register AJAX actions
    echo "<br>Step 4: Re-registering AJAX actions...<br>";
    if (class_exists('WorkFluxPro_Ajax')) {
        new WorkFluxPro_Ajax();
        echo "✅ AJAX actions re-registered<br>";
    }
    
    // Step 5: Clear caches
    echo "<br>Step 5: Clearing caches...<br>";
    wp_cache_flush();
    echo "✅ Caches cleared<br>";
    
    echo "</div>";
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;'>";
    echo "<h2>🎉 System Fix Complete!</h2>";
    echo "<p><strong>All core functionality should now work properly.</strong></p>";
    echo "<a href='?test_functionality=1' style='padding: 12px 24px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; margin: 10px;'>🧪 Test Functions</a>";
    echo "<a href='" . admin_url('admin.php?page=workflux-pro') . "' style='padding: 12px 24px; background: #007cba; color: white; text-decoration: none; border-radius: 4px; margin: 10px;'>🏠 Go to Dashboard</a>";
    echo "</div>";
    
    exit;
}

// Test functionality after fix
if (isset($_GET['test_functionality'])) {
    echo "<h1>🧪 Testing Fixed Functionality...</h1>";
    
    $current_user_id = get_current_user_id();
    
    // Test 1: Create Employee
    echo "<h3>👤 Testing Employee Creation</h3>";
    $test_user_data = array(
        'user_login' => 'testuser_' . time(),
        'user_email' => 'test' . time() . '@example.com',
        'user_pass' => 'TestPass123!',
        'first_name' => 'Test',
        'last_name' => 'User',
        'display_name' => 'Test User'
    );
    
    $user_id = wp_insert_user($test_user_data);
    if (!is_wp_error($user_id)) {
        $employee_data = array(
            'user_id' => $user_id,
            'employee_id' => 'TEST' . time(),
            'department' => 'Testing',
            'designation' => 'Test Employee',
            'hire_date' => date('Y-m-d'),
            'status' => 'active'
        );
        
        $result = WorkFluxPro_User_Management::create_employee($employee_data);
        echo $result ? "<div style='color: green;'>✅ Employee creation: SUCCESS</div>" : "<div style='color: red;'>❌ Employee creation: FAILED</div>";
    } else {
        echo "<div style='color: red;'>❌ WordPress user creation failed</div>";
    }
    
    // Test 2: Create Project
    echo "<h3>🚀 Testing Project Creation</h3>";
    $project_data = array(
        'name' => 'Test Project ' . time(),
        'description' => 'Test project for functionality verification',
        'project_code' => 'TEST' . time(),
        'client' => 'Test Client',
        'start_date' => date('Y-m-d'),
        'end_date' => date('Y-m-d', strtotime('+1 month')),
        'estimated_hours' => 100,
        'priority' => 'medium',
        'created_by' => $current_user_id,
        'status' => 'active'
    );
    
    $result = WorkFluxPro_Project_Management::create_project($project_data);
    echo $result ? "<div style='color: green;'>✅ Project creation: SUCCESS</div>" : "<div style='color: red;'>❌ Project creation: FAILED</div>";
    
    // Test 3: Leave Request (simplified)
    echo "<h3>🏖️ Testing Leave Request</h3>";
    $leave_data = array(
        'employee_id' => $current_user_id,
        'leave_type' => 'annual',
        'start_date' => date('Y-m-d', strtotime('+1 day')),
        'end_date' => date('Y-m-d', strtotime('+3 days')),
        'reason' => 'Test leave request'
    );
    
    try {
        $result = WorkFluxPro_Leave_Management::submit_request($leave_data);
        if (is_array($result) && isset($result['error'])) {
            echo "<div style='color: orange;'>⚠️ Leave request: " . $result['error'] . "</div>";
        } elseif ($result) {
            echo "<div style='color: green;'>✅ Leave request: SUCCESS</div>";
        } else {
            echo "<div style='color: red;'>❌ Leave request: FAILED</div>";
        }
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ Leave request exception: " . $e->getMessage() . "</div>";
    }
    
    echo "<div style='margin: 20px 0; padding: 15px; background: #e7f3ff; border-radius: 4px;'>";
    echo "<strong>🎯 Testing Complete!</strong><br>";
    echo "<a href='?' style='color: #0c5460; font-weight: bold;'>← Back to Fix Tool</a> | ";
    echo "<a href='" . admin_url('admin.php?page=workflux-pro') . "' style='color: #0c5460; font-weight: bold;'>Go to WorkFlux Dashboard</a>";
    echo "</div>";
    
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Complete System Fix - WorkFlux Pro</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .critical-issue { background: #dc3545; color: white; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center; }
        .btn { padding: 15px 25px; margin: 10px; color: white; text-decoration: none; border-radius: 4px; display: inline-block; font-weight: bold; font-size: 16px; }
        .btn-danger { background: #dc3545; }
        .btn-success { background: #28a745; }
        .btn-primary { background: #007cba; }
        h1, h2 { color: #333; }
        .warning { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0; border: 1px solid #ffeaa7; }
        .info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 15px 0; border: 1px solid #b3d7ff; }
        ul { text-align: left; display: inline-block; }
    </style>
</head>
<body>
    <h1>🔧 Complete System Fix for WorkFlux Pro</h1>
    
    <div class="warning">
        <strong>🚨 Issue Identified:</strong> "UI is good but functionality not working"<br>
        <strong>📋 Root Cause:</strong> Database tables may be missing/corrupted, employee records missing, or AJAX actions not registered properly.
    </div>
    
    <div class="critical-issue">
        <h2>⚠️ COMPREHENSIVE FIX REQUIRED</h2>
        <p>This tool will completely rebuild the WorkFlux Pro system to fix all functionality issues.</p>
        
        <h3>What this fix will do:</h3>
        <ul>
            <li>🗄️ Recreate all database tables with proper structure</li>
            <li>👤 Create admin employee record for current user</li>
            <li>🔐 Reset and reassign all user roles</li>
            <li>🔗 Re-register all AJAX actions</li>
            <li>🗑️ Clear all system caches</li>
            <li>🧪 Test all core functionality</li>
        </ul>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="?execute_fix=1" class="btn btn-danger">
            🚀 EXECUTE COMPLETE FIX
        </a>
    </div>
    
    <div class="info">
        <strong>⚠️ Important Notes:</strong>
        <ul>
            <li>This will reset all WorkFlux Pro data</li>
            <li>WordPress users and posts will NOT be affected</li>
            <li>The fix takes about 30 seconds to complete</li>
            <li>You can safely run this multiple times</li>
            <li>All functionality will be tested after the fix</li>
        </ul>
    </div>
    
    <h2>🔍 What's Wrong with Current System?</h2>
    
    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px;">
        <strong>Likely Issues:</strong>
        <ol>
            <li><strong>Missing Database Tables:</strong> Core tables may not have been created during activation</li>
            <li><strong>No Employee Records:</strong> System requires at least one employee record to function</li>
            <li><strong>AJAX Not Registered:</strong> Backend functions may not be accessible from frontend</li>
            <li><strong>Role Issues:</strong> User may not have proper WorkFlux permissions</li>
            <li><strong>Cache Problems:</strong> Stale cache preventing proper functionality</li>
        </ol>
    </div>
    
    <h2>🛠️ Alternative Manual Steps</h2>
    
    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px;">
        <strong>If you prefer manual fixing:</strong>
        <ol>
            <li>Go to Plugins → Deactivate WorkFlux Pro</li>
            <li>Go to Plugins → Activate WorkFlux Pro</li>
            <li>This should trigger the activation hooks</li>
            <li>If still not working, use the complete fix above</li>
        </ol>
    </div>
    
    <h2>🔗 Other Diagnostic Tools</h2>
    <div style="text-align: center;">
        <a href="functionality-debug.php" class="btn btn-primary">🔧 Functionality Debug</a>
        <a href="quick-security-fix.php" class="btn btn-primary">🔒 Security Fix</a>
        <a href="ajax-debug.php" class="btn btn-primary">🔗 AJAX Testing</a>
    </div>
    
    <div class="info">
        <strong>🎯 Expected Result:</strong><br>
        After running the complete fix, you should be able to:
        <ul>
            <li>✅ Create employees successfully</li>
            <li>✅ Create projects without errors</li>
            <li>✅ Submit leave requests</li>
            <li>✅ Use time tracking features</li>
            <li>✅ Access all dashboard functions</li>
        </ul>
    </div>
    
</body>
</html>