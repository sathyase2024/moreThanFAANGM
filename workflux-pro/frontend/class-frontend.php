<?php
/**
 * Frontend main class
 *
 * @package WorkFluxPro
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WorkFlux Pro Frontend Class
 */
class WorkFluxPro_Frontend {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_head', array($this, 'add_custom_css'));
        add_action('init', array($this, 'add_rewrite_rules'));
        add_action('template_redirect', array($this, 'handle_frontend_pages'));
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        // Only load on pages that need WorkFlux Pro functionality
        if (!$this->should_load_scripts()) {
            return;
        }
        
        // Enqueue styles
        wp_enqueue_style(
            'workflux-pro-frontend',
            WORKFLUX_PRO_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            WORKFLUX_PRO_VERSION
        );
        
        // Enqueue frontend JavaScript
        wp_enqueue_script(
            'workflux-pro-frontend',
            WORKFLUX_PRO_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            WORKFLUX_PRO_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('workflux-pro-frontend', 'workfluxProFrontend', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('workflux_pro_nonce'),
            'currentUser' => get_current_user_id(),
            'userRole' => WorkFluxPro_Roles::get_user_workflux_role(),
            'strings' => array(
                'clockIn' => __('Clock In', 'workflux-pro'),
                'clockOut' => __('Clock Out', 'workflux-pro'),
                'startProject' => __('Start Project', 'workflux-pro'),
                'stopProject' => __('Stop Project', 'workflux-pro'),
                'confirmAction' => __('Are you sure?', 'workflux-pro'),
                'success' => __('Operation completed successfully', 'workflux-pro'),
                'error' => __('An error occurred', 'workflux-pro'),
                'loading' => __('Loading...', 'workflux-pro')
            )
        ));
        
        // Enqueue date picker for forms
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css');
    }
    
    /**
     * Add custom CSS to head
     */
    public function add_custom_css() {
        if (!$this->should_load_scripts()) {
            return;
        }
        
        $custom_css = get_option('workflux_pro_custom_css', '');
        if (!empty($custom_css)) {
            echo '<style type="text/css">' . $custom_css . '</style>';
        }
    }
    
    /**
     * Add rewrite rules for frontend pages
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^workflux-pro/?$',
            'index.php?workflux_page=dashboard',
            'top'
        );
        
        add_rewrite_rule(
            '^workflux-pro/([^/]+)/?$',
            'index.php?workflux_page=$matches[1]',
            'top'
        );
        
        add_rewrite_tag('%workflux_page%', '([^&]+)');
    }
    
    /**
     * Handle frontend page requests
     */
    public function handle_frontend_pages() {
        $workflux_page = get_query_var('workflux_page');
        
        if (!$workflux_page) {
            return;
        }
        
        // Check if user is logged in and has WorkFlux role
        if (!is_user_logged_in()) {
            wp_redirect(wp_login_url(home_url('/workflux-pro/')));
            exit;
        }
        
        $user_role = WorkFluxPro_Roles::get_user_workflux_role();
        if (!$user_role) {
            wp_die(__('You do not have permission to access this page.', 'workflux-pro'));
        }
        
        // Route to appropriate page
        switch ($workflux_page) {
            case 'dashboard':
                $this->render_dashboard_page();
                break;
            case 'time-tracking':
                $this->render_time_tracking_page();
                break;
            case 'projects':
                $this->render_projects_page();
                break;
            case 'profile':
                $this->render_profile_page();
                break;
            default:
                wp_redirect(home_url('/workflux-pro/'));
                exit;
        }
        
        exit;
    }
    
    /**
     * Check if scripts should be loaded
     *
     * @return bool
     */
    private function should_load_scripts() {
        // Load on WorkFlux Pro frontend pages
        if (get_query_var('workflux_page')) {
            return true;
        }
        
        // Load on pages with WorkFlux Pro shortcodes
        global $post;
        if ($post && has_shortcode($post->post_content, 'workflux_pro_dashboard')) {
            return true;
        }
        
        if ($post && has_shortcode($post->post_content, 'workflux_pro_time_tracker')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Render dashboard page
     */
    private function render_dashboard_page() {
        get_header();
        ?>
        <div class="wfp-frontend-wrapper">
            <div class="wfp-container">
                <?php $this->render_navigation(); ?>
                
                <div class="wfp-dashboard-content">
                    <h1 class="wfp-page-title"><?php _e('Dashboard', 'workflux-pro'); ?></h1>
                    
                    <?php $this->render_dashboard_widgets(); ?>
                </div>
            </div>
        </div>
        <?php
        get_footer();
    }
    
    /**
     * Render time tracking page
     */
    private function render_time_tracking_page() {
        get_header();
        ?>
        <div class="wfp-frontend-wrapper">
            <div class="wfp-container">
                <?php $this->render_navigation(); ?>
                
                <div class="wfp-time-tracking-content">
                    <h1 class="wfp-page-title"><?php _e('Time Tracking', 'workflux-pro'); ?></h1>
                    
                    <?php $this->render_time_tracking_interface(); ?>
                </div>
            </div>
        </div>
        <?php
        get_footer();
    }
    
    /**
     * Render projects page
     */
    private function render_projects_page() {
        get_header();
        ?>
        <div class="wfp-frontend-wrapper">
            <div class="wfp-container">
                <?php $this->render_navigation(); ?>
                
                <div class="wfp-projects-content">
                    <h1 class="wfp-page-title"><?php _e('My Projects', 'workflux-pro'); ?></h1>
                    
                    <?php $this->render_projects_interface(); ?>
                </div>
            </div>
        </div>
        <?php
        get_footer();
    }
    
    /**
     * Render profile page
     */
    private function render_profile_page() {
        get_header();
        ?>
        <div class="wfp-frontend-wrapper">
            <div class="wfp-container">
                <?php $this->render_navigation(); ?>
                
                <div class="wfp-profile-content">
                    <h1 class="wfp-page-title"><?php _e('My Profile', 'workflux-pro'); ?></h1>
                    
                    <?php $this->render_profile_interface(); ?>
                </div>
            </div>
        </div>
        <?php
        get_footer();
    }
    
    /**
     * Render navigation
     */
    private function render_navigation() {
        $current_user = wp_get_current_user();
        $user_role = WorkFluxPro_Roles::get_user_workflux_role();
        $role_display_name = WorkFluxPro_Roles::get_role_display_name($user_role);
        
        ?>
        <nav class="wfp-frontend-nav">
            <div class="wfp-nav-header">
                <div class="wfp-nav-brand">
                    <h2><?php _e('WorkFlux Pro', 'workflux-pro'); ?></h2>
                </div>
                <div class="wfp-nav-user">
                    <?php echo get_avatar($current_user->ID, 32); ?>
                    <div class="wfp-user-info">
                        <span class="wfp-user-name"><?php echo esc_html($current_user->display_name); ?></span>
                        <span class="wfp-user-role"><?php echo esc_html($role_display_name); ?></span>
                    </div>
                </div>
            </div>
            
            <ul class="wfp-nav-menu">
                <li class="wfp-nav-item">
                    <a href="<?php echo home_url('/workflux-pro/'); ?>" class="wfp-nav-link">
                        <span class="dashicons dashicons-dashboard"></span>
                        <?php _e('Dashboard', 'workflux-pro'); ?>
                    </a>
                </li>
                <li class="wfp-nav-item">
                    <a href="<?php echo home_url('/workflux-pro/time-tracking/'); ?>" class="wfp-nav-link">
                        <span class="dashicons dashicons-clock"></span>
                        <?php _e('Time Tracking', 'workflux-pro'); ?>
                    </a>
                </li>
                <li class="wfp-nav-item">
                    <a href="<?php echo home_url('/workflux-pro/projects/'); ?>" class="wfp-nav-link">
                        <span class="dashicons dashicons-portfolio"></span>
                        <?php _e('Projects', 'workflux-pro'); ?>
                    </a>
                </li>
                <li class="wfp-nav-item">
                    <a href="<?php echo home_url('/workflux-pro/profile/'); ?>" class="wfp-nav-link">
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php _e('Profile', 'workflux-pro'); ?>
                    </a>
                </li>
                <li class="wfp-nav-item">
                    <a href="<?php echo admin_url('admin.php?page=workflux-pro'); ?>" class="wfp-nav-link">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <?php _e('Admin Panel', 'workflux-pro'); ?>
                    </a>
                </li>
                <li class="wfp-nav-item">
                    <a href="<?php echo wp_logout_url(home_url()); ?>" class="wfp-nav-link">
                        <span class="dashicons dashicons-exit"></span>
                        <?php _e('Logout', 'workflux-pro'); ?>
                    </a>
                </li>
            </ul>
        </nav>
        <?php
    }
    
    /**
     * Render dashboard widgets
     */
    private function render_dashboard_widgets() {
        $user_id = get_current_user_id();
        $clock_status = WorkFluxPro_Time_Tracking::get_current_status($user_id);
        $today_hours = WorkFluxPro_Time_Tracking::get_today_hours($user_id);
        $assigned_projects = WorkFluxPro_Project_Management::get_assigned_projects($user_id);
        $my_tasks = WorkFluxPro_Project_Management::get_user_tasks($user_id, array('limit' => 5));
        
        ?>
        <div class="wfp-dashboard-widgets">
            <div class="wfp-widget-row">
                <div class="wfp-widget wfp-widget-clock-status">
                    <div class="wfp-widget-header">
                        <h3><?php _e('Clock Status', 'workflux-pro'); ?></h3>
                    </div>
                    <div class="wfp-widget-content">
                        <?php if ($clock_status['is_clocked_in']): ?>
                            <div class="wfp-status-active">
                                <span class="wfp-status-indicator active"></span>
                                <strong><?php _e('Clocked In', 'workflux-pro'); ?></strong>
                                <p><?php printf(__('Since: %s', 'workflux-pro'), date('H:i', strtotime($clock_status['clocked_in_since']))); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="wfp-status-inactive">
                                <span class="wfp-status-indicator inactive"></span>
                                <strong><?php _e('Not Clocked In', 'workflux-pro'); ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="wfp-widget wfp-widget-today-hours">
                    <div class="wfp-widget-header">
                        <h3><?php _e('Today\'s Hours', 'workflux-pro'); ?></h3>
                    </div>
                    <div class="wfp-widget-content">
                        <div class="wfp-hours-display">
                            <span class="wfp-hours-number"><?php echo number_format($today_hours, 2); ?></span>
                            <span class="wfp-hours-label"><?php _e('hours', 'workflux-pro'); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="wfp-widget wfp-widget-projects">
                    <div class="wfp-widget-header">
                        <h3><?php _e('Active Projects', 'workflux-pro'); ?></h3>
                    </div>
                    <div class="wfp-widget-content">
                        <div class="wfp-projects-count">
                            <span class="wfp-count-number"><?php echo count($assigned_projects); ?></span>
                            <span class="wfp-count-label"><?php _e('projects', 'workflux-pro'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="wfp-widget wfp-widget-tasks">
                <div class="wfp-widget-header">
                    <h3><?php _e('Recent Tasks', 'workflux-pro'); ?></h3>
                    <a href="<?php echo home_url('/workflux-pro/projects/'); ?>" class="wfp-widget-link">
                        <?php _e('View All', 'workflux-pro'); ?>
                    </a>
                </div>
                <div class="wfp-widget-content">
                    <?php if (!empty($my_tasks)): ?>
                        <ul class="wfp-task-list">
                            <?php foreach ($my_tasks as $task): ?>
                                <li class="wfp-task-item">
                                    <div class="wfp-task-info">
                                        <div class="wfp-task-title"><?php echo esc_html($task->title); ?></div>
                                        <div class="wfp-task-project"><?php echo esc_html($task->project_name); ?></div>
                                    </div>
                                    <div class="wfp-task-status">
                                        <span class="wfp-status-badge wfp-status-<?php echo esc_attr($task->status); ?>">
                                            <?php echo esc_html(ucfirst(str_replace('_', ' ', $task->status))); ?>
                                        </span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="wfp-no-data"><?php _e('No tasks assigned', 'workflux-pro'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render time tracking interface
     */
    private function render_time_tracking_interface() {
        $user_id = get_current_user_id();
        $clock_status = WorkFluxPro_Time_Tracking::get_current_status($user_id);
        $today_hours = WorkFluxPro_Time_Tracking::get_today_hours($user_id);
        $weekly_hours = WorkFluxPro_Time_Tracking::get_weekly_hours($user_id);
        
        ?>
        <div class="wfp-time-tracking-interface">
            <div class="wfp-time-controls">
                <div class="wfp-current-status">
                    <h3><?php _e('Current Status', 'workflux-pro'); ?></h3>
                    <?php if ($clock_status['is_clocked_in']): ?>
                        <div class="wfp-status-card wfp-status-active">
                            <div class="wfp-status-info">
                                <span class="wfp-status-indicator active"></span>
                                <div class="wfp-status-details">
                                    <strong><?php _e('Clocked In', 'workflux-pro'); ?></strong>
                                    <p><?php printf(__('Since: %s', 'workflux-pro'), date('H:i', strtotime($clock_status['clocked_in_since']))); ?></p>
                                    <p><?php printf(__('Duration: %.2f hours', 'workflux-pro'), $clock_status['current_duration']); ?></p>
                                </div>
                            </div>
                            <div class="wfp-status-actions">
                                <button type="button" class="wfp-btn wfp-btn-secondary" id="wfp-clock-out">
                                    <?php _e('Clock Out', 'workflux-pro'); ?>
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="wfp-status-card wfp-status-inactive">
                            <div class="wfp-status-info">
                                <span class="wfp-status-indicator inactive"></span>
                                <div class="wfp-status-details">
                                    <strong><?php _e('Not Clocked In', 'workflux-pro'); ?></strong>
                                    <p><?php _e('Ready to start your day?', 'workflux-pro'); ?></p>
                                </div>
                            </div>
                            <div class="wfp-status-actions">
                                <button type="button" class="wfp-btn wfp-btn-primary" id="wfp-clock-in">
                                    <?php _e('Clock In', 'workflux-pro'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($clock_status['is_clocked_in'] && $clock_status['is_on_project']): ?>
                    <div class="wfp-project-status">
                        <h3><?php _e('Current Project', 'workflux-pro'); ?></h3>
                        <div class="wfp-project-card">
                            <div class="wfp-project-info">
                                <strong><?php echo esc_html($clock_status['project_name']); ?></strong>
                                <?php if ($clock_status['task_title']): ?>
                                    <p><?php echo esc_html($clock_status['task_title']); ?></p>
                                <?php endif; ?>
                                <p><?php printf(__('Duration: %.2f hours', 'workflux-pro'), $clock_status['project_duration']); ?></p>
                            </div>
                            <div class="wfp-project-actions">
                                <button type="button" class="wfp-btn wfp-btn-secondary" id="wfp-stop-project">
                                    <?php _e('Stop Project', 'workflux-pro'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="wfp-time-summary">
                <div class="wfp-summary-card">
                    <h3><?php _e('Today\'s Summary', 'workflux-pro'); ?></h3>
                    <div class="wfp-summary-item">
                        <span class="wfp-summary-value"><?php echo number_format($today_hours, 2); ?></span>
                        <span class="wfp-summary-label"><?php _e('Hours Worked', 'workflux-pro'); ?></span>
                    </div>
                </div>
                
                <div class="wfp-summary-card">
                    <h3><?php _e('This Week', 'workflux-pro'); ?></h3>
                    <div class="wfp-summary-item">
                        <span class="wfp-summary-value"><?php echo number_format($weekly_hours['total_hours'], 2); ?></span>
                        <span class="wfp-summary-label"><?php _e('Total Hours', 'workflux-pro'); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Clock In/Out Modal -->
        <div id="wfp-clock-modal" class="wfp-modal" style="display: none;">
            <div class="wfp-modal-content">
                <div class="wfp-modal-header">
                    <h3 id="wfp-modal-title"><?php _e('Clock In', 'workflux-pro'); ?></h3>
                    <button type="button" class="wfp-modal-close">&times;</button>
                </div>
                <div class="wfp-modal-body">
                    <form id="wfp-clock-form">
                        <div class="wfp-form-group">
                            <label for="wfp-location"><?php _e('Location (Optional)', 'workflux-pro'); ?></label>
                            <input type="text" id="wfp-location" name="location" class="wfp-form-control">
                        </div>
                        <div class="wfp-form-group" id="wfp-description-group" style="display: none;">
                            <label for="wfp-description"><?php _e('Description', 'workflux-pro'); ?></label>
                            <textarea id="wfp-description" name="description" class="wfp-form-control" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="wfp-modal-footer">
                    <button type="button" class="wfp-btn wfp-btn-secondary" id="wfp-modal-cancel">
                        <?php _e('Cancel', 'workflux-pro'); ?>
                    </button>
                    <button type="button" class="wfp-btn wfp-btn-primary" id="wfp-modal-submit">
                        <?php _e('Confirm', 'workflux-pro'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render projects interface
     */
    private function render_projects_interface() {
        $user_id = get_current_user_id();
        $assigned_projects = WorkFluxPro_Project_Management::get_assigned_projects($user_id);
        $my_tasks = WorkFluxPro_Project_Management::get_user_tasks($user_id);
        
        ?>
        <div class="wfp-projects-interface">
            <div class="wfp-projects-grid">
                <?php if (!empty($assigned_projects)): ?>
                    <?php foreach ($assigned_projects as $project): ?>
                        <div class="wfp-project-card">
                            <div class="wfp-project-header">
                                <h3 class="wfp-project-title"><?php echo esc_html($project->name); ?></h3>
                                <span class="wfp-project-code"><?php echo esc_html($project->project_code); ?></span>
                            </div>
                            <div class="wfp-project-body">
                                <p class="wfp-project-description"><?php echo esc_html(wp_trim_words($project->description, 20)); ?></p>
                                <div class="wfp-project-meta">
                                    <div class="wfp-meta-item">
                                        <span class="wfp-meta-label"><?php _e('Role:', 'workflux-pro'); ?></span>
                                        <span class="wfp-meta-value"><?php echo esc_html($project->role); ?></span>
                                    </div>
                                    <div class="wfp-meta-item">
                                        <span class="wfp-meta-label"><?php _e('Status:', 'workflux-pro'); ?></span>
                                        <span class="wfp-status-badge wfp-status-<?php echo esc_attr($project->status); ?>">
                                            <?php echo esc_html(ucfirst(str_replace('_', ' ', $project->status))); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="wfp-project-actions">
                                <button type="button" class="wfp-btn wfp-btn-primary wfp-start-project" 
                                        data-project-id="<?php echo esc_attr($project->id); ?>">
                                    <?php _e('Start Working', 'workflux-pro'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="wfp-no-projects">
                        <p><?php _e('No projects assigned to you yet.', 'workflux-pro'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="wfp-tasks-section">
                <h2><?php _e('My Tasks', 'workflux-pro'); ?></h2>
                <?php if (!empty($my_tasks)): ?>
                    <div class="wfp-tasks-grid">
                        <?php foreach ($my_tasks as $task): ?>
                            <div class="wfp-task-card">
                                <div class="wfp-task-header">
                                    <h4 class="wfp-task-title"><?php echo esc_html($task->title); ?></h4>
                                    <span class="wfp-status-badge wfp-status-<?php echo esc_attr($task->status); ?>">
                                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $task->status))); ?>
                                    </span>
                                </div>
                                <div class="wfp-task-body">
                                    <p class="wfp-task-project"><?php echo esc_html($task->project_name); ?></p>
                                    <?php if ($task->description): ?>
                                        <p class="wfp-task-description"><?php echo esc_html(wp_trim_words($task->description, 15)); ?></p>
                                    <?php endif; ?>
                                    <?php if ($task->due_date): ?>
                                        <p class="wfp-task-due-date">
                                            <?php printf(__('Due: %s', 'workflux-pro'), date('M d, Y', strtotime($task->due_date))); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="wfp-task-actions">
                                    <button type="button" class="wfp-btn wfp-btn-sm wfp-btn-primary wfp-start-task" 
                                            data-project-id="<?php echo esc_attr($task->project_id); ?>"
                                            data-task-id="<?php echo esc_attr($task->id); ?>">
                                        <?php _e('Start Task', 'workflux-pro'); ?>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="wfp-no-data"><?php _e('No tasks assigned to you.', 'workflux-pro'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render profile interface
     */
    private function render_profile_interface() {
        $user_id = get_current_user_id();
        $current_user = get_user_by('id', $user_id);
        $employee = WorkFluxPro_User_Management::get_employee_by_user_id($user_id);
        $leave_balance = WorkFluxPro_Leave_Management::get_leave_balance($user_id);
        
        ?>
        <div class="wfp-profile-interface">
            <div class="wfp-profile-section">
                <h2><?php _e('Personal Information', 'workflux-pro'); ?></h2>
                <div class="wfp-profile-card">
                    <div class="wfp-profile-avatar">
                        <?php echo get_avatar($user_id, 96); ?>
                    </div>
                    <div class="wfp-profile-info">
                        <h3><?php echo esc_html($current_user->display_name); ?></h3>
                        <p class="wfp-profile-email"><?php echo esc_html($current_user->user_email); ?></p>
                        <?php if ($employee): ?>
                            <div class="wfp-profile-details">
                                <div class="wfp-detail-item">
                                    <span class="wfp-detail-label"><?php _e('Employee ID:', 'workflux-pro'); ?></span>
                                    <span class="wfp-detail-value"><?php echo esc_html($employee->employee_id); ?></span>
                                </div>
                                <?php if ($employee->department): ?>
                                    <div class="wfp-detail-item">
                                        <span class="wfp-detail-label"><?php _e('Department:', 'workflux-pro'); ?></span>
                                        <span class="wfp-detail-value"><?php echo esc_html($employee->department); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($employee->designation): ?>
                                    <div class="wfp-detail-item">
                                        <span class="wfp-detail-label"><?php _e('Designation:', 'workflux-pro'); ?></span>
                                        <span class="wfp-detail-value"><?php echo esc_html($employee->designation); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($employee->hire_date): ?>
                                    <div class="wfp-detail-item">
                                        <span class="wfp-detail-label"><?php _e('Hire Date:', 'workflux-pro'); ?></span>
                                        <span class="wfp-detail-value"><?php echo date('M d, Y', strtotime($employee->hire_date)); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($leave_balance)): ?>
                <div class="wfp-profile-section">
                    <h2><?php _e('Leave Balance', 'workflux-pro'); ?></h2>
                    <div class="wfp-leave-balance-grid">
                        <?php foreach ($leave_balance as $type => $balance): ?>
                            <div class="wfp-leave-balance-card">
                                <div class="wfp-leave-type"><?php echo esc_html($balance['label']); ?></div>
                                <div class="wfp-leave-remaining"><?php echo $balance['remaining']; ?></div>
                                <div class="wfp-leave-total"><?php printf(__('of %d days', 'workflux-pro'), $balance['total']); ?></div>
                                <div class="wfp-leave-used"><?php printf(__('%d used', 'workflux-pro'), $balance['used']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="wfp-profile-section">
                <h2><?php _e('Quick Actions', 'workflux-pro'); ?></h2>
                <div class="wfp-quick-actions-grid">
                    <a href="#" class="wfp-quick-action-btn" id="wfp-request-leave">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php _e('Request Leave', 'workflux-pro'); ?>
                    </a>
                    <a href="#" class="wfp-quick-action-btn" id="wfp-request-external-duty">
                        <span class="dashicons dashicons-location"></span>
                        <?php _e('External Duty', 'workflux-pro'); ?>
                    </a>
                    <a href="<?php echo home_url('/workflux-pro/time-tracking/'); ?>" class="wfp-quick-action-btn">
                        <span class="dashicons dashicons-clock"></span>
                        <?php _e('Time Tracking', 'workflux-pro'); ?>
                    </a>
                    <a href="<?php echo home_url('/workflux-pro/projects/'); ?>" class="wfp-quick-action-btn">
                        <span class="dashicons dashicons-portfolio"></span>
                        <?php _e('My Projects', 'workflux-pro'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
}