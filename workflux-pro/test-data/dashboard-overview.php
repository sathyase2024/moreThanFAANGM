<?php
/**
 * WorkFlux Pro Dashboard Overview
 * Shows all available dashboards and their features by role
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
    <title>WorkFlux Pro Dashboard Overview</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .dashboard-section { margin: 30px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .super-admin { border-left: 4px solid #dc3545; background: #fff5f5; }
        .managing-head { border-left: 4px solid #fd7e14; background: #fff8f0; }
        .hr-manager { border-left: 4px solid #20c997; background: #f0fdf9; }
        .project-admin { border-left: 4px solid #0d6efd; background: #f0f8ff; }
        .employee { border-left: 4px solid #6f42c1; background: #f8f5ff; }
        .feature-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin: 15px 0; }
        .feature-item { padding: 10px; background: white; border-radius: 4px; border: 1px solid #e0e0e0; }
        .status-info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .btn { padding: 10px 15px; margin: 5px; background: #007cba; color: white; text-decoration: none; border-radius: 4px; display: inline-block; }
        .btn:hover { background: #005a87; color: white; }
        h1, h2, h3 { color: #333; }
        .role-badge { padding: 4px 8px; border-radius: 3px; color: white; font-size: 12px; font-weight: bold; }
        .badge-super { background: #dc3545; }
        .badge-managing { background: #fd7e14; }
        .badge-hr { background: #20c997; }
        .badge-project { background: #0d6efd; }
        .badge-employee { background: #6f42c1; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
        .test-dashboard { margin: 10px 0; }
    </style>
</head>
<body>
    <h1>📊 WorkFlux Pro Dashboard Overview</h1>
    
    <div class="status-info">
        <strong>🎯 Current Status:</strong><br>
        User: <?php echo wp_get_current_user()->display_name; ?> | 
        WordPress Role: <?php echo implode(', ', wp_get_current_user()->roles); ?> | 
        WorkFlux Role: <span class="role-badge badge-super"><?php echo WorkFluxPro_Roles::get_user_workflux_role() ?: 'None'; ?></span>
    </div>
    
    <!-- Super Admin Dashboard -->
    <div class="dashboard-section super-admin">
        <h2>🔴 Super Admin Dashboard <span class="role-badge badge-super">SUPER ADMIN</span></h2>
        
        <h3>📈 Dashboard Widgets:</h3>
        <div class="feature-list">
            <div class="feature-item">
                <strong>📊 Total Employees</strong><br>
                Complete count of all employees in system
            </div>
            <div class="feature-item">
                <strong>🚀 Active Projects</strong><br>
                All ongoing projects across departments
            </div>
            <div class="feature-item">
                <strong>📋 Pending Leaves</strong><br>
                Leave requests awaiting approval
            </div>
            <div class="feature-item">
                <strong>🚗 External Duties</strong><br>
                External duty requests pending approval
            </div>
            <div class="feature-item">
                <strong>🕒 Today's Attendance</strong><br>
                Real-time attendance tracking
            </div>
            <div class="feature-item">
                <strong>📝 Recent Activities</strong><br>
                Latest system activities and changes
            </div>
        </div>
        
        <h3>⚡ Quick Actions:</h3>
        <div class="feature-list">
            <div class="feature-item">✅ Create/Manage Employees</div>
            <div class="feature-item">✅ Create/Manage Projects</div>
            <div class="feature-item">✅ Approve All Requests</div>
            <div class="feature-item">✅ System Administration</div>
            <div class="feature-item">✅ View All Reports</div>
            <div class="feature-item">✅ Manage Roles & Settings</div>
        </div>
        
        <div class="test-dashboard">
            <a href="<?php echo admin_url('admin.php?page=workflux-pro'); ?>" class="btn">🔴 View Super Admin Dashboard</a>
        </div>
    </div>
    
    <!-- Managing Head Dashboard -->
    <div class="dashboard-section managing-head">
        <h2>🟠 Managing Head Dashboard <span class="role-badge badge-managing">MANAGING HEAD</span></h2>
        
        <h3>📈 Dashboard Widgets:</h3>
        <div class="feature-list">
            <div class="feature-item">
                <strong>👥 Team Overview</strong><br>
                Team members and their status
            </div>
            <div class="feature-item">
                <strong>📊 Team Projects</strong><br>
                Projects under management
            </div>
            <div class="feature-item">
                <strong>📋 Team Leave Requests</strong><br>
                Leave requests from team members
            </div>
            <div class="feature-item">
                <strong>🎯 Team Performance</strong><br>
                Productivity and performance metrics
            </div>
            <div class="feature-item">
                <strong>⏰ Team Time Tracking</strong><br>
                Team attendance and hours
            </div>
            <div class="feature-item">
                <strong>📈 Team Reports</strong><br>
                Departmental analytics
            </div>
        </div>
        
        <h3>⚡ Quick Actions:</h3>
        <div class="feature-list">
            <div class="feature-item">✅ Manage Team Projects</div>
            <div class="feature-item">✅ Approve Team Leaves</div>
            <div class="feature-item">✅ Assign Team Tasks</div>
            <div class="feature-item">✅ View Team Reports</div>
            <div class="feature-item">✅ Manage Team Members</div>
            <div class="feature-item">✅ Team Performance Review</div>
        </div>
    </div>
    
    <!-- HR Manager Dashboard -->
    <div class="dashboard-section hr-manager">
        <h2>🟢 HR Manager Dashboard <span class="role-badge badge-hr">HR MANAGER</span></h2>
        
        <h3>📈 Dashboard Widgets:</h3>
        <div class="feature-list">
            <div class="feature-item">
                <strong>👤 New Employees</strong><br>
                Recent hires this month
            </div>
            <div class="feature-item">
                <strong>📋 Pending Leave Requests</strong><br>
                All leave requests awaiting approval
            </div>
            <div class="feature-item">
                <strong>✅ Approved Leaves</strong><br>
                Leave requests approved this month
            </div>
            <div class="feature-item">
                <strong>🎂 Employee Birthdays</strong><br>
                Upcoming birthdays to celebrate
            </div>
            <div class="feature-item">
                <strong>🎉 Work Anniversaries</strong><br>
                Employee milestone celebrations
            </div>
            <div class="feature-item">
                <strong>📊 HR Analytics</strong><br>
                Employee engagement metrics
            </div>
        </div>
        
        <h3>⚡ Quick Actions:</h3>
        <div class="feature-list">
            <div class="feature-item">✅ Employee Onboarding</div>
            <div class="feature-item">✅ Manage Employee Data</div>
            <div class="feature-item">✅ Approve Leaves</div>
            <div class="feature-item">✅ HR Reports</div>
            <div class="feature-item">✅ Leave Policy Management</div>
            <div class="feature-item">✅ Performance Reviews</div>
        </div>
    </div>
    
    <!-- Project Admin Dashboard -->
    <div class="dashboard-section project-admin">
        <h2>🔵 Project Admin Dashboard <span class="role-badge badge-project">PROJECT ADMIN</span></h2>
        
        <h3>📈 Dashboard Widgets:</h3>
        <div class="feature-list">
            <div class="feature-item">
                <strong>📂 My Projects</strong><br>
                Projects under direct management
            </div>
            <div class="feature-item">
                <strong>⏳ Pending Tasks</strong><br>
                Tasks awaiting completion
            </div>
            <div class="feature-item">
                <strong>🚨 Overdue Tasks</strong><br>
                Tasks past their deadlines
            </div>
            <div class="feature-item">
                <strong>📈 Project Progress</strong><br>
                Real-time project completion status
            </div>
            <div class="feature-item">
                <strong>⏱️ Time Tracking</strong><br>
                Project time allocation and usage
            </div>
            <div class="feature-item">
                <strong>👥 Team Assignments</strong><br>
                Team member project allocations
            </div>
        </div>
        
        <h3>⚡ Quick Actions:</h3>
        <div class="feature-list">
            <div class="feature-item">✅ Create/Manage Projects</div>
            <div class="feature-item">✅ Assign Tasks</div>
            <div class="feature-item">✅ Approve Team Members</div>
            <div class="feature-item">✅ Project Reports</div>
            <div class="feature-item">✅ Timeline Management</div>
            <div class="feature-item">✅ Budget Tracking</div>
        </div>
    </div>
    
    <!-- Employee Dashboard -->
    <div class="dashboard-section employee">
        <h2>🟣 Employee Dashboard <span class="role-badge badge-employee">EMPLOYEE</span></h2>
        
        <h3>📈 Dashboard Widgets:</h3>
        <div class="feature-list">
            <div class="feature-item">
                <strong>🕒 Clock Status</strong><br>
                Current clock in/out status
            </div>
            <div class="feature-item">
                <strong>⏰ Today's Hours</strong><br>
                Hours worked today
            </div>
            <div class="feature-item">
                <strong>📂 Assigned Projects</strong><br>
                Current project assignments
            </div>
            <div class="feature-item">
                <strong>✅ My Tasks</strong><br>
                Personal task list and deadlines
            </div>
            <div class="feature-item">
                <strong>🏖️ Leave Balance</strong><br>
                Available leave days by type
            </div>
            <div class="feature-item">
                <strong>📋 Recent Leaves</strong><br>
                Recent leave request history
            </div>
        </div>
        
        <h3>⚡ Quick Actions:</h3>
        <div class="feature-list">
            <div class="feature-item">✅ Clock In/Out</div>
            <div class="feature-item">✅ Start/Stop Projects</div>
            <div class="feature-item">✅ Submit Leave Requests</div>
            <div class="feature-item">✅ External Duty Requests</div>
            <div class="feature-item">✅ View Own Reports</div>
            <div class="feature-item">✅ Update Profile</div>
        </div>
    </div>
    
    <!-- Dashboard Feature Comparison -->
    <div class="dashboard-section">
        <h2>📊 Dashboard Feature Comparison</h2>
        
        <table>
            <thead>
                <tr>
                    <th>Feature</th>
                    <th>Super Admin</th>
                    <th>Managing Head</th>
                    <th>HR Manager</th>
                    <th>Project Admin</th>
                    <th>Employee</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Employee Management</strong></td>
                    <td>✅ Full Access</td>
                    <td>✅ Team Only</td>
                    <td>✅ HR Functions</td>
                    <td>❌ No Access</td>
                    <td>❌ No Access</td>
                </tr>
                <tr>
                    <td><strong>Project Management</strong></td>
                    <td>✅ All Projects</td>
                    <td>✅ Team Projects</td>
                    <td>✅ Assign Only</td>
                    <td>✅ Own Projects</td>
                    <td>❌ View Only</td>
                </tr>
                <tr>
                    <td><strong>Leave Approval</strong></td>
                    <td>✅ All Requests</td>
                    <td>✅ Team Requests</td>
                    <td>✅ All Requests</td>
                    <td>❌ No Access</td>
                    <td>❌ Submit Only</td>
                </tr>
                <tr>
                    <td><strong>Time Tracking</strong></td>
                    <td>✅ System Wide</td>
                    <td>✅ Team View</td>
                    <td>✅ HR Analytics</td>
                    <td>✅ Project Level</td>
                    <td>✅ Own Only</td>
                </tr>
                <tr>
                    <td><strong>Reports & Analytics</strong></td>
                    <td>✅ All Reports</td>
                    <td>✅ Team Reports</td>
                    <td>✅ HR Reports</td>
                    <td>✅ Project Reports</td>
                    <td>✅ Own Reports</td>
                </tr>
                <tr>
                    <td><strong>System Settings</strong></td>
                    <td>✅ Full Control</td>
                    <td>❌ No Access</td>
                    <td>❌ No Access</td>
                    <td>❌ No Access</td>
                    <td>❌ No Access</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Test Dashboard Access -->
    <div class="dashboard-section">
        <h2>🧪 Test Dashboard Access</h2>
        <p>Use the buttons below to test different dashboard views (your permissions may restrict access):</p>
        
        <div style="text-align: center; margin: 20px 0;">
            <a href="?role_test=wfp_super_admin" class="btn" style="background: #dc3545;">Test Super Admin View</a>
            <a href="?role_test=wfp_managing_head" class="btn" style="background: #fd7e14;">Test Managing Head View</a>
            <a href="?role_test=wfp_hr_manager" class="btn" style="background: #20c997;">Test HR Manager View</a>
            <a href="?role_test=wfp_project_admin" class="btn" style="background: #0d6efd;">Test Project Admin View</a>
            <a href="?role_test=wfp_employee" class="btn" style="background: #6f42c1;">Test Employee View</a>
        </div>
        
        <?php if (isset($_GET['role_test'])): ?>
            <div style="margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 5px;">
                <h3>🧪 Dashboard Data Test for <?php echo esc_html($_GET['role_test']); ?>:</h3>
                <?php
                $test_role = sanitize_text_field($_GET['role_test']);
                $user_id = get_current_user_id();
                
                switch ($test_role) {
                    case 'wfp_super_admin':
                    case 'wfp_managing_head':
                        echo "<strong>Manager Dashboard Data:</strong><br>";
                        $data = array(
                            'total_employees' => 'Function: WorkFluxPro_User_Management::get_total_employees()',
                            'active_projects' => 'Function: WorkFluxPro_Project_Management::get_active_projects_count()',
                            'pending_leaves' => 'Function: WorkFluxPro_Leave_Management::get_pending_requests_count()',
                            'pending_external_duties' => 'Function: WorkFluxPro_External_Duty::get_pending_requests_count()',
                            'today_attendance' => 'Function: WorkFluxPro_Time_Tracking::get_today_attendance()',
                            'recent_activities' => 'Function: WorkFluxPro_Reports::get_recent_activities()'
                        );
                        break;
                    case 'wfp_hr_manager':
                        echo "<strong>HR Dashboard Data:</strong><br>";
                        $data = array(
                            'new_employees_this_month' => 'Function: WorkFluxPro_User_Management::get_new_employees_count()',
                            'pending_leaves' => 'Function: WorkFluxPro_Leave_Management::get_pending_requests_count()',
                            'approved_leaves_this_month' => 'Function: WorkFluxPro_Leave_Management::get_approved_requests_count()',
                            'employee_birthdays' => 'Function: WorkFluxPro_User_Management::get_upcoming_birthdays()',
                            'work_anniversaries' => 'Function: WorkFluxPro_User_Management::get_upcoming_anniversaries()'
                        );
                        break;
                    case 'wfp_project_admin':
                        echo "<strong>Project Admin Dashboard Data:</strong><br>";
                        $data = array(
                            'my_projects' => 'Function: WorkFluxPro_Project_Management::get_user_projects()',
                            'pending_tasks' => 'Function: WorkFluxPro_Project_Management::get_pending_tasks()',
                            'overdue_tasks' => 'Function: WorkFluxPro_Project_Management::get_overdue_tasks()',
                            'project_progress' => 'Function: WorkFluxPro_Project_Management::get_projects_progress()'
                        );
                        break;
                    case 'wfp_employee':
                        echo "<strong>Employee Dashboard Data:</strong><br>";
                        $data = array(
                            'clock_status' => 'Function: WorkFluxPro_Time_Tracking::get_current_status()',
                            'today_hours' => 'Function: WorkFluxPro_Time_Tracking::get_today_hours()',
                            'assigned_projects' => 'Function: WorkFluxPro_Project_Management::get_assigned_projects()',
                            'my_tasks' => 'Function: WorkFluxPro_Project_Management::get_user_tasks()',
                            'leave_balance' => 'Function: WorkFluxPro_Leave_Management::get_leave_balance()',
                            'recent_leaves' => 'Function: WorkFluxPro_Leave_Management::get_user_requests()'
                        );
                        break;
                }
                
                if (isset($data)) {
                    echo "<ul>";
                    foreach ($data as $key => $description) {
                        echo "<li><strong>" . esc_html($key) . ":</strong> " . esc_html($description) . "</li>";
                    }
                    echo "</ul>";
                }
                ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Quick Actions -->
    <div class="dashboard-section">
        <h2>🚀 Quick Actions & Links</h2>
        <div style="text-align: center;">
            <a href="<?php echo admin_url('admin.php?page=workflux-pro'); ?>" class="btn">🏠 Main Dashboard</a>
            <a href="ajax-debug.php" class="btn">🔧 AJAX Debug Tool</a>
            <a href="activation-check.php" class="btn">✅ System Check</a>
            <a href="leave-test-tool.php" class="btn">📋 Leave Testing</a>
            <a href="<?php echo admin_url('users.php'); ?>" class="btn">👥 Users</a>
        </div>
    </div>
    
</body>
</html>