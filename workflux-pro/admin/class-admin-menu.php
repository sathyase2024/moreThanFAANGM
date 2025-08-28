<?php
/**
 * Admin menu class
 *
 * @package WorkFluxPro
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WorkFlux Pro Admin Menu Class
 */
class WorkFluxPro_Admin_Menu {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_admin_actions'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        if (!WorkFluxPro_Admin::can_access_admin()) {
            return;
        }
        
        $capability = WorkFluxPro_Admin::get_menu_capability();
        $user_role = WorkFluxPro_Roles::get_user_workflux_role();
        
        // Main menu
        add_menu_page(
            __('WorkFlux Pro', 'workflux-pro'),
            __('WorkFlux Pro', 'workflux-pro'),
            $capability,
            'workflux-pro',
            array($this, 'dashboard_page'),
            'dashicons-businessperson',
            30
        );
        
        // Dashboard (rename the first submenu)
        add_submenu_page(
            'workflux-pro',
            __('Dashboard', 'workflux-pro'),
            __('Dashboard', 'workflux-pro'),
            $capability,
            'workflux-pro',
            array($this, 'dashboard_page')
        );
        
        // Employees (for HR and above)
        if (WorkFluxPro_Roles::user_can('wfp_manage_employees') || 
            WorkFluxPro_Roles::user_can('wfp_user_onboarding')) {
            add_submenu_page(
                'workflux-pro',
                __('Employees', 'workflux-pro'),
                __('Employees', 'workflux-pro'),
                $capability,
                'wfp-employees',
                array($this, 'employees_page')
            );
        }
        
        // Projects (for project admins and above)
        if (WorkFluxPro_Roles::user_can('wfp_manage_projects') || 
            WorkFluxPro_Roles::user_can('wfp_view_assigned_projects')) {
            add_submenu_page(
                'workflux-pro',
                __('Projects', 'workflux-pro'),
                __('Projects', 'workflux-pro'),
                $capability,
                'wfp-projects',
                array($this, 'projects_page')
            );
        }
        
        // Time Tracking
        add_submenu_page(
            'workflux-pro',
            __('Time Tracking', 'workflux-pro'),
            __('Time Tracking', 'workflux-pro'),
            $capability,
            'wfp-time-tracking',
            array($this, 'time_tracking_page')
        );
        
        // Leave Management
        add_submenu_page(
            'workflux-pro',
            __('Leave Management', 'workflux-pro'),
            __('Leave Management', 'workflux-pro'),
            $capability,
            'wfp-leave-management',
            array($this, 'leave_management_page')
        );
        
        // External Duty
        add_submenu_page(
            'workflux-pro',
            __('External Duty', 'workflux-pro'),
            __('External Duty', 'workflux-pro'),
            $capability,
            'wfp-external-duty',
            array($this, 'external_duty_page')
        );
        
        // Reports (for managers and above)
        if (WorkFluxPro_Roles::user_can('wfp_view_team_reports') || 
            WorkFluxPro_Roles::user_can('wfp_view_all_reports')) {
            add_submenu_page(
                'workflux-pro',
                __('Reports', 'workflux-pro'),
                __('Reports', 'workflux-pro'),
                $capability,
                'wfp-reports',
                array($this, 'reports_page')
            );
        }
        
        // Settings (for super admin only)
        if (WorkFluxPro_Roles::user_can('wfp_manage_settings')) {
            add_submenu_page(
                'workflux-pro',
                __('Settings', 'workflux-pro'),
                __('Settings', 'workflux-pro'),
                $capability,
                'wfp-settings',
                array($this, 'settings_page')
            );
        }
    }
    
    /**
     * Handle admin actions
     */
    public function handle_admin_actions() {
        if (!isset($_GET['action']) || !isset($_GET['page']) || 
            strpos($_GET['page'], 'wfp-') !== 0 && $_GET['page'] !== 'workflux-pro') {
            return;
        }
        
        $action = sanitize_text_field($_GET['action']);
        $nonce_action = 'wfp_' . $action;
        
        if (!wp_verify_nonce($_GET['nonce'] ?? '', $nonce_action)) {
            wp_die(__('Security check failed', 'workflux-pro'));
        }
        
        switch ($action) {
            case 'delete_employee':
                $this->handle_delete_employee();
                break;
            case 'delete_project':
                $this->handle_delete_project();
                break;
            case 'approve_leave':
                $this->handle_approve_leave();
                break;
            case 'approve_external_duty':
                $this->handle_approve_external_duty();
                break;
        }
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        WorkFluxPro_Admin::render_admin_header(
            __('Dashboard', 'workflux-pro'),
            __('Overview of your WorkFlux Pro activities', 'workflux-pro')
        );
        
        $dashboard = new WorkFluxPro_Admin_Dashboard();
        $dashboard->render();
        
        WorkFluxPro_Admin::render_admin_footer();
    }
    
    /**
     * Employees page
     */
    public function employees_page() {
        if (!WorkFluxPro_Roles::user_can('wfp_manage_employees') && 
            !WorkFluxPro_Roles::user_can('wfp_user_onboarding')) {
            wp_die(__('Permission denied', 'workflux-pro'));
        }
        
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'add':
                $this->render_add_employee_page();
                break;
            case 'edit':
                $this->render_edit_employee_page();
                break;
            default:
                $this->render_employees_list_page();
                break;
        }
    }
    
    /**
     * Projects page
     */
    public function projects_page() {
        if (!WorkFluxPro_Roles::user_can('wfp_manage_projects') && 
            !WorkFluxPro_Roles::user_can('wfp_view_assigned_projects')) {
            wp_die(__('Permission denied', 'workflux-pro'));
        }
        
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'add':
                $this->render_add_project_page();
                break;
            case 'edit':
                $this->render_edit_project_page();
                break;
            case 'view':
                $this->render_view_project_page();
                break;
            default:
                $this->render_projects_list_page();
                break;
        }
    }
    
    /**
     * Time tracking page
     */
    public function time_tracking_page() {
        WorkFluxPro_Admin::render_admin_header(
            __('Time Tracking', 'workflux-pro'),
            __('Manage employee time tracking and attendance', 'workflux-pro')
        );
        
        $this->render_time_tracking_content();
        
        WorkFluxPro_Admin::render_admin_footer();
    }
    
    /**
     * Leave management page
     */
    public function leave_management_page() {
        WorkFluxPro_Admin::render_admin_header(
            __('Leave Management', 'workflux-pro'),
            __('Manage leave requests and approvals', 'workflux-pro')
        );
        
        $this->render_leave_management_content();
        
        WorkFluxPro_Admin::render_admin_footer();
    }
    
    /**
     * External duty page
     */
    public function external_duty_page() {
        WorkFluxPro_Admin::render_admin_header(
            __('External Duty', 'workflux-pro'),
            __('Manage external duty requests and approvals', 'workflux-pro')
        );
        
        $this->render_external_duty_content();
        
        WorkFluxPro_Admin::render_admin_footer();
    }
    
    /**
     * Reports page
     */
    public function reports_page() {
        if (!WorkFluxPro_Roles::user_can('wfp_view_team_reports') && 
            !WorkFluxPro_Roles::user_can('wfp_view_all_reports')) {
            wp_die(__('Permission denied', 'workflux-pro'));
        }
        
        WorkFluxPro_Admin::render_admin_header(
            __('Reports', 'workflux-pro'),
            __('Generate and view various reports', 'workflux-pro')
        );
        
        $this->render_reports_content();
        
        WorkFluxPro_Admin::render_admin_footer();
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        if (!WorkFluxPro_Roles::user_can('wfp_manage_settings')) {
            wp_die(__('Permission denied', 'workflux-pro'));
        }
        
        WorkFluxPro_Admin::render_admin_header(
            __('Settings', 'workflux-pro'),
            __('Configure WorkFlux Pro settings', 'workflux-pro')
        );
        
        $this->render_settings_content();
        
        WorkFluxPro_Admin::render_admin_footer();
    }
    
    /**
     * Render employees list page
     */
    private function render_employees_list_page() {
        WorkFluxPro_Admin::render_admin_header(
            __('Employees', 'workflux-pro'),
            __('Manage employee records and assignments', 'workflux-pro')
        );
        
        $employees = WorkFluxPro_User_Management::get_all_employees();
        
        ?>
        <div class="wfp-admin-content">
            <div class="wfp-content-header">
                <h2><?php _e('All Employees', 'workflux-pro'); ?></h2>
                <?php if (WorkFluxPro_Roles::user_can('wfp_user_onboarding')): ?>
                    <a href="<?php echo admin_url('admin.php?page=wfp-employees&action=add'); ?>" class="button button-primary">
                        <?php _e('Add New Employee', 'workflux-pro'); ?>
                    </a>
                <?php endif; ?>
            </div>
            
            <?php
            $columns = array(
                'employee_id' => __('Employee ID', 'workflux-pro'),
                'display_name' => __('Name', 'workflux-pro'),
                'user_email' => __('Email', 'workflux-pro'),
                'department' => __('Department', 'workflux-pro'),
                'designation' => __('Designation', 'workflux-pro'),
                'hire_date' => __('Hire Date', 'workflux-pro'),
                'status' => __('Status', 'workflux-pro')
            );
            
            $actions = array();
            if (WorkFluxPro_Roles::user_can('wfp_manage_employees')) {
                $actions[] = array(
                    'label' => __('Edit', 'workflux-pro'),
                    'url' => admin_url('admin.php?page=wfp-employees&action=edit&id={id}'),
                    'class' => 'button-secondary'
                );
                $actions[] = array(
                    'label' => __('Delete', 'workflux-pro'),
                    'url' => admin_url('admin.php?page=wfp-employees&action=delete_employee&id={id}&nonce=' . wp_create_nonce('wfp_delete_employee')),
                    'class' => 'button-secondary wfp-delete',
                    'confirm' => __('Are you sure you want to delete this employee?', 'workflux-pro')
                );
            }
            
            WorkFluxPro_Admin::render_data_table($columns, $employees, array(
                'search' => true,
                'export' => true,
                'actions' => $actions
            ));
            ?>
        </div>
        <?php
        
        WorkFluxPro_Admin::render_admin_footer();
    }
    
    /**
     * Render projects list page
     */
    private function render_projects_list_page() {
        WorkFluxPro_Admin::render_admin_header(
            __('Projects', 'workflux-pro'),
            __('Manage projects and task assignments', 'workflux-pro')
        );
        
        $user_id = get_current_user_id();
        if (WorkFluxPro_Roles::user_can('wfp_manage_projects')) {
            $projects = WorkFluxPro_Project_Management::get_projects();
        } else {
            $projects = WorkFluxPro_Project_Management::get_user_projects($user_id);
        }
        
        ?>
        <div class="wfp-admin-content">
            <div class="wfp-content-header">
                <h2><?php _e('Projects', 'workflux-pro'); ?></h2>
                <?php if (WorkFluxPro_Roles::user_can('wfp_manage_projects')): ?>
                    <a href="<?php echo admin_url('admin.php?page=wfp-projects&action=add'); ?>" class="button button-primary">
                        <?php _e('Add New Project', 'workflux-pro'); ?>
                    </a>
                <?php endif; ?>
            </div>
            
            <?php
            $columns = array(
                'project_code' => __('Project Code', 'workflux-pro'),
                'name' => __('Name', 'workflux-pro'),
                'client' => __('Client', 'workflux-pro'),
                'status' => __('Status', 'workflux-pro'),
                'priority' => __('Priority', 'workflux-pro'),
                'estimated_hours' => __('Estimated Hours', 'workflux-pro'),
                'actual_hours' => __('Actual Hours', 'workflux-pro'),
                'start_date' => __('Start Date', 'workflux-pro'),
                'end_date' => __('End Date', 'workflux-pro')
            );
            
            $actions = array(
                array(
                    'label' => __('View', 'workflux-pro'),
                    'url' => admin_url('admin.php?page=wfp-projects&action=view&id={id}'),
                    'class' => 'button-secondary'
                )
            );
            
            if (WorkFluxPro_Roles::user_can('wfp_manage_projects')) {
                $actions[] = array(
                    'label' => __('Edit', 'workflux-pro'),
                    'url' => admin_url('admin.php?page=wfp-projects&action=edit&id={id}'),
                    'class' => 'button-secondary'
                );
            }
            
            WorkFluxPro_Admin::render_data_table($columns, $projects, array(
                'search' => true,
                'export' => true,
                'actions' => $actions
            ));
            ?>
        </div>
        <?php
        
        WorkFluxPro_Admin::render_admin_footer();
    }
    
    /**
     * Render time tracking content
     */
    private function render_time_tracking_content() {
        $user_id = get_current_user_id();
        $current_status = WorkFluxPro_Time_Tracking::get_current_status($user_id);
        $today_hours = WorkFluxPro_Time_Tracking::get_today_hours($user_id);
        
        ?>
        <div class="wfp-admin-content">
            <div class="wfp-time-tracking-dashboard">
                <div class="wfp-current-status">
                    <h3><?php _e('Current Status', 'workflux-pro'); ?></h3>
                    <div class="wfp-status-card">
                        <?php if ($current_status['is_clocked_in']): ?>
                            <div class="wfp-status-active">
                                <span class="wfp-status-indicator active"></span>
                                <strong><?php _e('Clocked In', 'workflux-pro'); ?></strong>
                                <p><?php printf(__('Since: %s', 'workflux-pro'), date('H:i', strtotime($current_status['clocked_in_since']))); ?></p>
                                <p><?php printf(__('Duration: %.2f hours', 'workflux-pro'), $current_status['current_duration']); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="wfp-status-inactive">
                                <span class="wfp-status-indicator inactive"></span>
                                <strong><?php _e('Not Clocked In', 'workflux-pro'); ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="wfp-today-summary">
                    <h3><?php _e('Today\'s Summary', 'workflux-pro'); ?></h3>
                    <div class="wfp-summary-card">
                        <div class="wfp-summary-item">
                            <span class="wfp-summary-value"><?php echo number_format($today_hours, 2); ?></span>
                            <span class="wfp-summary-label"><?php _e('Hours Worked', 'workflux-pro'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="wfp-time-tracking-controls">
                <h3><?php _e('Time Tracking Controls', 'workflux-pro'); ?></h3>
                <div class="wfp-controls-row">
                    <?php if (!$current_status['is_clocked_in']): ?>
                        <button type="button" class="button button-primary" id="wfp-clock-in">
                            <?php _e('Clock In', 'workflux-pro'); ?>
                        </button>
                    <?php else: ?>
                        <button type="button" class="button button-secondary" id="wfp-clock-out">
                            <?php _e('Clock Out', 'workflux-pro'); ?>
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($current_status['is_clocked_in']): ?>
                        <button type="button" class="button" id="wfp-add-break">
                            <?php _e('Add Break Time', 'workflux-pro'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render leave management content
     */
    private function render_leave_management_content() {
        $user_id = get_current_user_id();
        $pending_requests = WorkFluxPro_Leave_Management::get_requests(array('status' => 'pending'));
        $user_requests = WorkFluxPro_Leave_Management::get_user_requests($user_id);
        
        ?>
        <div class="wfp-admin-content">
            <?php if (WorkFluxPro_Roles::user_can('wfp_approve_leaves') && !empty($pending_requests)): ?>
                <div class="wfp-pending-requests">
                    <h3><?php _e('Pending Leave Requests', 'workflux-pro'); ?></h3>
                    <?php
                    $columns = array(
                        'employee_name' => __('Employee', 'workflux-pro'),
                        'leave_type' => __('Leave Type', 'workflux-pro'),
                        'start_date' => __('Start Date', 'workflux-pro'),
                        'end_date' => __('End Date', 'workflux-pro'),
                        'days_requested' => __('Days', 'workflux-pro'),
                        'reason' => __('Reason', 'workflux-pro')
                    );
                    
                    $actions = array(
                        array(
                            'label' => __('Approve', 'workflux-pro'),
                            'url' => '#',
                            'class' => 'button-primary wfp-approve-leave'
                        ),
                        array(
                            'label' => __('Reject', 'workflux-pro'),
                            'url' => '#',
                            'class' => 'button-secondary wfp-reject-leave'
                        )
                    );
                    
                    WorkFluxPro_Admin::render_data_table($columns, $pending_requests, array('actions' => $actions));
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="wfp-user-requests">
                <h3><?php _e('My Leave Requests', 'workflux-pro'); ?></h3>
                <a href="#" class="button button-primary" id="wfp-new-leave-request">
                    <?php _e('Submit New Request', 'workflux-pro'); ?>
                </a>
                
                <?php if (!empty($user_requests)): ?>
                    <?php
                    $columns = array(
                        'leave_type' => __('Leave Type', 'workflux-pro'),
                        'start_date' => __('Start Date', 'workflux-pro'),
                        'end_date' => __('End Date', 'workflux-pro'),
                        'days_requested' => __('Days', 'workflux-pro'),
                        'status' => __('Status', 'workflux-pro'),
                        'created_at' => __('Submitted', 'workflux-pro')
                    );
                    
                    WorkFluxPro_Admin::render_data_table($columns, $user_requests);
                    ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render external duty content
     */
    private function render_external_duty_content() {
        $user_id = get_current_user_id();
        $pending_requests = WorkFluxPro_External_Duty::get_requests(array('status' => 'pending'));
        $user_requests = WorkFluxPro_External_Duty::get_user_requests($user_id);
        
        ?>
        <div class="wfp-admin-content">
            <?php if (WorkFluxPro_Roles::user_can('wfp_approve_external_duty') && !empty($pending_requests)): ?>
                <div class="wfp-pending-requests">
                    <h3><?php _e('Pending External Duty Requests', 'workflux-pro'); ?></h3>
                    <?php
                    $columns = array(
                        'employee_name' => __('Employee', 'workflux-pro'),
                        'purpose' => __('Purpose', 'workflux-pro'),
                        'location' => __('Location', 'workflux-pro'),
                        'start_date' => __('Start Date', 'workflux-pro'),
                        'end_date' => __('End Date', 'workflux-pro'),
                        'description' => __('Description', 'workflux-pro')
                    );
                    
                    $actions = array(
                        array(
                            'label' => __('Approve', 'workflux-pro'),
                            'url' => '#',
                            'class' => 'button-primary wfp-approve-external-duty'
                        ),
                        array(
                            'label' => __('Reject', 'workflux-pro'),
                            'url' => '#',
                            'class' => 'button-secondary wfp-reject-external-duty'
                        )
                    );
                    
                    WorkFluxPro_Admin::render_data_table($columns, $pending_requests, array('actions' => $actions));
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="wfp-user-requests">
                <h3><?php _e('My External Duty Requests', 'workflux-pro'); ?></h3>
                <a href="#" class="button button-primary" id="wfp-new-external-duty-request">
                    <?php _e('Submit New Request', 'workflux-pro'); ?>
                </a>
                
                <?php if (!empty($user_requests)): ?>
                    <?php
                    $columns = array(
                        'purpose' => __('Purpose', 'workflux-pro'),
                        'location' => __('Location', 'workflux-pro'),
                        'start_date' => __('Start Date', 'workflux-pro'),
                        'end_date' => __('End Date', 'workflux-pro'),
                        'status' => __('Status', 'workflux-pro'),
                        'created_at' => __('Submitted', 'workflux-pro')
                    );
                    
                    WorkFluxPro_Admin::render_data_table($columns, $user_requests);
                    ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render reports content
     */
    private function render_reports_content() {
        ?>
        <div class="wfp-admin-content">
            <div class="wfp-reports-generator">
                <h3><?php _e('Generate Reports', 'workflux-pro'); ?></h3>
                <form id="wfp-generate-report-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Report Type', 'workflux-pro'); ?></th>
                            <td>
                                <select name="report_type" required>
                                    <option value=""><?php _e('Select Report Type', 'workflux-pro'); ?></option>
                                    <?php foreach (WorkFluxPro_Reports::get_report_types() as $type => $label): ?>
                                        <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Date Range', 'workflux-pro'); ?></th>
                            <td>
                                <input type="date" name="date_from" required>
                                <span><?php _e('to', 'workflux-pro'); ?></span>
                                <input type="date" name="date_to" required>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e('Generate Report', 'workflux-pro'); ?></button>
                    </p>
                </form>
            </div>
            
            <div id="wfp-report-results" class="wfp-report-results" style="display: none;">
                <!-- Report results will be loaded here -->
            </div>
        </div>
        <?php
    }
    
    /**
     * Render settings content
     */
    private function render_settings_content() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        
        ?>
        <div class="wfp-admin-content">
            <form method="post" action="">
                <?php wp_nonce_field('wfp_save_settings'); ?>
                
                <h3><?php _e('General Settings', 'workflux-pro'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Company Name', 'workflux-pro'); ?></th>
                        <td>
                            <input type="text" name="workflux_pro_company_name" 
                                   value="<?php echo esc_attr(get_option('workflux_pro_company_name', '')); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Time Zone', 'workflux-pro'); ?></th>
                        <td>
                            <select name="workflux_pro_timezone">
                                <?php
                                $selected_timezone = get_option('workflux_pro_timezone', get_option('timezone_string'));
                                $timezones = timezone_identifiers_list();
                                foreach ($timezones as $timezone) {
                                    printf(
                                        '<option value="%s" %s>%s</option>',
                                        esc_attr($timezone),
                                        selected($selected_timezone, $timezone, false),
                                        esc_html($timezone)
                                    );
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <h3><?php _e('Time Tracking Settings', 'workflux-pro'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Allow Mobile Tracking', 'workflux-pro'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="workflux_pro_allow_mobile_tracking" value="1"
                                       <?php checked(get_option('workflux_pro_allow_mobile_tracking'), 1); ?>>
                                <?php _e('Allow employees to track time from mobile devices', 'workflux-pro'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Require Location', 'workflux-pro'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="workflux_pro_require_location" value="1"
                                       <?php checked(get_option('workflux_pro_require_location'), 1); ?>>
                                <?php _e('Require location information when clocking in/out', 'workflux-pro'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <h3><?php _e('Leave Settings', 'workflux-pro'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Annual Leave Days', 'workflux-pro'); ?></th>
                        <td>
                            <input type="number" name="workflux_pro_annual_leave_days" 
                                   value="<?php echo esc_attr(get_option('workflux_pro_annual_leave_days', 21)); ?>" 
                                   min="0" max="365">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Sick Leave Days', 'workflux-pro'); ?></th>
                        <td>
                            <input type="number" name="workflux_pro_sick_leave_days" 
                                   value="<?php echo esc_attr(get_option('workflux_pro_sick_leave_days', 10)); ?>" 
                                   min="0" max="365">
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'wfp_save_settings')) {
            wp_die(__('Security check failed', 'workflux-pro'));
        }
        
        if (!WorkFluxPro_Roles::user_can('wfp_manage_settings')) {
            wp_die(__('Permission denied', 'workflux-pro'));
        }
        
        $settings = array(
            'workflux_pro_company_name',
            'workflux_pro_timezone',
            'workflux_pro_allow_mobile_tracking',
            'workflux_pro_require_location',
            'workflux_pro_annual_leave_days',
            'workflux_pro_sick_leave_days'
        );
        
        foreach ($settings as $setting) {
            if (isset($_POST[$setting])) {
                update_option($setting, sanitize_text_field($_POST[$setting]));
            }
        }
        
        wp_redirect(admin_url('admin.php?page=wfp-settings&wfp_message=settings_saved'));
        exit;
    }
    
    /**
     * Handle employee deletion
     */
    private function handle_delete_employee() {
        if (!WorkFluxPro_Roles::user_can('wfp_manage_employees')) {
            wp_die(__('Permission denied', 'workflux-pro'));
        }
        
        $employee_id = intval($_GET['id']);
        $employee = WorkFluxPro_User_Management::get_employee($employee_id);
        
        if (!$employee) {
            wp_redirect(admin_url('admin.php?page=wfp-employees&wfp_error=invalid_data'));
            exit;
        }
        
        // Soft delete - mark as terminated
        $result = WorkFluxPro_User_Management::update_employee($employee_id, array('status' => 'terminated'));
        
        if ($result) {
            wp_redirect(admin_url('admin.php?page=wfp-employees&wfp_message=employee_updated'));
        } else {
            wp_redirect(admin_url('admin.php?page=wfp-employees&wfp_error=operation_failed'));
        }
        exit;
    }
}