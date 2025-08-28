<?php
/**
 * Employee Creation Security Fix Tool
 * Diagnoses and fixes employee creation security issues
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
$test_results = array();

// Fix 1: Ensure roles have proper capabilities
if (class_exists('WorkFluxPro_Roles')) {
    try {
        // Recreate roles with updated capabilities
        WorkFluxPro_Roles::remove_custom_roles();
        WorkFluxPro_Roles::add_custom_roles();
        $fixes_applied[] = "WorkFlux roles recreated with updated capabilities";
    } catch (Exception $e) {
        $fixes_applied[] = "Error recreating roles: " . $e->getMessage();
    }
}

// Fix 2: Ensure current user has proper role
$current_user_id = get_current_user_id();
$current_workflux_role = WorkFluxPro_Roles::get_user_workflux_role($current_user_id);

if (!$current_workflux_role && current_user_can('manage_options')) {
    $user = new WP_User($current_user_id);
    $user->add_role('wfp_super_admin');
    $fixes_applied[] = "Added Super Admin role to current user";
}

// Test 1: Check permissions
$can_manage_employees = WorkFluxPro_Roles::user_can('wfp_manage_employees');
$test_results['permissions'] = array(
    'status' => $can_manage_employees ? 'pass' : 'fail',
    'message' => $can_manage_employees ? 'User has wfp_manage_employees permission' : 'User lacks wfp_manage_employees permission'
);

// Test 2: Check AJAX registration
global $wp_filter;
$ajax_registered = isset($wp_filter['wp_ajax_wfp_create_employee']) && !empty($wp_filter['wp_ajax_wfp_create_employee']->callbacks);
$test_results['ajax'] = array(
    'status' => $ajax_registered ? 'pass' : 'fail',
    'message' => $ajax_registered ? 'AJAX action wfp_create_employee is registered' : 'AJAX action wfp_create_employee is NOT registered'
);

// Test 3: Check User Management class
$user_mgmt_exists = class_exists('WorkFluxPro_User_Management') && method_exists('WorkFluxPro_User_Management', 'create_employee');
$test_results['user_management'] = array(
    'status' => $user_mgmt_exists ? 'pass' : 'fail',
    'message' => $user_mgmt_exists ? 'User Management class and create_employee method exist' : 'User Management class or create_employee method missing'
);

// Test 4: Database table check
global $wpdb;
$employees_table = $wpdb->prefix . 'wfp_employees';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$employees_table'") === $employees_table;
$test_results['database'] = array(
    'status' => $table_exists ? 'pass' : 'fail',
    'message' => $table_exists ? 'Employee database table exists' : 'Employee database table missing'
);

// Handle test employee creation
$creation_result = '';
if (isset($_POST['test_create_employee'])) {
    $test_data = array(
        'user_login' => 'test_employee_' . time(),
        'user_email' => 'test' . time() . '@example.com',
        'user_pass' => 'TempPass123!',
        'first_name' => 'Test',
        'last_name' => 'Employee',
        'display_name' => 'Test Employee',
        'workflux_role' => 'wfp_employee',
        'employee_id' => 'TEST' . time(),
        'department' => 'Testing',
        'designation' => 'Test Employee',
        'hire_date' => date('Y-m-d'),
        'manager_id' => $current_user_id
    );
    
    // Simulate the AJAX call
    $_POST = array_merge($_POST, $test_data);
    $_POST['nonce'] = wp_create_nonce('workflux_pro_nonce');
    
    ob_start();
    try {
        WorkFluxPro_Ajax::create_employee();
    } catch (Exception $e) {
        $creation_result = '<div class="test-error">Exception: ' . esc_html($e->getMessage()) . '</div>';
    }
    $ajax_output = ob_get_clean();
    
    if (empty($creation_result)) {
        if (!empty($ajax_output)) {
            $response = json_decode($ajax_output, true);
            if ($response && isset($response['success'])) {
                if ($response['success']) {
                    $creation_result = '<div class="test-success">✅ Employee created successfully!</div>';
                } else {
                    $creation_result = '<div class="test-error">❌ Employee creation failed: ' . esc_html($response['message']) . '</div>';
                }
            } else {
                $creation_result = '<div class="test-error">❌ Invalid AJAX response: ' . esc_html($ajax_output) . '</div>';
            }
        } else {
            $creation_result = '<div class="test-error">❌ No response from AJAX call</div>';
        }
    }
}

?><!DOCTYPE html>
<html>
<head>
    <title>Employee Creation Security Fix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .fix-item, .test-item { margin: 10px 0; padding: 15px; border-radius: 5px; }
        .fix-success, .test-pass { border-left: 4px solid #46b450; background: #f7fff7; }
        .fix-error, .test-fail { border-left: 4px solid #dc3232; background: #fef7f7; }
        .test-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; }
        .test-error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; }
        .btn { padding: 10px 15px; margin: 5px; background: #007cba; color: white; border: none; cursor: pointer; text-decoration: none; display: inline-block; border-radius: 4px; }
        .btn:hover { background: #005177; }
        .btn-danger { background: #dc3545; }
        .btn-success { background: #28a745; }
        h1, h2 { color: #333; }
        .status-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; max-width: 300px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>WorkFlux Pro Employee Creation Security Fix</h1>
    
    <h2>🔧 Fixes Applied</h2>
    <?php if (!empty($fixes_applied)): ?>
        <?php foreach ($fixes_applied as $fix): ?>
            <div class="fix-item fix-success">✓ <?php echo esc_html($fix); ?></div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="fix-item">No fixes were needed - system appears to be configured correctly.</div>
    <?php endif; ?>
    
    <h2>🧪 System Tests</h2>
    <div class="status-grid">
        <?php foreach ($test_results as $test_name => $result): ?>
            <div class="test-item test-<?php echo $result['status']; ?>">
                <strong><?php echo ucfirst(str_replace('_', ' ', $test_name)); ?>:</strong><br>
                <?php echo $result['status'] === 'pass' ? '✅' : '❌'; ?> <?php echo esc_html($result['message']); ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <h2>👤 Current User Status</h2>
    <table>
        <tr>
            <td><strong>User:</strong></td>
            <td><?php echo wp_get_current_user()->display_name; ?> (ID: <?php echo get_current_user_id(); ?>)</td>
        </tr>
        <tr>
            <td><strong>WordPress Roles:</strong></td>
            <td><?php echo implode(', ', wp_get_current_user()->roles); ?></td>
        </tr>
        <tr>
            <td><strong>WorkFlux Role:</strong></td>
            <td><?php echo WorkFluxPro_Roles::get_user_workflux_role() ?: 'None assigned'; ?></td>
        </tr>
        <tr>
            <td><strong>Can Manage Employees:</strong></td>
            <td><?php echo WorkFluxPro_Roles::user_can('wfp_manage_employees') ? '✅ Yes' : '❌ No'; ?></td>
        </tr>
        <tr>
            <td><strong>Can Manage Projects:</strong></td>
            <td><?php echo WorkFluxPro_Roles::user_can('wfp_manage_projects') ? '✅ Yes' : '❌ No'; ?></td>
        </tr>
    </table>
    
    <?php if ($creation_result): ?>
        <h2>🧪 Employee Creation Test Result</h2>
        <?php echo $creation_result; ?>
    <?php endif; ?>
    
    <h2>🧪 Test Employee Creation</h2>
    <p>Click the button below to test the employee creation process with a temporary test user:</p>
    
    <form method="post">
        <button type="submit" name="test_create_employee" class="btn btn-success">Create Test Employee</button>
    </form>
    
    <h2>🔧 Manual Employee Creation Test</h2>
    <p>Use this form to test employee creation with custom data:</p>
    
    <form id="manual-test-form">
        <div class="form-group">
            <label>Username:</label>
            <input type="text" id="test-username" value="manual_test_<?php echo time(); ?>" required>
        </div>
        <div class="form-group">
            <label>Email:</label>
            <input type="email" id="test-email" value="manual<?php echo time(); ?>@example.com" required>
        </div>
        <div class="form-group">
            <label>Password:</label>
            <input type="password" id="test-password" value="TempPass123!" required>
        </div>
        <div class="form-group">
            <label>First Name:</label>
            <input type="text" id="test-firstname" value="Manual" required>
        </div>
        <div class="form-group">
            <label>Last Name:</label>
            <input type="text" id="test-lastname" value="Test" required>
        </div>
        <div class="form-group">
            <label>WorkFlux Role:</label>
            <select id="test-role">
                <option value="wfp_employee">Employee</option>
                <option value="wfp_project_admin">Project Admin</option>
                <option value="wfp_hr_manager">HR Manager</option>
                <option value="wfp_managing_head">Managing Head</option>
            </select>
        </div>
        <div class="form-group">
            <label>Employee ID:</label>
            <input type="text" id="test-empid" value="MANUAL<?php echo time(); ?>" required>
        </div>
        <div class="form-group">
            <label>Department:</label>
            <input type="text" id="test-department" value="Testing" required>
        </div>
        <div class="form-group">
            <label>Designation:</label>
            <input type="text" id="test-designation" value="Manual Test Employee" required>
        </div>
        
        <button type="button" onclick="testEmployeeCreation()" class="btn">Test Employee Creation</button>
    </form>
    
    <div id="manual-test-result" style="margin-top: 20px;"></div>
    
    <h2>📋 Next Steps</h2>
    <div class="fix-item">
        <p><strong>If tests are passing:</strong></p>
        <ul>
            <li>Employee creation should now work in the main interface</li>
            <li>Try creating an employee through WorkFlux Pro → Employees → Add New</li>
            <li>Check that emails are properly sent (if email system is configured)</li>
        </ul>
        
        <p><strong>If tests are still failing:</strong></p>
        <ul>
            <li>Check the browser console for JavaScript errors</li>
            <li>Verify the AJAX URL and nonce are correct</li>
            <li>Enable WordPress debug mode to see detailed error messages</li>
            <li>Check database permissions and table structure</li>
        </ul>
        
        <p><strong>Related Tools:</strong></p>
        <ul>
            <li><a href="activation-check.php">Full System Check</a></li>
            <li><a href="ajax-debug.php">AJAX Debug Tool</a></li>
            <li><a href="security-quick-fix.php">General Security Fix</a></li>
        </ul>
    </div>
    
    <script>
        const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
        const nonce = '<?php echo wp_create_nonce('workflux_pro_nonce'); ?>';
        
        function testEmployeeCreation() {
            const resultDiv = document.getElementById('manual-test-result');
            resultDiv.innerHTML = '<div style="padding: 15px; background: #f0f0f0; border-radius: 5px;">Testing employee creation...</div>';
            
            const data = {
                action: 'wfp_create_employee',
                nonce: nonce,
                user_login: document.getElementById('test-username').value,
                user_email: document.getElementById('test-email').value,
                user_pass: document.getElementById('test-password').value,
                first_name: document.getElementById('test-firstname').value,
                last_name: document.getElementById('test-lastname').value,
                display_name: document.getElementById('test-firstname').value + ' ' + document.getElementById('test-lastname').value,
                workflux_role: document.getElementById('test-role').value,
                employee_id: document.getElementById('test-empid').value,
                department: document.getElementById('test-department').value,
                designation: document.getElementById('test-designation').value,
                hire_date: '<?php echo date('Y-m-d'); ?>',
                manager_id: <?php echo get_current_user_id(); ?>
            };
            
            fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data).toString()
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    resultDiv.innerHTML = '<div class="test-success">✅ Employee created successfully! User ID: ' + (result.data ? result.data.user_id : 'N/A') + '</div>';
                } else {
                    resultDiv.innerHTML = '<div class="test-error">❌ Employee creation failed: ' + (result.message || 'Unknown error') + '</div>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<div class="test-error">❌ AJAX request failed: ' + error.message + '</div>';
            });
        }
    </script>
</body>
</html>