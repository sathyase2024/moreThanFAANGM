<?php
/**
 * WorkFlux Pro Functionality Debug Tool
 * Comprehensive diagnosis and fix for non-working core functions
 */

// WordPress environment
if (!defined('ABSPATH')) {
    require_once('../../../../wp-config.php');
}

// Only allow admin users
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

$test_results = array();
$fixes_applied = array();

// Auto-fix mode
if (isset($_GET['fix_all'])) {
    echo "<h1>🔧 Fixing All Core Functionality Issues...</h1>";
    
    // Fix 1: Recreate database tables
    if (class_exists('WorkFluxPro_Database')) {
        global $wpdb;
        
        // Drop existing tables if they exist
        $tables = array(
            $wpdb->prefix . 'wfp_employees',
            $wpdb->prefix . 'wfp_projects',
            $wpdb->prefix . 'wfp_tasks',
            $wpdb->prefix . 'wfp_time_tracking',
            $wpdb->prefix . 'wfp_leave_requests',
            $wpdb->prefix . 'wfp_external_duty',
            $wpdb->prefix . 'wfp_project_assignments',
            $wpdb->prefix . 'wfp_notifications',
            $wpdb->prefix . 'wfp_employee_settings'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Recreate all tables
        $db = new WorkFluxPro_Database();
        $db->create_tables();
        $fixes_applied[] = "✅ Recreated all database tables";
    }
    
    // Fix 2: Reset and recreate all roles
    if (class_exists('WorkFluxPro_Roles')) {
        WorkFluxPro_Roles::remove_custom_roles();
        WorkFluxPro_Roles::add_custom_roles();
        
        // Assign current user as super admin
        $user = new WP_User(get_current_user_id());
        $user->add_role('wfp_super_admin');
        $fixes_applied[] = "✅ Reset all roles and assigned Super Admin to current user";
    }
    
    // Fix 3: Re-register all AJAX actions
    if (class_exists('WorkFluxPro_Ajax')) {
        // Force re-initialization
        remove_all_actions('wp_ajax_wfp_create_employee');
        remove_all_actions('wp_ajax_wfp_create_project');
        remove_all_actions('wp_ajax_wfp_submit_leave_request');
        
        new WorkFluxPro_Ajax();
        $fixes_applied[] = "✅ Re-registered all AJAX actions";
    }
    
    // Fix 4: Clear all caches
    wp_cache_flush();
    if (function_exists('wp_cache_clear_cache')) {
        wp_cache_clear_cache();
    }
    $fixes_applied[] = "✅ Cleared all caches";
    
    // Fix 5: Verify core classes exist
    $required_classes = array(
        'WorkFluxPro_User_Management',
        'WorkFluxPro_Project_Management', 
        'WorkFluxPro_Leave_Management',
        'WorkFluxPro_Time_Tracking'
    );
    
    $missing_classes = array();
    foreach ($required_classes as $class) {
        if (!class_exists($class)) {
            $missing_classes[] = $class;
        }
    }
    
    if (empty($missing_classes)) {
        $fixes_applied[] = "✅ All required classes are loaded";
    } else {
        $fixes_applied[] = "❌ Missing classes: " . implode(', ', $missing_classes);
    }
    
    echo "<h2>Applied Fixes:</h2>";
    foreach ($fixes_applied as $fix) {
        $color = strpos($fix, '❌') !== false ? '#f8d7da' : '#d4edda';
        echo "<div style='padding: 10px; margin: 5px 0; background: $color; border-radius: 4px;'>$fix</div>";
    }
    
    echo "<div style='margin: 20px 0; padding: 15px; background: #d1ecf1; border-radius: 4px;'>";
    echo "<strong>🎯 Next Step:</strong> <a href='?test_all=1' style='color: #0c5460; font-weight: bold;'>Test All Functions</a>";
    echo "</div>";
    
    exit;
}

// Test all functionality
if (isset($_GET['test_all'])) {
    echo "<h1>🧪 Testing All Core Functionality...</h1>";
    
    // Test 1: Employee Creation
    echo "<h3>👤 Testing Employee Creation...</h3>";
    try {
        $test_data = array(
            'user_login' => 'functest_' . time(),
            'user_email' => 'functest' . time() . '@example.com',
            'user_pass' => 'TestPass123!',
            'first_name' => 'Function',
            'last_name' => 'Test',
            'display_name' => 'Function Test'
        );
        
        $user_id = wp_insert_user($test_data);
        if (is_wp_error($user_id)) {
            throw new Exception($user_id->get_error_message());
        }
        
        $employee_data = array(
            'user_id' => $user_id,
            'employee_id' => 'FUNC' . time(),
            'department' => 'Testing',
            'designation' => 'Test Employee',
            'hire_date' => date('Y-m-d'),
            'status' => 'active'
        );
        
        if (class_exists('WorkFluxPro_User_Management')) {
            $result = WorkFluxPro_User_Management::create_employee($employee_data);
            if ($result) {
                echo "<div style='padding: 10px; background: #d4edda; border-radius: 4px; margin: 5px 0;'>✅ Employee creation: SUCCESS</div>";
            } else {
                echo "<div style='padding: 10px; background: #f8d7da; border-radius: 4px; margin: 5px 0;'>❌ Employee creation: FAILED (method returned false)</div>";
            }
        } else {
            echo "<div style='padding: 10px; background: #f8d7da; border-radius: 4px; margin: 5px 0;'>❌ Employee creation: WorkFluxPro_User_Management class not found</div>";
        }
    } catch (Exception $e) {
        echo "<div style='padding: 10px; background: #f8d7da; border-radius: 4px; margin: 5px 0;'>❌ Employee creation: EXCEPTION - " . $e->getMessage() . "</div>";
    }
    
    // Test 2: Project Creation
    echo "<h3>🚀 Testing Project Creation...</h3>";
    try {
        $project_data = array(
            'name' => 'Function Test Project ' . time(),
            'description' => 'Test project for functionality verification',
            'project_code' => 'FUNC' . time(),
            'client' => 'Test Client',
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+1 month')),
            'estimated_hours' => 100,
            'priority' => 'medium',
            'created_by' => get_current_user_id(),
            'status' => 'active'
        );
        
        if (class_exists('WorkFluxPro_Project_Management')) {
            $result = WorkFluxPro_Project_Management::create_project($project_data);
            if ($result) {
                echo "<div style='padding: 10px; background: #d4edda; border-radius: 4px; margin: 5px 0;'>✅ Project creation: SUCCESS</div>";
            } else {
                echo "<div style='padding: 10px; background: #f8d7da; border-radius: 4px; margin: 5px 0;'>❌ Project creation: FAILED (method returned false)</div>";
            }
        } else {
            echo "<div style='padding: 10px; background: #f8d7da; border-radius: 4px; margin: 5px 0;'>❌ Project creation: WorkFluxPro_Project_Management class not found</div>";
        }
    } catch (Exception $e) {
        echo "<div style='padding: 10px; background: #f8d7da; border-radius: 4px; margin: 5px 0;'>❌ Project creation: EXCEPTION - " . $e->getMessage() . "</div>";
    }
    
    // Test 3: Leave Request
    echo "<h3>🏖️ Testing Leave Request...</h3>";
    try {
        if (class_exists('WorkFluxPro_Leave_Management')) {
            $leave_data = array(
                'employee_id' => get_current_user_id(),
                'leave_type' => 'annual',
                'start_date' => date('Y-m-d', strtotime('+1 day')),
                'end_date' => date('Y-m-d', strtotime('+3 days')),
                'reason' => 'Function test leave request'
            );
            
            $result = WorkFluxPro_Leave_Management::submit_request($leave_data);
            if (is_array($result) && isset($result['error'])) {
                echo "<div style='padding: 10px; background: #fff3cd; border-radius: 4px; margin: 5px 0;'>⚠️ Leave request: VALIDATION ERROR - " . $result['error'] . "</div>";
            } elseif ($result) {
                echo "<div style='padding: 10px; background: #d4edda; border-radius: 4px; margin: 5px 0;'>✅ Leave request: SUCCESS</div>";
            } else {
                echo "<div style='padding: 10px; background: #f8d7da; border-radius: 4px; margin: 5px 0;'>❌ Leave request: FAILED (method returned false)</div>";
            }
        } else {
            echo "<div style='padding: 10px; background: #f8d7da; border-radius: 4px; margin: 5px 0;'>❌ Leave request: WorkFluxPro_Leave_Management class not found</div>";
        }
    } catch (Exception $e) {
        echo "<div style='padding: 10px; background: #f8d7da; border-radius: 4px; margin: 5px 0;'>❌ Leave request: EXCEPTION - " . $e->getMessage() . "</div>";
    }
    
    // Test 4: Time Tracking
    echo "<h3>⏰ Testing Time Tracking...</h3>";
    try {
        if (class_exists('WorkFluxPro_Time_Tracking')) {
            $user_id = get_current_user_id();
            $location = 'Function Test Location';
            $ip_address = '127.0.0.1';
            
            $result = WorkFluxPro_Time_Tracking::clock_in($user_id, $location, $ip_address);
            if ($result) {
                echo "<div style='padding: 10px; background: #d4edda; border-radius: 4px; margin: 5px 0;'>✅ Time tracking (clock in): SUCCESS</div>";
            } else {
                echo "<div style='padding: 10px; background: #f8d7da; border-radius: 4px; margin: 5px 0;'>❌ Time tracking (clock in): FAILED</div>";
            }
        } else {
            echo "<div style='padding: 10px; background: #f8d7da; border-radius: 4px; margin: 5px 0;'>❌ Time tracking: WorkFluxPro_Time_Tracking class not found</div>";
        }
    } catch (Exception $e) {
        echo "<div style='padding: 10px; background: #f8d7da; border-radius: 4px; margin: 5px 0;'>❌ Time tracking: EXCEPTION - " . $e->getMessage() . "</div>";
    }
    
    // Test 5: Database Tables
    echo "<h3>🗄️ Testing Database Tables...</h3>";
    global $wpdb;
    
    $required_tables = array(
        'wfp_employees' => $wpdb->prefix . 'wfp_employees',
        'wfp_projects' => $wpdb->prefix . 'wfp_projects',
        'wfp_tasks' => $wpdb->prefix . 'wfp_tasks',
        'wfp_time_tracking' => $wpdb->prefix . 'wfp_time_tracking',
        'wfp_leave_requests' => $wpdb->prefix . 'wfp_leave_requests'
    );
    
    foreach ($required_tables as $name => $table) {
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
        if ($exists) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
            echo "<div style='padding: 10px; background: #d4edda; border-radius: 4px; margin: 5px 0;'>✅ Table $name: EXISTS ($count records)</div>";
        } else {
            echo "<div style='padding: 10px; background: #f8d7da; border-radius: 4px; margin: 5px 0;'>❌ Table $name: MISSING</div>";
        }
    }
    
    echo "<div style='margin: 20px 0; padding: 15px; background: #e7f3ff; border-radius: 4px;'>";
    echo "<strong>🎯 Testing Complete!</strong><br>";
    echo "<a href='?' style='color: #0c5460; font-weight: bold;'>← Back to Main Menu</a> | ";
    echo "<a href='" . admin_url('admin.php?page=workflux-pro') . "' style='color: #0c5460; font-weight: bold;'>Go to WorkFlux Dashboard</a>";
    echo "</div>";
    
    exit;
}

// Show diagnostics
?>
<!DOCTYPE html>
<html>
<head>
    <title>WorkFlux Pro Functionality Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .status-item { padding: 12px; margin: 8px 0; border-radius: 4px; }
        .status-pass { background: #d4edda; border-left: 4px solid #28a745; }
        .status-fail { background: #f8d7da; border-left: 4px solid #dc3545; }
        .status-warn { background: #fff3cd; border-left: 4px solid #ffc107; }
        .btn { padding: 12px 20px; margin: 8px; color: white; text-decoration: none; border-radius: 4px; display: inline-block; font-weight: bold; text-align: center; }
        .btn-danger { background: #dc3545; }
        .btn-success { background: #28a745; }
        .btn-primary { background: #007cba; }
        .btn-warning { background: #ffc107; color: #333; }
        h1, h2 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
        .critical-issue { background: #dc3545; color: white; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center; }
    </style>
</head>
<body>
    <h1>🔧 WorkFlux Pro Functionality Debug</h1>
    
    <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #ffeaa7;">
        <strong>🚨 Issue Reported:</strong> "UI is good but functionality not working - create projects, employees, etc."<br>
        <strong>🎯 This tool will:</strong> Diagnose and fix all core functionality issues.
    </div>
    
    <h2>📊 Quick System Check</h2>
    
    <table>
        <tr>
            <th>Component</th>
            <th>Status</th>
            <th>Issue</th>
        </tr>
        <tr>
            <td><strong>WordPress</strong></td>
            <td>✅ Version <?php echo get_bloginfo('version'); ?></td>
            <td>-</td>
        </tr>
        <tr>
            <td><strong>PHP</strong></td>
            <td>✅ Version <?php echo PHP_VERSION; ?></td>
            <td>-</td>
        </tr>
        <tr>
            <td><strong>WorkFlux Classes</strong></td>
            <td><?php 
                $classes = array('WorkFluxPro_Ajax', 'WorkFluxPro_User_Management', 'WorkFluxPro_Project_Management');
                $loaded = 0;
                foreach ($classes as $class) {
                    if (class_exists($class)) $loaded++;
                }
                if ($loaded === count($classes)) {
                    echo "✅ All loaded ($loaded/" . count($classes) . ")";
                } else {
                    echo "❌ Some missing ($loaded/" . count($classes) . ")";
                }
            ?></td>
            <td><?php echo $loaded !== count($classes) ? 'Classes not loading properly' : '-'; ?></td>
        </tr>
        <tr>
            <td><strong>Database Tables</strong></td>
            <td><?php 
                global $wpdb;
                $tables = array('wfp_employees', 'wfp_projects', 'wfp_leave_requests');
                $exists = 0;
                foreach ($tables as $table) {
                    if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}$table'")) $exists++;
                }
                if ($exists === count($tables)) {
                    echo "✅ All exist ($exists/" . count($tables) . ")";
                } else {
                    echo "❌ Some missing ($exists/" . count($tables) . ")";
                }
            ?></td>
            <td><?php echo $exists !== count($tables) ? 'Database tables missing or corrupted' : '-'; ?></td>
        </tr>
        <tr>
            <td><strong>AJAX Actions</strong></td>
            <td><?php 
                global $wp_filter;
                $actions = array('wp_ajax_wfp_create_employee', 'wp_ajax_wfp_create_project');
                $registered = 0;
                foreach ($actions as $action) {
                    if (isset($wp_filter[$action])) $registered++;
                }
                if ($registered === count($actions)) {
                    echo "✅ All registered ($registered/" . count($actions) . ")";
                } else {
                    echo "❌ Some missing ($registered/" . count($actions) . ")";
                }
            ?></td>
            <td><?php echo $registered !== count($actions) ? 'AJAX handlers not registered' : '-'; ?></td>
        </tr>
        <tr>
            <td><strong>User Permissions</strong></td>
            <td><?php 
                $can_manage = WorkFluxPro_Roles::user_can('wfp_manage_employees') && WorkFluxPro_Roles::user_can('wfp_manage_projects');
                echo $can_manage ? '✅ Has permissions' : '❌ Missing permissions';
            ?></td>
            <td><?php echo !$can_manage ? 'User lacks required capabilities' : '-'; ?></td>
        </tr>
    </table>
    
    <?php
    // Determine if there are critical issues
    $critical_issues = array();
    
    if ($loaded !== count($classes)) {
        $critical_issues[] = "Core classes not loading";
    }
    if ($exists !== count($tables)) {
        $critical_issues[] = "Database tables missing";
    }
    if ($registered !== count($actions)) {
        $critical_issues[] = "AJAX actions not registered";
    }
    if (!WorkFluxPro_Roles::user_can('wfp_manage_employees')) {
        $critical_issues[] = "Missing user permissions";
    }
    
    if (!empty($critical_issues)):
    ?>
        <div class="critical-issue">
            <h2>🚨 CRITICAL ISSUES DETECTED</h2>
            <p>The following issues are preventing WorkFlux Pro from functioning:</p>
            <ul style="text-align: left; display: inline-block;">
                <?php foreach ($critical_issues as $issue): ?>
                    <li><?php echo esc_html($issue); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="?fix_all=1" class="btn btn-danger" style="font-size: 18px; padding: 15px 30px;">
                🔧 FIX ALL ISSUES NOW
            </a>
        </div>
        
    <?php else: ?>
        <div class="status-item status-pass">
            <strong>✅ No Critical Issues Detected</strong><br>
            System appears configured correctly. Test individual functions to identify specific problems.
        </div>
    <?php endif; ?>
    
    <h2>🧪 Comprehensive Testing</h2>
    <div style="text-align: center; margin: 20px 0;">
        <a href="?test_all=1" class="btn btn-primary">🧪 TEST ALL FUNCTIONALITY</a>
    </div>
    
    <h2>🛠️ Manual Debugging Steps</h2>
    <div style="background: #f8f9fa; padding: 20px; border-radius: 5px;">
        <strong>If issues persist after auto-fix:</strong>
        <ol>
            <li><strong>Check Error Logs:</strong> Look in <code>/wp-content/debug.log</code> for PHP errors</li>
            <li><strong>Browser Console:</strong> Check for JavaScript errors in browser developer tools</li>
            <li><strong>Database Access:</strong> Verify WordPress can write to database</li>
            <li><strong>File Permissions:</strong> Check file/folder permissions are correct</li>
            <li><strong>Plugin Conflicts:</strong> Temporarily deactivate other plugins</li>
            <li><strong>Theme Issues:</strong> Test with default WordPress theme</li>
        </ol>
    </div>
    
    <h2>🔗 Additional Tools</h2>
    <div style="text-align: center;">
        <a href="quick-security-fix.php" class="btn btn-warning">🔒 Security Fix</a>
        <a href="ajax-debug.php" class="btn btn-primary">🔧 AJAX Testing</a>
        <a href="activation-check.php" class="btn btn-success">✅ Full System Check</a>
        <a href="<?php echo admin_url('admin.php?page=workflux-pro'); ?>" class="btn btn-primary">🏠 WorkFlux Dashboard</a>
    </div>
    
    <h2>📞 Emergency Recovery</h2>
    <div style="background: #e7f3ff; padding: 15px; border-radius: 5px;">
        <strong>If nothing works:</strong><br>
        <ol>
            <li>Deactivate WorkFlux Pro plugin</li>
            <li>Delete and re-upload plugin files</li>
            <li>Reactivate plugin</li>
            <li>Run this diagnostic tool again</li>
        </ol>
    </div>
    
</body>
</html>