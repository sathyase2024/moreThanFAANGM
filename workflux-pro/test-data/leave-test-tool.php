<?php
/**
 * Leave Management Test Tool
 * Test leave submission and email notifications
 */

// WordPress environment
if (!defined('ABSPATH')) {
    require_once('../../../../wp-config.php');
}

// Only allow admin users
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

// Handle test submission
$test_result = '';
if (isset($_POST['test_leave_submission'])) {
    $test_data = array(
        'employee_id' => get_current_user_id(),
        'leave_type' => sanitize_text_field($_POST['leave_type']),
        'start_date' => sanitize_text_field($_POST['start_date']),
        'end_date' => sanitize_text_field($_POST['end_date']),
        'reason' => sanitize_textarea_field($_POST['reason'])
    );
    
    $result = WorkFluxPro_Leave_Management::submit_request($test_data);
    
    if (is_array($result) && isset($result['error'])) {
        $test_result = '<div class="test-error">❌ Error: ' . esc_html($result['error']) . '</div>';
    } elseif ($result) {
        $test_result = '<div class="test-success">✅ Success! Leave request #' . $result . ' submitted successfully.</div>';
    } else {
        $test_result = '<div class="test-error">❌ Failed to submit leave request (unknown error)</div>';
    }
}

// Handle email test
$email_test_result = '';
if (isset($_POST['test_email'])) {
    $to = sanitize_email($_POST['test_email_address']);
    $subject = 'WorkFlux Pro Email Test';
    $message = '
    <h2>Email Test Successful</h2>
    <p>This is a test email from WorkFlux Pro email notification system.</p>
    <p><strong>Test Details:</strong></p>
    <ul>
        <li>Date: ' . date('F j, Y g:i A') . '</li>
        <li>From: ' . get_bloginfo('name') . '</li>
        <li>System: WorkFlux Pro v' . (defined('WORKFLUX_PRO_VERSION') ? WORKFLUX_PRO_VERSION : '1.0.0') . '</li>
    </ul>
    ';
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    if (wp_mail($to, $subject, $message, $headers)) {
        $email_test_result = '<div class="test-success">✅ Test email sent successfully to ' . esc_html($to) . '</div>';
    } else {
        $email_test_result = '<div class="test-error">❌ Failed to send test email to ' . esc_html($to) . '</div>';
    }
}

?><!DOCTYPE html>
<html>
<head>
    <title>WorkFlux Pro Leave & Email Test Tool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .test-success { padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; color: #155724; border-radius: 5px; margin: 10px 0; }
        .test-error { padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 5px; margin: 10px 0; }
        .test-warning { padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; border-radius: 5px; margin: 10px 0; }
        .btn { padding: 10px 15px; margin: 5px; background: #007cba; color: white; border: none; cursor: pointer; border-radius: 4px; }
        .btn:hover { background: #005a87; }
        .btn-danger { background: #dc3545; }
        .btn-success { background: #28a745; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { width: 100%; max-width: 400px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        textarea { height: 80px; resize: vertical; }
        h1, h2 { color: #333; }
        .status-info { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
    </style>
</head>
<body>
    <h1>WorkFlux Pro Leave & Email Test Tool</h1>
    
    <!-- Current Status -->
    <div class="status-info">
        <h3>Current Status</h3>
        <table>
            <tr>
                <td><strong>Current User:</strong></td>
                <td><?php echo wp_get_current_user()->display_name; ?> (ID: <?php echo get_current_user_id(); ?>)</td>
            </tr>
            <tr>
                <td><strong>WorkFlux Role:</strong></td>
                <td><?php echo WorkFluxPro_Roles::get_user_workflux_role() ?: 'None assigned'; ?></td>
            </tr>
            <tr>
                <td><strong>Can Submit Leave:</strong></td>
                <td><?php echo WorkFluxPro_Roles::user_can('wfp_submit_leave_requests') ? '✅ Yes' : '❌ No'; ?></td>
            </tr>
            <tr>
                <td><strong>Email From:</strong></td>
                <td><?php echo get_option('admin_email'); ?></td>
            </tr>
            <tr>
                <td><strong>Site Name:</strong></td>
                <td><?php echo get_bloginfo('name'); ?></td>
            </tr>
        </table>
    </div>
    
    <?php if ($test_result): ?>
        <?php echo $test_result; ?>
    <?php endif; ?>
    
    <?php if ($email_test_result): ?>
        <?php echo $email_test_result; ?>
    <?php endif; ?>
    
    <!-- Leave Submission Test -->
    <div class="test-section">
        <h2>🏖️ Leave Submission Test</h2>
        <p>Test the leave request submission system with validation and email notifications.</p>
        
        <form method="post">
            <div class="form-group">
                <label for="leave_type">Leave Type:</label>
                <select name="leave_type" id="leave_type" required>
                    <option value="">Select Leave Type</option>
                    <?php foreach (WorkFluxPro_Leave_Management::get_leave_types() as $type => $label): ?>
                        <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="start_date">Start Date:</label>
                <input type="date" name="start_date" id="start_date" value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="end_date">End Date:</label>
                <input type="date" name="end_date" id="end_date" value="<?php echo date('Y-m-d', strtotime('+3 days')); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="reason">Reason (Optional):</label>
                <textarea name="reason" id="reason" placeholder="Enter reason for leave request...">Test leave request from WorkFlux Pro testing tool</textarea>
            </div>
            
            <button type="submit" name="test_leave_submission" class="btn btn-success">Submit Test Leave Request</button>
        </form>
    </div>
    
    <!-- Email Test -->
    <div class="test-section">
        <h2>📧 Email System Test</h2>
        <p>Test the email notification system to ensure emails are being sent properly.</p>
        
        <form method="post">
            <div class="form-group">
                <label for="test_email_address">Test Email Address:</label>
                <input type="email" name="test_email_address" id="test_email_address" 
                       value="<?php echo get_option('admin_email'); ?>" required 
                       placeholder="Enter email address to test">
            </div>
            
            <button type="submit" name="test_email" class="btn">Send Test Email</button>
        </form>
    </div>
    
    <!-- Current Leave Balance -->
    <div class="test-section">
        <h2>📊 Current Leave Balance</h2>
        <?php 
        $current_user_id = get_current_user_id();
        $employee = WorkFluxPro_User_Management::get_employee_by_user_id($current_user_id);
        
        if ($employee): ?>
            <?php $balances = WorkFluxPro_Leave_Management::get_leave_balance($current_user_id); ?>
            <table>
                <thead>
                    <tr>
                        <th>Leave Type</th>
                        <th>Total Days</th>
                        <th>Used Days</th>
                        <th>Remaining Days</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($balances as $type => $balance): ?>
                        <tr>
                            <td><?php echo esc_html($balance['label']); ?></td>
                            <td><?php echo esc_html($balance['total']); ?></td>
                            <td><?php echo esc_html($balance['used']); ?></td>
                            <td><?php echo esc_html($balance['remaining']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="test-warning">⚠️ No employee record found for current user. Please create an employee record first.</div>
        <?php endif; ?>
    </div>
    
    <!-- Recent Leave Requests -->
    <div class="test-section">
        <h2>📋 Recent Leave Requests</h2>
        <?php 
        $recent_requests = WorkFluxPro_Leave_Management::get_user_requests($current_user_id, 5);
        
        if (!empty($recent_requests)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Leave Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Days</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_requests as $request): ?>
                        <tr>
                            <td><?php echo esc_html($request->id); ?></td>
                            <td><?php 
                                $leave_types = WorkFluxPro_Leave_Management::get_leave_types();
                                echo esc_html($leave_types[$request->leave_type] ?? $request->leave_type); 
                            ?></td>
                            <td><?php echo esc_html(date('M j, Y', strtotime($request->start_date))); ?></td>
                            <td><?php echo esc_html(date('M j, Y', strtotime($request->end_date))); ?></td>
                            <td><?php echo esc_html($request->days_requested); ?></td>
                            <td>
                                <span style="padding: 4px 8px; border-radius: 3px; font-size: 12px; 
                                    <?php 
                                    switch($request->status) {
                                        case 'approved':
                                            echo 'background: #d4edda; color: #155724;';
                                            break;
                                        case 'rejected':
                                            echo 'background: #f8d7da; color: #721c24;';
                                            break;
                                        case 'pending':
                                            echo 'background: #fff3cd; color: #856404;';
                                            break;
                                        default:
                                            echo 'background: #e2e3e5; color: #383d41;';
                                    }
                                    ?>">
                                    <?php echo esc_html(ucfirst($request->status)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(date('M j, Y', strtotime($request->created_at))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="test-warning">No leave requests found. Submit a test request above to see it here.</div>
        <?php endif; ?>
    </div>
    
    <!-- Validation Tests -->
    <div class="test-section">
        <h2>🧪 Quick Validation Tests</h2>
        <p>Click these buttons to test various validation scenarios:</p>
        
        <button onclick="testPastDate()" class="btn">Test Past Date Error</button>
        <button onclick="testInvalidDateRange()" class="btn">Test Invalid Date Range</button>
        <button onclick="testMissingFields()" class="btn">Test Missing Fields</button>
        
        <div id="validation-result" style="margin-top: 15px;"></div>
    </div>
    
    <script>
        const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
        const nonce = '<?php echo wp_create_nonce('workflux_pro_nonce'); ?>';
        
        function testValidation(data, testName) {
            const resultDiv = document.getElementById('validation-result');
            resultDiv.innerHTML = 'Testing ' + testName + '...';
            
            fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=wfp_submit_leave_request&nonce=' + nonce + '&' + new URLSearchParams(data).toString()
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    resultDiv.innerHTML = '<div class="test-error">❌ ' + testName + ': Validation should have failed but passed!</div>';
                } else {
                    resultDiv.innerHTML = '<div class="test-success">✅ ' + testName + ': Validation correctly failed - ' + result.message + '</div>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<div class="test-error">❌ ' + testName + ': Request failed - ' + error.message + '</div>';
            });
        }
        
        function testPastDate() {
            testValidation({
                leave_type: 'annual',
                start_date: '2023-01-01',
                end_date: '2023-01-02',
                reason: 'Test past date'
            }, 'Past Date Test');
        }
        
        function testInvalidDateRange() {
            testValidation({
                leave_type: 'annual',
                start_date: '<?php echo date('Y-m-d', strtotime('+5 days')); ?>',
                end_date: '<?php echo date('Y-m-d', strtotime('+1 day')); ?>',
                reason: 'Test invalid range'
            }, 'Invalid Date Range Test');
        }
        
        function testMissingFields() {
            testValidation({
                leave_type: '',
                start_date: '',
                end_date: '',
                reason: 'Test missing fields'
            }, 'Missing Fields Test');
        }
    </script>
</body>
</html>