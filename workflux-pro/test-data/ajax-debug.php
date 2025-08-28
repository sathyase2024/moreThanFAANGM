<?php
/**
 * AJAX Debug Tool
 * Simple tool to test WorkFlux Pro AJAX functionality
 */

// WordPress environment
if (!defined('ABSPATH')) {
    // Assuming this is run from WordPress admin
    require_once('../../../../wp-config.php');
}

// Only allow admin users
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

?><!DOCTYPE html>
<html>
<head>
    <title>WorkFlux Pro AJAX Debug Tool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .debug-button { padding: 10px 15px; margin: 5px; background: #0073aa; color: white; border: none; cursor: pointer; }
        .debug-button:hover { background: #005177; }
        .debug-result { margin: 10px 0; padding: 10px; background: #f9f9f9; border-left: 4px solid #00a0d2; }
        .debug-error { border-left-color: #dc3232; background: #fef7f7; }
        .debug-success { border-left-color: #46b450; background: #f7fff7; }
        input, select, textarea { margin: 5px; padding: 8px; }
        .form-group { margin: 10px 0; }
        label { display: inline-block; width: 150px; }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h1>WorkFlux Pro AJAX Debug Tool</h1>
    
    <div class="debug-section">
        <h2>Security Test</h2>
        <button class="debug-button" onclick="testSecurity()">Test Nonce Security</button>
        <div id="security-result" class="debug-result" style="display:none;"></div>
    </div>
    
    <div class="debug-section">
        <h2>Employee Management</h2>
        <div class="form-group">
            <label>Username:</label>
            <input type="text" id="emp-username" placeholder="test.employee">
        </div>
        <div class="form-group">
            <label>Email:</label>
            <input type="email" id="emp-email" placeholder="test@example.com">
        </div>
        <div class="form-group">
            <label>Password:</label>
            <input type="password" id="emp-password" placeholder="TempPass123">
        </div>
        <div class="form-group">
            <label>First Name:</label>
            <input type="text" id="emp-firstname" placeholder="Test">
        </div>
        <div class="form-group">
            <label>Last Name:</label>
            <input type="text" id="emp-lastname" placeholder="Employee">
        </div>
        <div class="form-group">
            <label>Role:</label>
            <select id="emp-role">
                <option value="wfp_employee">Employee</option>
                <option value="wfp_project_admin">Project Admin</option>
                <option value="wfp_hr_manager">HR Manager</option>
                <option value="wfp_managing_head">Managing Head</option>
                <option value="wfp_super_admin">Super Admin</option>
            </select>
        </div>
        <div class="form-group">
            <label>Employee ID:</label>
            <input type="text" id="emp-id" placeholder="EMP2024TEST">
        </div>
        <div class="form-group">
            <label>Department:</label>
            <input type="text" id="emp-department" placeholder="Information Technology">
        </div>
        <div class="form-group">
            <label>Designation:</label>
            <input type="text" id="emp-designation" placeholder="Test Developer">
        </div>
        <button class="debug-button" onclick="createEmployee()">Create Employee</button>
        <div id="employee-result" class="debug-result" style="display:none;"></div>
    </div>
    
    <div class="debug-section">
        <h2>Project Management</h2>
        <div class="form-group">
            <label>Project Name:</label>
            <input type="text" id="proj-name" placeholder="Test Project">
        </div>
        <div class="form-group">
            <label>Description:</label>
            <textarea id="proj-description" placeholder="Test project description"></textarea>
        </div>
        <div class="form-group">
            <label>Project Code:</label>
            <input type="text" id="proj-code" placeholder="TEST2024">
        </div>
        <div class="form-group">
            <label>Client:</label>
            <input type="text" id="proj-client" placeholder="Test Client Inc">
        </div>
        <div class="form-group">
            <label>Start Date:</label>
            <input type="date" id="proj-start" value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="form-group">
            <label>End Date:</label>
            <input type="date" id="proj-end" value="<?php echo date('Y-m-d', strtotime('+3 months')); ?>">
        </div>
        <div class="form-group">
            <label>Estimated Hours:</label>
            <input type="number" id="proj-hours" placeholder="100" value="100">
        </div>
        <div class="form-group">
            <label>Priority:</label>
            <select id="proj-priority">
                <option value="low">Low</option>
                <option value="medium" selected>Medium</option>
                <option value="high">High</option>
            </select>
        </div>
        <button class="debug-button" onclick="createProject()">Create Project</button>
        <div id="project-result" class="debug-result" style="display:none;"></div>
    </div>
    
    <div class="debug-section">
        <h2>Time Tracking</h2>
        <button class="debug-button" onclick="clockIn()">Clock In</button>
        <button class="debug-button" onclick="clockOut()">Clock Out</button>
        <div id="time-result" class="debug-result" style="display:none;"></div>
    </div>
    
    <div class="debug-section">
        <h2>Leave Management</h2>
        <div class="form-group">
            <label>Leave Type:</label>
            <select id="leave-type">
                <option value="annual">Annual Leave</option>
                <option value="sick">Sick Leave</option>
                <option value="personal">Personal Leave</option>
                <option value="emergency">Emergency Leave</option>
            </select>
        </div>
        <div class="form-group">
            <label>Start Date:</label>
            <input type="date" id="leave-start" value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
        </div>
        <div class="form-group">
            <label>End Date:</label>
            <input type="date" id="leave-end" value="<?php echo date('Y-m-d', strtotime('+3 days')); ?>">
        </div>
        <div class="form-group">
            <label>Reason:</label>
            <textarea id="leave-reason" placeholder="Test leave request">Test leave request from AJAX debug tool</textarea>
        </div>
        <button class="debug-button" onclick="submitLeaveRequest()">Submit Leave Request</button>
        <div id="leave-result" class="debug-result" style="display:none;"></div>
    </div>
    
    <div class="debug-section">
        <h2>Dashboard Data</h2>
        <button class="debug-button" onclick="getDashboardData()">Get Dashboard Data</button>
        <div id="dashboard-result" class="debug-result" style="display:none;"></div>
    </div>

    <script>
        // Get WordPress AJAX URL and nonce
        const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
        const nonce = '<?php echo wp_create_nonce('workflux_pro_nonce'); ?>';
        const currentUser = <?php echo get_current_user_id(); ?>;
        
        // Helper function for AJAX requests
        function makeAjaxRequest(action, data, resultElementId) {
            const $result = $('#' + resultElementId);
            $result.show().removeClass('debug-success debug-error').html('Processing...');
            
            const requestData = {
                action: action,
                nonce: nonce,
                ...data
            };
            
            $.post(ajaxUrl, requestData)
                .done(function(response) {
                    console.log('Response:', response);
                    
                    if (response.success) {
                        $result.addClass('debug-success').html(`
                            <strong>Success:</strong> ${response.message || 'Operation completed successfully'}<br>
                            <strong>Data:</strong> <pre>${JSON.stringify(response.data, null, 2)}</pre>
                        `);
                    } else {
                        $result.addClass('debug-error').html(`
                            <strong>Error:</strong> ${response.message || 'Unknown error occurred'}<br>
                            <strong>Response:</strong> <pre>${JSON.stringify(response, null, 2)}</pre>
                        `);
                    }
                })
                .fail(function(xhr, status, error) {
                    console.error('AJAX Error:', xhr.responseText);
                    $result.addClass('debug-error').html(`
                        <strong>AJAX Error:</strong> ${error}<br>
                        <strong>Status:</strong> ${status}<br>
                        <strong>Response:</strong> <pre>${xhr.responseText}</pre>
                    `);
                });
        }
        
        function testSecurity() {
            // Test without nonce
            $.post(ajaxUrl, {
                action: 'wfp_get_dashboard_data'
            }).always(function(response) {
                const $result = $('#security-result');
                $result.show().html(`
                    <strong>Test without nonce:</strong><br>
                    <pre>${JSON.stringify(response, null, 2)}</pre>
                `);
            });
        }
        
        function createEmployee() {
            const data = {
                user_login: $('#emp-username').val(),
                user_email: $('#emp-email').val(),
                user_pass: $('#emp-password').val(),
                first_name: $('#emp-firstname').val(),
                last_name: $('#emp-lastname').val(),
                display_name: $('#emp-firstname').val() + ' ' + $('#emp-lastname').val(),
                workflux_role: $('#emp-role').val(),
                employee_id: $('#emp-id').val(),
                department: $('#emp-department').val(),
                designation: $('#emp-designation').val(),
                hire_date: '<?php echo date('Y-m-d'); ?>',
                manager_id: currentUser
            };
            
            makeAjaxRequest('wfp_create_employee', data, 'employee-result');
        }
        
        function createProject() {
            const data = {
                name: $('#proj-name').val(),
                description: $('#proj-description').val(),
                project_code: $('#proj-code').val(),
                client: $('#proj-client').val(),
                start_date: $('#proj-start').val(),
                end_date: $('#proj-end').val(),
                estimated_hours: $('#proj-hours').val(),
                priority: $('#proj-priority').val()
            };
            
            makeAjaxRequest('wfp_create_project', data, 'project-result');
        }
        
        function clockIn() {
            const data = {
                location: 'Debug Test Location'
            };
            
            makeAjaxRequest('wfp_clock_in', data, 'time-result');
        }
        
        function clockOut() {
            const data = {
                description: 'Debug test clock out'
            };
            
            makeAjaxRequest('wfp_clock_out', data, 'time-result');
        }
        
        function submitLeaveRequest() {
            const data = {
                leave_type: $('#leave-type').val(),
                start_date: $('#leave-start').val(),
                end_date: $('#leave-end').val(),
                reason: $('#leave-reason').val()
            };
            
            makeAjaxRequest('wfp_submit_leave_request', data, 'leave-result');
        }
        
        function getDashboardData() {
            makeAjaxRequest('wfp_get_dashboard_data', {}, 'dashboard-result');
        }
    </script>
</body>
</html>