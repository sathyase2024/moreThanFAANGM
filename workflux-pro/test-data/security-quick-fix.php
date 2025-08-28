<?php
/**
 * WorkFlux Pro Security Quick Fix
 * Addresses common security and AJAX issues
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
$errors = array();

// Fix 1: Re-register AJAX actions
if (class_exists('WorkFluxPro_Ajax')) {
    try {
        // Force re-initialization of AJAX hooks
        new WorkFluxPro_Ajax();
        $fixes_applied[] = "AJAX hooks re-registered";
    } catch (Exception $e) {
        $errors[] = "Failed to re-register AJAX hooks: " . $e->getMessage();
    }
} else {
    $errors[] = "WorkFluxPro_Ajax class not found";
}

// Fix 2: Ensure roles are properly created
if (class_exists('WorkFluxPro_Roles')) {
    try {
        WorkFluxPro_Roles::create_roles();
        $fixes_applied[] = "WorkFlux roles verified/created";
    } catch (Exception $e) {
        $errors[] = "Failed to create roles: " . $e->getMessage();
    }
} else {
    $errors[] = "WorkFluxPro_Roles class not found";
}

// Fix 3: Ensure database tables exist
if (class_exists('WorkFluxPro_Database')) {
    try {
        $db = new WorkFluxPro_Database();
        $db->create_tables();
        $fixes_applied[] = "Database tables verified/created";
    } catch (Exception $e) {
        $errors[] = "Failed to create database tables: " . $e->getMessage();
    }
} else {
    $errors[] = "WorkFluxPro_Database class not found";
}

// Fix 4: Assign current user a WorkFlux role if needed
$current_user_id = get_current_user_id();
$current_workflux_role = WorkFluxPro_Roles::get_user_workflux_role($current_user_id);

if (!$current_workflux_role && current_user_can('manage_options')) {
    $user = new WP_User($current_user_id);
    $user->add_role('wfp_super_admin');
    $fixes_applied[] = "Assigned Super Admin role to current user";
}

// Fix 5: Clear any cached data
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
    $fixes_applied[] = "WordPress cache cleared";
}

// Fix 6: Test AJAX functionality
$test_nonce = wp_create_nonce('workflux_pro_nonce');
$ajax_test_result = "Nonce generated successfully: " . substr($test_nonce, 0, 10) . "...";
$fixes_applied[] = $ajax_test_result;

?><!DOCTYPE html>
<html>
<head>
    <title>WorkFlux Pro Security Quick Fix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .fix-item { margin: 10px 0; padding: 10px; }
        .fix-success { border-left: 4px solid #46b450; background: #f7fff7; }
        .fix-error { border-left: 4px solid #dc3232; background: #fef7f7; }
        .btn { padding: 10px 15px; margin: 5px; background: #0073aa; color: white; border: none; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #005177; }
        h1, h2 { color: #333; }
        pre { background: #f9f9f9; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>WorkFlux Pro Security Quick Fix</h1>
    
    <h2>✅ Fixes Applied</h2>
    <?php if (!empty($fixes_applied)): ?>
        <?php foreach ($fixes_applied as $fix): ?>
            <div class="fix-item fix-success">✓ <?php echo esc_html($fix); ?></div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="fix-item">No fixes were needed.</div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <h2>❌ Errors Encountered</h2>
        <?php foreach ($errors as $error): ?>
            <div class="fix-item fix-error">✗ <?php echo esc_html($error); ?></div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <h2>🧪 Quick Test</h2>
    <div class="fix-item">
        <button class="btn" onclick="testAjax()">Test AJAX Security</button>
        <div id="test-result" style="margin-top: 10px;"></div>
    </div>
    
    <h2>Next Steps</h2>
    <div class="fix-item">
        <p><strong>Recommended Actions:</strong></p>
        <ul>
            <li><a href="activation-check.php">Run Activation Check</a> - Verify all components</li>
            <li><a href="ajax-debug.php">Open AJAX Debug Tool</a> - Test specific functions</li>
            <li><a href="<?php echo admin_url('admin.php?page=workflux-pro'); ?>">Access WorkFlux Dashboard</a> - Try the main interface</li>
            <li>Test employee creation and project creation functions</li>
        </ul>
    </div>
    
    <h2>Current Status</h2>
    <div class="fix-item">
        <strong>Current User:</strong> <?php echo wp_get_current_user()->display_name; ?><br>
        <strong>WorkFlux Role:</strong> <?php echo WorkFluxPro_Roles::get_user_workflux_role() ?: 'None assigned'; ?><br>
        <strong>WordPress Roles:</strong> <?php echo implode(', ', wp_get_current_user()->roles); ?><br>
        <strong>Plugin Status:</strong> <?php echo is_plugin_active('workflux-pro/workflux-pro.php') ? 'Active' : 'Inactive'; ?><br>
        <strong>Generated Nonce:</strong> <code><?php echo wp_create_nonce('workflux_pro_nonce'); ?></code>
    </div>
    
    <script>
        const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
        const nonce = '<?php echo wp_create_nonce('workflux_pro_nonce'); ?>';
        
        function testAjax() {
            const resultDiv = document.getElementById('test-result');
            resultDiv.innerHTML = 'Testing...';
            
            // Test with jQuery if available, otherwise use fetch
            if (typeof jQuery !== 'undefined') {
                jQuery.post(ajaxUrl, {
                    action: 'wfp_get_dashboard_data',
                    nonce: nonce
                })
                .done(function(response) {
                    if (response.success) {
                        resultDiv.innerHTML = '<div class="fix-success">✅ AJAX Test Successful! Security is working properly.</div>';
                    } else {
                        resultDiv.innerHTML = '<div class="fix-error">❌ AJAX Test Failed: ' + (response.message || 'Unknown error') + '</div>';
                    }
                })
                .fail(function(xhr) {
                    resultDiv.innerHTML = '<div class="fix-error">❌ AJAX Request Failed: ' + xhr.responseText + '</div>';
                });
            } else {
                // Fallback to fetch API
                fetch(ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=wfp_get_dashboard_data&nonce=' + nonce
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resultDiv.innerHTML = '<div class="fix-success">✅ AJAX Test Successful! Security is working properly.</div>';
                    } else {
                        resultDiv.innerHTML = '<div class="fix-error">❌ AJAX Test Failed: ' + (data.message || 'Unknown error') + '</div>';
                    }
                })
                .catch(error => {
                    resultDiv.innerHTML = '<div class="fix-error">❌ AJAX Request Failed: ' + error.message + '</div>';
                });
            }
        }
    </script>
    
    <?php if (class_exists('jQuery')): ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <?php endif; ?>
</body>
</html>