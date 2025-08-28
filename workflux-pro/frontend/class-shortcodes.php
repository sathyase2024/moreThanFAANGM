<?php
/**
 * Shortcodes class
 *
 * @package WorkFluxPro
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WorkFlux Pro Shortcodes Class
 */
class WorkFluxPro_Shortcodes {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_shortcodes'));
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('workflux_pro_dashboard', array($this, 'dashboard_shortcode'));
        add_shortcode('workflux_pro_time_tracker', array($this, 'time_tracker_shortcode'));
        add_shortcode('workflux_pro_project_list', array($this, 'project_list_shortcode'));
        add_shortcode('workflux_pro_leave_form', array($this, 'leave_form_shortcode'));
        add_shortcode('workflux_pro_profile', array($this, 'profile_shortcode'));
        add_shortcode('workflux_pro_login', array($this, 'login_shortcode'));
    }
    
    /**
     * Dashboard shortcode
     *
     * @param array $atts
     * @return string
     */
    public function dashboard_shortcode($atts) {
        $atts = shortcode_atts(array(
            'user_role' => '',
            'show_widgets' => 'true',
            'show_activities' => 'true'
        ), $atts);
        
        if (!is_user_logged_in()) {
            return $this->render_login_required_message();
        }
        
        $user_role = WorkFluxPro_Roles::get_user_workflux_role();
        if (!$user_role) {
            return '<div class="wfp-error">' . __('You do not have a WorkFlux Pro role assigned.', 'workflux-pro') . '</div>';
        }
        
        // Filter by role if specified
        if (!empty($atts['user_role']) && $user_role !== $atts['user_role']) {
            return '<div class="wfp-error">' . __('Access denied for your role.', 'workflux-pro') . '</div>';
        }
        
        ob_start();
        ?>
        <div class="wfp-shortcode-dashboard">
            <?php if ($atts['show_widgets'] === 'true'): ?>
                <?php $this->render_dashboard_widgets(); ?>
            <?php endif; ?>
            
            <?php if ($atts['show_activities'] === 'true'): ?>
                <?php $this->render_recent_activities(); ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Time tracker shortcode
     *
     * @param array $atts
     * @return string
     */
    public function time_tracker_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_summary' => 'true',
            'show_project_selector' => 'true'
        ), $atts);
        
        if (!is_user_logged_in()) {
            return $this->render_login_required_message();
        }
        
        if (!WorkFluxPro_Roles::user_can('wfp_clock_in_out')) {
            return '<div class="wfp-error">' . __('Permission denied.', 'workflux-pro') . '</div>';
        }
        
        $user_id = get_current_user_id();
        $clock_status = WorkFluxPro_Time_Tracking::get_current_status($user_id);
        
        ob_start();
        ?>
        <div class="wfp-shortcode-time-tracker">
            <div class="wfp-time-tracker-controls">
                <?php if (!$clock_status['is_clocked_in']): ?>
                    <button type="button" class="wfp-btn wfp-btn-primary wfp-btn-large" id="wfp-clock-in-btn">
                        <span class="dashicons dashicons-clock"></span>
                        <?php _e('Clock In', 'workflux-pro'); ?>
                    </button>
                <?php else: ?>
                    <div class="wfp-clocked-in-status">
                        <div class="wfp-status-info">
                            <span class="wfp-status-indicator active"></span>
                            <div class="wfp-status-text">
                                <strong><?php _e('Clocked In', 'workflux-pro'); ?></strong>
                                <p><?php printf(__('Since: %s', 'workflux-pro'), date('H:i', strtotime($clock_status['clocked_in_since']))); ?></p>
                            </div>
                        </div>
                        <button type="button" class="wfp-btn wfp-btn-secondary" id="wfp-clock-out-btn">
                            <?php _e('Clock Out', 'workflux-pro'); ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($atts['show_project_selector'] === 'true' && $clock_status['is_clocked_in']): ?>
                <div class="wfp-project-selector">
                    <h4><?php _e('Start Working on a Project', 'workflux-pro'); ?></h4>
                    <?php $this->render_project_selector(); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($atts['show_summary'] === 'true'): ?>
                <div class="wfp-time-summary">
                    <?php $this->render_time_summary($user_id); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Project list shortcode
     *
     * @param array $atts
     * @return string
     */
    public function project_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => '10',
            'show_tasks' => 'false',
            'status' => ''
        ), $atts);
        
        if (!is_user_logged_in()) {
            return $this->render_login_required_message();
        }
        
        $user_id = get_current_user_id();
        $projects = WorkFluxPro_Project_Management::get_assigned_projects($user_id);
        
        if (!empty($atts['status'])) {
            $projects = array_filter($projects, function($project) use ($atts) {
                return $project->status === $atts['status'];
            });
        }
        
        $projects = array_slice($projects, 0, intval($atts['limit']));
        
        ob_start();
        ?>
        <div class="wfp-shortcode-project-list">
            <?php if (!empty($projects)): ?>
                <div class="wfp-projects-grid">
                    <?php foreach ($projects as $project): ?>
                        <div class="wfp-project-card">
                            <div class="wfp-project-header">
                                <h3><?php echo esc_html($project->name); ?></h3>
                                <span class="wfp-status-badge wfp-status-<?php echo esc_attr($project->status); ?>">
                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $project->status))); ?>
                                </span>
                            </div>
                            <div class="wfp-project-content">
                                <p><?php echo esc_html(wp_trim_words($project->description, 20)); ?></p>
                                <div class="wfp-project-meta">
                                    <span class="wfp-project-code"><?php echo esc_html($project->project_code); ?></span>
                                    <span class="wfp-project-role"><?php echo esc_html($project->role); ?></span>
                                </div>
                            </div>
                            
                            <?php if ($atts['show_tasks'] === 'true'): ?>
                                <?php $this->render_project_tasks($project->id); ?>
                            <?php endif; ?>
                            
                            <div class="wfp-project-actions">
                                <button type="button" class="wfp-btn wfp-btn-primary wfp-start-project" 
                                        data-project-id="<?php echo esc_attr($project->id); ?>">
                                    <?php _e('Start Working', 'workflux-pro'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="wfp-no-projects">
                    <p><?php _e('No projects found.', 'workflux-pro'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Leave form shortcode
     *
     * @param array $atts
     * @return string
     */
    public function leave_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_balance' => 'true',
            'redirect_url' => ''
        ), $atts);
        
        if (!is_user_logged_in()) {
            return $this->render_login_required_message();
        }
        
        if (!WorkFluxPro_Roles::user_can('wfp_submit_leave_requests')) {
            return '<div class="wfp-error">' . __('Permission denied.', 'workflux-pro') . '</div>';
        }
        
        $user_id = get_current_user_id();
        $leave_balance = WorkFluxPro_Leave_Management::get_leave_balance($user_id);
        
        ob_start();
        ?>
        <div class="wfp-shortcode-leave-form">
            <?php if ($atts['show_balance'] === 'true' && !empty($leave_balance)): ?>
                <div class="wfp-leave-balance-summary">
                    <h4><?php _e('Your Leave Balance', 'workflux-pro'); ?></h4>
                    <div class="wfp-balance-grid">
                        <?php foreach ($leave_balance as $type => $balance): ?>
                            <div class="wfp-balance-item">
                                <span class="wfp-balance-type"><?php echo esc_html($balance['label']); ?></span>
                                <span class="wfp-balance-days"><?php echo $balance['remaining']; ?> / <?php echo $balance['total']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <form id="wfp-leave-request-form" class="wfp-form">
                <div class="wfp-form-row">
                    <div class="wfp-form-group">
                        <label for="leave_type"><?php _e('Leave Type', 'workflux-pro'); ?> *</label>
                        <select name="leave_type" id="leave_type" class="wfp-form-control" required>
                            <option value=""><?php _e('Select Leave Type', 'workflux-pro'); ?></option>
                            <?php foreach (WorkFluxPro_Leave_Management::get_leave_types() as $type => $label): ?>
                                <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="wfp-form-row">
                    <div class="wfp-form-group">
                        <label for="start_date"><?php _e('Start Date', 'workflux-pro'); ?> *</label>
                        <input type="date" name="start_date" id="start_date" class="wfp-form-control" required>
                    </div>
                    <div class="wfp-form-group">
                        <label for="end_date"><?php _e('End Date', 'workflux-pro'); ?> *</label>
                        <input type="date" name="end_date" id="end_date" class="wfp-form-control" required>
                    </div>
                </div>
                
                <div class="wfp-form-group">
                    <label for="reason"><?php _e('Reason', 'workflux-pro'); ?></label>
                    <textarea name="reason" id="reason" class="wfp-form-control" rows="4" 
                              placeholder="<?php _e('Please provide a reason for your leave request...', 'workflux-pro'); ?>"></textarea>
                </div>
                
                <div class="wfp-form-actions">
                    <button type="submit" class="wfp-btn wfp-btn-primary">
                        <?php _e('Submit Leave Request', 'workflux-pro'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Profile shortcode
     *
     * @param array $atts
     * @return string
     */
    public function profile_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_avatar' => 'true',
            'show_leave_balance' => 'true',
            'show_stats' => 'true'
        ), $atts);
        
        if (!is_user_logged_in()) {
            return $this->render_login_required_message();
        }
        
        $user_id = get_current_user_id();
        $current_user = get_user_by('id', $user_id);
        $employee = WorkFluxPro_User_Management::get_employee_by_user_id($user_id);
        
        ob_start();
        ?>
        <div class="wfp-shortcode-profile">
            <div class="wfp-profile-header">
                <?php if ($atts['show_avatar'] === 'true'): ?>
                    <div class="wfp-profile-avatar">
                        <?php echo get_avatar($user_id, 80); ?>
                    </div>
                <?php endif; ?>
                <div class="wfp-profile-info">
                    <h3><?php echo esc_html($current_user->display_name); ?></h3>
                    <p><?php echo esc_html($current_user->user_email); ?></p>
                    <?php if ($employee): ?>
                        <p class="wfp-employee-id"><?php printf(__('ID: %s', 'workflux-pro'), esc_html($employee->employee_id)); ?></p>
                        <?php if ($employee->designation): ?>
                            <p class="wfp-designation"><?php echo esc_html($employee->designation); ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($atts['show_leave_balance'] === 'true'): ?>
                <?php $leave_balance = WorkFluxPro_Leave_Management::get_leave_balance($user_id); ?>
                <?php if (!empty($leave_balance)): ?>
                    <div class="wfp-profile-leave-balance">
                        <h4><?php _e('Leave Balance', 'workflux-pro'); ?></h4>
                        <div class="wfp-balance-cards">
                            <?php foreach ($leave_balance as $type => $balance): ?>
                                <div class="wfp-balance-card">
                                    <div class="wfp-balance-type"><?php echo esc_html($balance['label']); ?></div>
                                    <div class="wfp-balance-remaining"><?php echo $balance['remaining']; ?></div>
                                    <div class="wfp-balance-total"><?php printf(__('of %d', 'workflux-pro'), $balance['total']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if ($atts['show_stats'] === 'true'): ?>
                <div class="wfp-profile-stats">
                    <h4><?php _e('Quick Stats', 'workflux-pro'); ?></h4>
                    <div class="wfp-stats-grid">
                        <?php
                        $today_hours = WorkFluxPro_Time_Tracking::get_today_hours($user_id);
                        $assigned_projects = WorkFluxPro_Project_Management::get_assigned_projects($user_id);
                        $my_tasks = WorkFluxPro_Project_Management::get_user_tasks($user_id);
                        ?>
                        <div class="wfp-stat-item">
                            <span class="wfp-stat-value"><?php echo number_format($today_hours, 1); ?></span>
                            <span class="wfp-stat-label"><?php _e('Hours Today', 'workflux-pro'); ?></span>
                        </div>
                        <div class="wfp-stat-item">
                            <span class="wfp-stat-value"><?php echo count($assigned_projects); ?></span>
                            <span class="wfp-stat-label"><?php _e('Active Projects', 'workflux-pro'); ?></span>
                        </div>
                        <div class="wfp-stat-item">
                            <span class="wfp-stat-value"><?php echo count($my_tasks); ?></span>
                            <span class="wfp-stat-label"><?php _e('Assigned Tasks', 'workflux-pro'); ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Login shortcode
     *
     * @param array $atts
     * @return string
     */
    public function login_shortcode($atts) {
        $atts = shortcode_atts(array(
            'redirect' => '',
            'show_register_link' => 'false'
        ), $atts);
        
        if (is_user_logged_in()) {
            return '<div class="wfp-info">' . __('You are already logged in.', 'workflux-pro') . '</div>';
        }
        
        $redirect_url = !empty($atts['redirect']) ? esc_url($atts['redirect']) : '';
        
        ob_start();
        ?>
        <div class="wfp-shortcode-login">
            <form id="wfp-login-form" class="wfp-login-form">
                <div class="wfp-form-group">
                    <label for="user_login"><?php _e('Username or Email', 'workflux-pro'); ?></label>
                    <input type="text" name="log" id="user_login" class="wfp-form-control" required>
                </div>
                
                <div class="wfp-form-group">
                    <label for="user_pass"><?php _e('Password', 'workflux-pro'); ?></label>
                    <input type="password" name="pwd" id="user_pass" class="wfp-form-control" required>
                </div>
                
                <div class="wfp-form-group">
                    <label class="wfp-checkbox-label">
                        <input type="checkbox" name="rememberme" value="forever">
                        <?php _e('Remember Me', 'workflux-pro'); ?>
                    </label>
                </div>
                
                <?php if (!empty($redirect_url)): ?>
                    <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_url); ?>">
                <?php endif; ?>
                
                <div class="wfp-form-actions">
                    <button type="submit" class="wfp-btn wfp-btn-primary wfp-btn-block">
                        <?php _e('Log In', 'workflux-pro'); ?>
                    </button>
                </div>
                
                <div class="wfp-form-links">
                    <a href="<?php echo wp_lostpassword_url(); ?>"><?php _e('Forgot Password?', 'workflux-pro'); ?></a>
                    <?php if ($atts['show_register_link'] === 'true' && get_option('users_can_register')): ?>
                        <a href="<?php echo wp_registration_url(); ?>"><?php _e('Register', 'workflux-pro'); ?></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render login required message
     *
     * @return string
     */
    private function render_login_required_message() {
        return sprintf(
            '<div class="wfp-login-required">%s <a href="%s">%s</a></div>',
            __('Please log in to view this content.', 'workflux-pro'),
            wp_login_url(get_permalink()),
            __('Log In', 'workflux-pro')
        );
    }
    
    /**
     * Render dashboard widgets
     */
    private function render_dashboard_widgets() {
        $user_id = get_current_user_id();
        $clock_status = WorkFluxPro_Time_Tracking::get_current_status($user_id);
        $today_hours = WorkFluxPro_Time_Tracking::get_today_hours($user_id);
        $assigned_projects = WorkFluxPro_Project_Management::get_assigned_projects($user_id);
        
        ?>
        <div class="wfp-dashboard-widgets">
            <div class="wfp-widget wfp-widget-clock">
                <h4><?php _e('Clock Status', 'workflux-pro'); ?></h4>
                <?php if ($clock_status['is_clocked_in']): ?>
                    <div class="wfp-status-active">
                        <span class="wfp-status-indicator active"></span>
                        <span><?php _e('Clocked In', 'workflux-pro'); ?></span>
                    </div>
                <?php else: ?>
                    <div class="wfp-status-inactive">
                        <span class="wfp-status-indicator inactive"></span>
                        <span><?php _e('Clocked Out', 'workflux-pro'); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="wfp-widget wfp-widget-hours">
                <h4><?php _e('Today\'s Hours', 'workflux-pro'); ?></h4>
                <div class="wfp-hours-display">
                    <span class="wfp-hours-number"><?php echo number_format($today_hours, 2); ?></span>
                </div>
            </div>
            
            <div class="wfp-widget wfp-widget-projects">
                <h4><?php _e('Active Projects', 'workflux-pro'); ?></h4>
                <div class="wfp-projects-count">
                    <span class="wfp-count-number"><?php echo count($assigned_projects); ?></span>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render recent activities
     */
    private function render_recent_activities() {
        $user_id = get_current_user_id();
        $activities = WorkFluxPro_Reports::get_recent_activities($user_id, 5);
        
        if (empty($activities)) {
            return;
        }
        
        ?>
        <div class="wfp-recent-activities">
            <h4><?php _e('Recent Activities', 'workflux-pro'); ?></h4>
            <ul class="wfp-activities-list">
                <?php foreach ($activities as $activity): ?>
                    <li class="wfp-activity-item">
                        <span class="wfp-activity-description"><?php echo esc_html($activity['description']); ?></span>
                        <span class="wfp-activity-time"><?php echo human_time_diff(strtotime($activity['time']), current_time('timestamp')); ?> <?php _e('ago', 'workflux-pro'); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }
    
    /**
     * Render project selector
     */
    private function render_project_selector() {
        $user_id = get_current_user_id();
        $projects = WorkFluxPro_Project_Management::get_assigned_projects($user_id);
        
        if (empty($projects)) {
            echo '<p>' . __('No projects assigned.', 'workflux-pro') . '</p>';
            return;
        }
        
        ?>
        <select id="wfp-project-select" class="wfp-form-control">
            <option value=""><?php _e('Select a project...', 'workflux-pro'); ?></option>
            <?php foreach ($projects as $project): ?>
                <option value="<?php echo esc_attr($project->id); ?>">
                    <?php echo esc_html($project->name . ' (' . $project->project_code . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="button" class="wfp-btn wfp-btn-primary" id="wfp-start-project-work">
            <?php _e('Start Working', 'workflux-pro'); ?>
        </button>
        <?php
    }
    
    /**
     * Render time summary
     *
     * @param int $user_id
     */
    private function render_time_summary($user_id) {
        $today_hours = WorkFluxPro_Time_Tracking::get_today_hours($user_id);
        $weekly_hours = WorkFluxPro_Time_Tracking::get_weekly_hours($user_id);
        
        ?>
        <div class="wfp-time-summary-grid">
            <div class="wfp-summary-item">
                <span class="wfp-summary-label"><?php _e('Today', 'workflux-pro'); ?></span>
                <span class="wfp-summary-value"><?php echo number_format($today_hours, 2); ?>h</span>
            </div>
            <div class="wfp-summary-item">
                <span class="wfp-summary-label"><?php _e('This Week', 'workflux-pro'); ?></span>
                <span class="wfp-summary-value"><?php echo number_format($weekly_hours['total_hours'], 2); ?>h</span>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render project tasks
     *
     * @param int $project_id
     */
    private function render_project_tasks($project_id) {
        $tasks = WorkFluxPro_Project_Management::get_project_tasks($project_id, array('limit' => 5));
        
        if (empty($tasks)) {
            return;
        }
        
        ?>
        <div class="wfp-project-tasks">
            <h5><?php _e('Recent Tasks', 'workflux-pro'); ?></h5>
            <ul class="wfp-task-list">
                <?php foreach ($tasks as $task): ?>
                    <li class="wfp-task-item">
                        <span class="wfp-task-title"><?php echo esc_html($task->title); ?></span>
                        <span class="wfp-status-badge wfp-status-<?php echo esc_attr($task->status); ?>">
                            <?php echo esc_html(ucfirst(str_replace('_', ' ', $task->status))); ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }
}