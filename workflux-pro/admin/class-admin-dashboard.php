<?php
/**
 * Admin dashboard class
 *
 * @package WorkFluxPro
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WorkFlux Pro Admin Dashboard Class
 */
class WorkFluxPro_Admin_Dashboard {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Constructor can be empty for now
    }
    
    /**
     * Render dashboard
     */
    public function render() {
        $user_id = get_current_user_id();
        $user_role = WorkFluxPro_Roles::get_user_workflux_role($user_id);
        
        ?>
        <div class="wfp-dashboard">
            <?php $this->render_dashboard_widgets($user_role, $user_id); ?>
            <?php $this->render_recent_activities($user_id); ?>
            <?php $this->render_quick_actions($user_role); ?>
        </div>
        <?php
    }
    
    /**
     * Render dashboard widgets based on user role
     *
     * @param string $user_role
     * @param int $user_id
     */
    private function render_dashboard_widgets($user_role, $user_id) {
        ?>
        <div class="wfp-dashboard-widgets">
            <?php
            switch ($user_role) {
                case 'wfp_super_admin':
                case 'wfp_managing_head':
                    $this->render_manager_widgets($user_id);
                    break;
                case 'wfp_hr_manager':
                    $this->render_hr_widgets($user_id);
                    break;
                case 'wfp_project_admin':
                    $this->render_project_admin_widgets($user_id);
                    break;
                case 'wfp_employee':
                    $this->render_employee_widgets($user_id);
                    break;
                default:
                    $this->render_default_widgets($user_id);
                    break;
            }
            ?>
        </div>
        <?php
    }
    
    /**
     * Render manager widgets
     *
     * @param int $user_id
     */
    private function render_manager_widgets($user_id) {
        $total_employees = WorkFluxPro_User_Management::get_total_employees();
        $active_projects = WorkFluxPro_Project_Management::get_active_projects_count();
        $pending_leaves = WorkFluxPro_Leave_Management::get_pending_requests_count();
        $pending_external_duties = WorkFluxPro_External_Duty::get_pending_requests_count();
        $today_attendance = WorkFluxPro_Time_Tracking::get_today_attendance();
        
        ?>
        <div class="wfp-widget-row">
            <div class="wfp-widget wfp-widget-employees">
                <div class="wfp-widget-header">
                    <h3><?php _e('Total Employees', 'workflux-pro'); ?></h3>
                    <span class="wfp-widget-icon dashicons dashicons-groups"></span>
                </div>
                <div class="wfp-widget-content">
                    <div class="wfp-widget-number"><?php echo number_format($total_employees); ?></div>
                    <div class="wfp-widget-label"><?php _e('Active Employees', 'workflux-pro'); ?></div>
                </div>
            </div>
            
            <div class="wfp-widget wfp-widget-projects">
                <div class="wfp-widget-header">
                    <h3><?php _e('Active Projects', 'workflux-pro'); ?></h3>
                    <span class="wfp-widget-icon dashicons dashicons-portfolio"></span>
                </div>
                <div class="wfp-widget-content">
                    <div class="wfp-widget-number"><?php echo number_format($active_projects); ?></div>
                    <div class="wfp-widget-label"><?php _e('In Progress', 'workflux-pro'); ?></div>
                </div>
            </div>
            
            <div class="wfp-widget wfp-widget-attendance">
                <div class="wfp-widget-header">
                    <h3><?php _e('Today\'s Attendance', 'workflux-pro'); ?></h3>
                    <span class="wfp-widget-icon dashicons dashicons-clock"></span>
                </div>
                <div class="wfp-widget-content">
                    <div class="wfp-widget-number"><?php echo number_format($today_attendance); ?></div>
                    <div class="wfp-widget-label"><?php _e('Employees Present', 'workflux-pro'); ?></div>
                </div>
            </div>
            
            <div class="wfp-widget wfp-widget-pending">
                <div class="wfp-widget-header">
                    <h3><?php _e('Pending Approvals', 'workflux-pro'); ?></h3>
                    <span class="wfp-widget-icon dashicons dashicons-warning"></span>
                </div>
                <div class="wfp-widget-content">
                    <div class="wfp-widget-number"><?php echo number_format($pending_leaves + $pending_external_duties); ?></div>
                    <div class="wfp-widget-label">
                        <?php printf(__('%d Leaves, %d External Duties', 'workflux-pro'), $pending_leaves, $pending_external_duties); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="wfp-widget-row">
            <div class="wfp-widget wfp-widget-chart">
                <div class="wfp-widget-header">
                    <h3><?php _e('Weekly Attendance Trend', 'workflux-pro'); ?></h3>
                </div>
                <div class="wfp-widget-content">
                    <canvas id="attendanceChart" width="400" height="200"></canvas>
                </div>
            </div>
            
            <div class="wfp-widget wfp-widget-chart">
                <div class="wfp-widget-header">
                    <h3><?php _e('Project Status Distribution', 'workflux-pro'); ?></h3>
                </div>
                <div class="wfp-widget-content">
                    <canvas id="projectStatusChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render HR widgets
     *
     * @param int $user_id
     */
    private function render_hr_widgets($user_id) {
        $new_employees = WorkFluxPro_User_Management::get_new_employees_count();
        $pending_leaves = WorkFluxPro_Leave_Management::get_pending_requests_count();
        $approved_leaves = WorkFluxPro_Leave_Management::get_approved_requests_count();
        $upcoming_birthdays = WorkFluxPro_User_Management::get_upcoming_birthdays();
        $work_anniversaries = WorkFluxPro_User_Management::get_upcoming_anniversaries();
        
        ?>
        <div class="wfp-widget-row">
            <div class="wfp-widget wfp-widget-new-employees">
                <div class="wfp-widget-header">
                    <h3><?php _e('New Employees', 'workflux-pro'); ?></h3>
                    <span class="wfp-widget-icon dashicons dashicons-plus-alt"></span>
                </div>
                <div class="wfp-widget-content">
                    <div class="wfp-widget-number"><?php echo number_format($new_employees); ?></div>
                    <div class="wfp-widget-label"><?php _e('This Month', 'workflux-pro'); ?></div>
                </div>
            </div>
            
            <div class="wfp-widget wfp-widget-pending-leaves">
                <div class="wfp-widget-header">
                    <h3><?php _e('Pending Leaves', 'workflux-pro'); ?></h3>
                    <span class="wfp-widget-icon dashicons dashicons-calendar-alt"></span>
                </div>
                <div class="wfp-widget-content">
                    <div class="wfp-widget-number"><?php echo number_format($pending_leaves); ?></div>
                    <div class="wfp-widget-label"><?php _e('Awaiting Approval', 'workflux-pro'); ?></div>
                </div>
            </div>
            
            <div class="wfp-widget wfp-widget-approved-leaves">
                <div class="wfp-widget-header">
                    <h3><?php _e('Approved Leaves', 'workflux-pro'); ?></h3>
                    <span class="wfp-widget-icon dashicons dashicons-yes"></span>
                </div>
                <div class="wfp-widget-content">
                    <div class="wfp-widget-number"><?php echo number_format($approved_leaves); ?></div>
                    <div class="wfp-widget-label"><?php _e('This Month', 'workflux-pro'); ?></div>
                </div>
            </div>
            
            <div class="wfp-widget wfp-widget-anniversaries">
                <div class="wfp-widget-header">
                    <h3><?php _e('Upcoming Events', 'workflux-pro'); ?></h3>
                    <span class="wfp-widget-icon dashicons dashicons-calendar"></span>
                </div>
                <div class="wfp-widget-content">
                    <div class="wfp-widget-number"><?php echo count($work_anniversaries); ?></div>
                    <div class="wfp-widget-label"><?php _e('Work Anniversaries', 'workflux-pro'); ?></div>
                </div>
            </div>
        </div>
        
        <div class="wfp-widget-row">
            <div class="wfp-widget wfp-widget-list">
                <div class="wfp-widget-header">
                    <h3><?php _e('Upcoming Work Anniversaries', 'workflux-pro'); ?></h3>
                </div>
                <div class="wfp-widget-content">
                    <?php if (!empty($work_anniversaries)): ?>
                        <ul class="wfp-anniversary-list">
                            <?php foreach (array_slice($work_anniversaries, 0, 5) as $anniversary): ?>
                                <li>
                                    <strong><?php echo esc_html($anniversary->display_name); ?></strong>
                                    <span><?php printf(__('%d years on %s', 'workflux-pro'), $anniversary->years_of_service, date('M d', strtotime($anniversary->hire_date))); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p><?php _e('No upcoming anniversaries', 'workflux-pro'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render project admin widgets
     *
     * @param int $user_id
     */
    private function render_project_admin_widgets($user_id) {
        $my_projects = WorkFluxPro_Project_Management::get_user_projects($user_id);
        $pending_tasks = WorkFluxPro_Project_Management::get_pending_tasks($user_id);
        $overdue_tasks = WorkFluxPro_Project_Management::get_overdue_tasks($user_id);
        $project_progress = WorkFluxPro_Project_Management::get_projects_progress($user_id);
        
        ?>
        <div class="wfp-widget-row">
            <div class="wfp-widget wfp-widget-my-projects">
                <div class="wfp-widget-header">
                    <h3><?php _e('My Projects', 'workflux-pro'); ?></h3>
                    <span class="wfp-widget-icon dashicons dashicons-portfolio"></span>
                </div>
                <div class="wfp-widget-content">
                    <div class="wfp-widget-number"><?php echo count($my_projects); ?></div>
                    <div class="wfp-widget-label"><?php _e('Active Projects', 'workflux-pro'); ?></div>
                </div>
            </div>
            
            <div class="wfp-widget wfp-widget-pending-tasks">
                <div class="wfp-widget-header">
                    <h3><?php _e('Pending Tasks', 'workflux-pro'); ?></h3>
                    <span class="wfp-widget-icon dashicons dashicons-list-view"></span>
                </div>
                <div class="wfp-widget-content">
                    <div class="wfp-widget-number"><?php echo count($pending_tasks); ?></div>
                    <div class="wfp-widget-label"><?php _e('To Do', 'workflux-pro'); ?></div>
                </div>
            </div>
            
            <div class="wfp-widget wfp-widget-overdue-tasks">
                <div class="wfp-widget-header">
                    <h3><?php _e('Overdue Tasks', 'workflux-pro'); ?></h3>
                    <span class="wfp-widget-icon dashicons dashicons-warning"></span>
                </div>
                <div class="wfp-widget-content">
                    <div class="wfp-widget-number wfp-number-warning"><?php echo count($overdue_tasks); ?></div>
                    <div class="wfp-widget-label"><?php _e('Past Due Date', 'workflux-pro'); ?></div>
                </div>
            </div>
        </div>
        
        <div class="wfp-widget-row">
            <div class="wfp-widget wfp-widget-project-progress">
                <div class="wfp-widget-header">
                    <h3><?php _e('Project Progress', 'workflux-pro'); ?></h3>
                </div>
                <div class="wfp-widget-content">
                    <?php if (!empty($project_progress)): ?>
                        <?php foreach (array_slice($project_progress, 0, 5) as $progress): ?>
                            <div class="wfp-progress-item">
                                <div class="wfp-progress-header">
                                    <span class="wfp-progress-name"><?php echo esc_html($progress['project']->name); ?></span>
                                    <span class="wfp-progress-percentage"><?php echo $progress['progress_percentage']; ?>%</span>
                                </div>
                                <div class="wfp-progress-bar">
                                    <div class="wfp-progress-fill" style="width: <?php echo $progress['progress_percentage']; ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p><?php _e('No projects assigned', 'workflux-pro'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render employee widgets
     *
     * @param int $user_id
     */
    private function render_employee_widgets($user_id) {
        $clock_status = WorkFluxPro_Time_Tracking::get_current_status($user_id);
        $today_hours = WorkFluxPro_Time_Tracking::get_today_hours($user_id);
        $assigned_projects = WorkFluxPro_Project_Management::get_assigned_projects($user_id);
        $my_tasks = WorkFluxPro_Project_Management::get_user_tasks($user_id, array('limit' => 10));
        $leave_balance = WorkFluxPro_Leave_Management::get_leave_balance($user_id);
        
        ?>
        <div class="wfp-widget-row">
            <div class="wfp-widget wfp-widget-clock-status">
                <div class="wfp-widget-header">
                    <h3><?php _e('Clock Status', 'workflux-pro'); ?></h3>
                    <span class="wfp-widget-icon dashicons dashicons-clock"></span>
                </div>
                <div class="wfp-widget-content">
                    <?php if ($clock_status['is_clocked_in']): ?>
                        <div class="wfp-status-active">
                            <div class="wfp-widget-number wfp-number-success"><?php _e('CLOCKED IN', 'workflux-pro'); ?></div>
                            <div class="wfp-widget-label">
                                <?php printf(__('Since %s', 'workflux-pro'), date('H:i', strtotime($clock_status['clocked_in_since']))); ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="wfp-status-inactive">
                            <div class="wfp-widget-number wfp-number-secondary"><?php _e('CLOCKED OUT', 'workflux-pro'); ?></div>
                            <div class="wfp-widget-label"><?php _e('Not currently working', 'workflux-pro'); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="wfp-widget wfp-widget-today-hours">
                <div class="wfp-widget-header">
                    <h3><?php _e('Today\'s Hours', 'workflux-pro'); ?></h3>
                    <span class="wfp-widget-icon dashicons dashicons-chart-line"></span>
                </div>
                <div class="wfp-widget-content">
                    <div class="wfp-widget-number"><?php echo number_format($today_hours, 2); ?></div>
                    <div class="wfp-widget-label"><?php _e('Hours Worked', 'workflux-pro'); ?></div>
                </div>
            </div>
            
            <div class="wfp-widget wfp-widget-assigned-projects">
                <div class="wfp-widget-header">
                    <h3><?php _e('Assigned Projects', 'workflux-pro'); ?></h3>
                    <span class="wfp-widget-icon dashicons dashicons-portfolio"></span>
                </div>
                <div class="wfp-widget-content">
                    <div class="wfp-widget-number"><?php echo count($assigned_projects); ?></div>
                    <div class="wfp-widget-label"><?php _e('Active Projects', 'workflux-pro'); ?></div>
                </div>
            </div>
            
            <div class="wfp-widget wfp-widget-my-tasks">
                <div class="wfp-widget-header">
                    <h3><?php _e('My Tasks', 'workflux-pro'); ?></h3>
                    <span class="wfp-widget-icon dashicons dashicons-list-view"></span>
                </div>
                <div class="wfp-widget-content">
                    <div class="wfp-widget-number"><?php echo count($my_tasks); ?></div>
                    <div class="wfp-widget-label"><?php _e('Assigned Tasks', 'workflux-pro'); ?></div>
                </div>
            </div>
        </div>
        
        <div class="wfp-widget-row">
            <div class="wfp-widget wfp-widget-leave-balance">
                <div class="wfp-widget-header">
                    <h3><?php _e('Leave Balance', 'workflux-pro'); ?></h3>
                </div>
                <div class="wfp-widget-content">
                    <?php if (!empty($leave_balance)): ?>
                        <div class="wfp-leave-balance-grid">
                            <?php foreach ($leave_balance as $type => $balance): ?>
                                <div class="wfp-leave-balance-item">
                                    <div class="wfp-leave-type"><?php echo esc_html($balance['label']); ?></div>
                                    <div class="wfp-leave-remaining"><?php echo $balance['remaining']; ?></div>
                                    <div class="wfp-leave-total"><?php printf(__('of %d days', 'workflux-pro'), $balance['total']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p><?php _e('No leave information available', 'workflux-pro'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="wfp-widget wfp-widget-recent-tasks">
                <div class="wfp-widget-header">
                    <h3><?php _e('Recent Tasks', 'workflux-pro'); ?></h3>
                </div>
                <div class="wfp-widget-content">
                    <?php if (!empty($my_tasks)): ?>
                        <ul class="wfp-task-list">
                            <?php foreach (array_slice($my_tasks, 0, 5) as $task): ?>
                                <li class="wfp-task-item">
                                    <div class="wfp-task-title"><?php echo esc_html($task->title); ?></div>
                                    <div class="wfp-task-project"><?php echo esc_html($task->project_name); ?></div>
                                    <div class="wfp-task-status">
                                        <?php WorkFluxPro_Admin::render_status_badge($task->status, WorkFluxPro_Project_Management::get_task_statuses()); ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p><?php _e('No tasks assigned', 'workflux-pro'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render default widgets for users without specific roles
     *
     * @param int $user_id
     */
    private function render_default_widgets($user_id) {
        ?>
        <div class="wfp-widget-row">
            <div class="wfp-widget wfp-widget-welcome">
                <div class="wfp-widget-header">
                    <h3><?php _e('Welcome to WorkFlux Pro', 'workflux-pro'); ?></h3>
                </div>
                <div class="wfp-widget-content">
                    <p><?php _e('Please contact your administrator to assign you a role in WorkFlux Pro to access the features.', 'workflux-pro'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render recent activities
     *
     * @param int $user_id
     */
    private function render_recent_activities($user_id) {
        $activities = WorkFluxPro_Reports::get_recent_activities($user_id, 10);
        
        ?>
        <div class="wfp-recent-activities">
            <h3><?php _e('Recent Activities', 'workflux-pro'); ?></h3>
            <?php if (!empty($activities)): ?>
                <ul class="wfp-activities-list">
                    <?php foreach ($activities as $activity): ?>
                        <li class="wfp-activity-item">
                            <span class="wfp-activity-icon dashicons dashicons-<?php echo esc_attr($activity['icon']); ?>"></span>
                            <div class="wfp-activity-content">
                                <div class="wfp-activity-description"><?php echo esc_html($activity['description']); ?></div>
                                <div class="wfp-activity-time"><?php echo human_time_diff(strtotime($activity['time']), current_time('timestamp')); ?> <?php _e('ago', 'workflux-pro'); ?></div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="wfp-activities-footer">
                    <a href="<?php echo admin_url('admin.php?page=wfp-reports'); ?>" class="button">
                        <?php _e('View All Reports', 'workflux-pro'); ?>
                    </a>
                </div>
            <?php else: ?>
                <p><?php _e('No recent activities found.', 'workflux-pro'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render quick actions based on user role
     *
     * @param string $user_role
     */
    private function render_quick_actions($user_role) {
        ?>
        <div class="wfp-quick-actions">
            <h3><?php _e('Quick Actions', 'workflux-pro'); ?></h3>
            <div class="wfp-actions-grid">
                <?php
                switch ($user_role) {
                    case 'wfp_super_admin':
                    case 'wfp_managing_head':
                        $this->render_manager_actions();
                        break;
                    case 'wfp_hr_manager':
                        $this->render_hr_actions();
                        break;
                    case 'wfp_project_admin':
                        $this->render_project_admin_actions();
                        break;
                    case 'wfp_employee':
                        $this->render_employee_actions();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render manager quick actions
     */
    private function render_manager_actions() {
        ?>
        <a href="<?php echo admin_url('admin.php?page=wfp-employees&action=add'); ?>" class="wfp-quick-action">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php _e('Add Employee', 'workflux-pro'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=wfp-projects&action=add'); ?>" class="wfp-quick-action">
            <span class="dashicons dashicons-portfolio"></span>
            <?php _e('Create Project', 'workflux-pro'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=wfp-reports'); ?>" class="wfp-quick-action">
            <span class="dashicons dashicons-chart-bar"></span>
            <?php _e('Generate Report', 'workflux-pro'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=wfp-leave-management'); ?>" class="wfp-quick-action">
            <span class="dashicons dashicons-calendar-alt"></span>
            <?php _e('Review Leaves', 'workflux-pro'); ?>
        </a>
        <?php
    }
    
    /**
     * Render HR quick actions
     */
    private function render_hr_actions() {
        ?>
        <a href="<?php echo admin_url('admin.php?page=wfp-employees&action=add'); ?>" class="wfp-quick-action">
            <span class="dashicons dashicons-admin-users"></span>
            <?php _e('Onboard Employee', 'workflux-pro'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=wfp-leave-management'); ?>" class="wfp-quick-action">
            <span class="dashicons dashicons-calendar-alt"></span>
            <?php _e('Manage Leaves', 'workflux-pro'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=wfp-reports'); ?>" class="wfp-quick-action">
            <span class="dashicons dashicons-chart-pie"></span>
            <?php _e('HR Reports', 'workflux-pro'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=wfp-employees'); ?>" class="wfp-quick-action">
            <span class="dashicons dashicons-groups"></span>
            <?php _e('View Employees', 'workflux-pro'); ?>
        </a>
        <?php
    }
    
    /**
     * Render project admin quick actions
     */
    private function render_project_admin_actions() {
        ?>
        <a href="<?php echo admin_url('admin.php?page=wfp-projects&action=add'); ?>" class="wfp-quick-action">
            <span class="dashicons dashicons-portfolio"></span>
            <?php _e('Create Project', 'workflux-pro'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=wfp-projects'); ?>" class="wfp-quick-action">
            <span class="dashicons dashicons-list-view"></span>
            <?php _e('Manage Tasks', 'workflux-pro'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=wfp-time-tracking'); ?>" class="wfp-quick-action">
            <span class="dashicons dashicons-clock"></span>
            <?php _e('Track Time', 'workflux-pro'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=wfp-reports'); ?>" class="wfp-quick-action">
            <span class="dashicons dashicons-chart-line"></span>
            <?php _e('Project Reports', 'workflux-pro'); ?>
        </a>
        <?php
    }
    
    /**
     * Render employee quick actions
     */
    private function render_employee_actions() {
        ?>
        <a href="<?php echo admin_url('admin.php?page=wfp-time-tracking'); ?>" class="wfp-quick-action">
            <span class="dashicons dashicons-clock"></span>
            <?php _e('Clock In/Out', 'workflux-pro'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=wfp-leave-management'); ?>" class="wfp-quick-action">
            <span class="dashicons dashicons-calendar-alt"></span>
            <?php _e('Request Leave', 'workflux-pro'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=wfp-external-duty'); ?>" class="wfp-quick-action">
            <span class="dashicons dashicons-location"></span>
            <?php _e('External Duty', 'workflux-pro'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=wfp-projects'); ?>" class="wfp-quick-action">
            <span class="dashicons dashicons-portfolio"></span>
            <?php _e('My Projects', 'workflux-pro'); ?>
        </a>
        <?php
    }
}