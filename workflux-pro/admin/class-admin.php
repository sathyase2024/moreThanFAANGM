<?php
/**
 * Admin main class
 *
 * @package WorkFluxPro
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WorkFlux Pro Admin Class
 */
class WorkFluxPro_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        // Check user permissions
        if (!current_user_can('manage_options') && !WorkFluxPro_Roles::get_user_workflux_role()) {
            return;
        }
        
        // Register settings
        $this->register_settings();
    }
    
    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook
     */
    public function enqueue_scripts($hook) {
        // Only load on WorkFlux Pro admin pages
        if (strpos($hook, 'workflux-pro') === false && strpos($hook, 'wfp-') === false) {
            return;
        }
        
        // Enqueue styles
        wp_enqueue_style(
            'workflux-pro-admin',
            WORKFLUX_PRO_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WORKFLUX_PRO_VERSION
        );
        
        // Enqueue Chart.js for reports
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            array(),
            '3.9.1',
            true
        );
        
        // Enqueue admin JavaScript
        wp_enqueue_script(
            'workflux-pro-admin',
            WORKFLUX_PRO_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'chart-js'),
            WORKFLUX_PRO_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('workflux-pro-admin', 'workfluxProAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('workflux_pro_nonce'),
            'currentUser' => get_current_user_id(),
            'userRole' => WorkFluxPro_Roles::get_user_workflux_role(),
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this item?', 'workflux-pro'),
                'success' => __('Operation completed successfully', 'workflux-pro'),
                'error' => __('An error occurred', 'workflux-pro'),
                'loading' => __('Loading...', 'workflux-pro'),
                'save' => __('Save', 'workflux-pro'),
                'cancel' => __('Cancel', 'workflux-pro')
            )
        ));
        
        // Enqueue WordPress media uploader
        wp_enqueue_media();
        
        // Enqueue date picker
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css');
    }
    
    /**
     * Register admin settings
     */
    private function register_settings() {
        // General settings
        register_setting('workflux_pro_general', 'workflux_pro_company_name');
        register_setting('workflux_pro_general', 'workflux_pro_company_logo');
        register_setting('workflux_pro_general', 'workflux_pro_timezone');
        register_setting('workflux_pro_general', 'workflux_pro_date_format');
        register_setting('workflux_pro_general', 'workflux_pro_time_format');
        
        // Time tracking settings
        register_setting('workflux_pro_time_tracking', 'workflux_pro_allow_mobile_tracking');
        register_setting('workflux_pro_time_tracking', 'workflux_pro_require_location');
        register_setting('workflux_pro_time_tracking', 'workflux_pro_auto_break_time');
        register_setting('workflux_pro_time_tracking', 'workflux_pro_overtime_threshold');
        
        // Leave settings
        register_setting('workflux_pro_leave', 'workflux_pro_annual_leave_days');
        register_setting('workflux_pro_leave', 'workflux_pro_sick_leave_days');
        register_setting('workflux_pro_leave', 'workflux_pro_personal_leave_days');
        register_setting('workflux_pro_leave', 'workflux_pro_require_leave_approval');
        
        // Email notification settings
        register_setting('workflux_pro_notifications', 'workflux_pro_enable_email_notifications');
        register_setting('workflux_pro_notifications', 'workflux_pro_notification_email');
        register_setting('workflux_pro_notifications', 'workflux_pro_notify_clock_in');
        register_setting('workflux_pro_notifications', 'workflux_pro_notify_leave_requests');
        register_setting('workflux_pro_notifications', 'workflux_pro_notify_project_assignments');
    }
    
    /**
     * Show admin notices
     */
    public function admin_notices() {
        // Check if database tables are created
        if (!WorkFluxPro_Database::tables_exist()) {
            echo '<div class="notice notice-error"><p>';
            echo __('WorkFlux Pro: Database tables are not created. Please deactivate and reactivate the plugin.', 'workflux-pro');
            echo '</p></div>';
        }
        
        // Check if user has WorkFlux role
        $current_user_role = WorkFluxPro_Roles::get_user_workflux_role();
        if (!$current_user_role && !current_user_can('manage_options')) {
            echo '<div class="notice notice-warning"><p>';
            echo __('WorkFlux Pro: You need to be assigned a WorkFlux role to access the features.', 'workflux-pro');
            echo '</p></div>';
        }
        
        // Show success messages
        if (isset($_GET['wfp_message'])) {
            $message = sanitize_text_field($_GET['wfp_message']);
            $messages = array(
                'employee_created' => __('Employee created successfully', 'workflux-pro'),
                'employee_updated' => __('Employee updated successfully', 'workflux-pro'),
                'project_created' => __('Project created successfully', 'workflux-pro'),
                'project_updated' => __('Project updated successfully', 'workflux-pro'),
                'settings_saved' => __('Settings saved successfully', 'workflux-pro')
            );
            
            if (isset($messages[$message])) {
                echo '<div class="notice notice-success is-dismissible"><p>';
                echo esc_html($messages[$message]);
                echo '</p></div>';
            }
        }
        
        // Show error messages
        if (isset($_GET['wfp_error'])) {
            $error = sanitize_text_field($_GET['wfp_error']);
            $errors = array(
                'permission_denied' => __('Permission denied', 'workflux-pro'),
                'invalid_data' => __('Invalid data provided', 'workflux-pro'),
                'operation_failed' => __('Operation failed', 'workflux-pro')
            );
            
            if (isset($errors[$error])) {
                echo '<div class="notice notice-error is-dismissible"><p>';
                echo esc_html($errors[$error]);
                echo '</p></div>';
            }
        }
    }
    
    /**
     * Get admin menu capability based on user role
     *
     * @return string
     */
    public static function get_menu_capability() {
        $current_user_role = WorkFluxPro_Roles::get_user_workflux_role();
        
        if (!$current_user_role) {
            return 'manage_options';
        }
        
        // All WorkFlux roles can access the menu
        return 'read';
    }
    
    /**
     * Check if current user can access admin area
     *
     * @return bool
     */
    public static function can_access_admin() {
        return current_user_can('manage_options') || WorkFluxPro_Roles::get_user_workflux_role();
    }
    
    /**
     * Render admin header
     */
    public static function render_admin_header($title = '', $description = '') {
        $current_user = wp_get_current_user();
        $user_role = WorkFluxPro_Roles::get_user_workflux_role();
        $role_display_name = WorkFluxPro_Roles::get_role_display_name($user_role);
        ?>
        <div class="wfp-admin-header">
            <div class="wfp-header-content">
                <div class="wfp-header-title">
                    <h1><?php echo esc_html($title ?: __('WorkFlux Pro Dashboard', 'workflux-pro')); ?></h1>
                    <?php if ($description): ?>
                        <p class="description"><?php echo esc_html($description); ?></p>
                    <?php endif; ?>
                </div>
                <div class="wfp-header-user">
                    <div class="wfp-user-info">
                        <?php echo get_avatar($current_user->ID, 32); ?>
                        <div class="wfp-user-details">
                            <span class="wfp-user-name"><?php echo esc_html($current_user->display_name); ?></span>
                            <span class="wfp-user-role"><?php echo esc_html($role_display_name); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render admin footer
     */
    public static function render_admin_footer() {
        ?>
        <div class="wfp-admin-footer">
            <p>
                <?php 
                printf(
                    __('WorkFlux Pro v%s - Developed by %s', 'workflux-pro'),
                    WORKFLUX_PRO_VERSION,
                    '<strong>Sri Hayavadhana Info-Tech</strong>'
                );
                ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Render loading spinner
     */
    public static function render_loading_spinner() {
        ?>
        <div class="wfp-loading-spinner">
            <div class="wfp-spinner"></div>
            <p><?php _e('Loading...', 'workflux-pro'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Render data table
     *
     * @param array $columns
     * @param array $data
     * @param array $args
     */
    public static function render_data_table($columns, $data, $args = array()) {
        $defaults = array(
            'class' => 'wfp-data-table',
            'id' => '',
            'actions' => array(),
            'pagination' => false,
            'search' => false,
            'export' => false
        );
        
        $args = wp_parse_args($args, $defaults);
        ?>
        <div class="wfp-table-wrapper">
            <?php if ($args['search'] || $args['export']): ?>
                <div class="wfp-table-controls">
                    <?php if ($args['search']): ?>
                        <div class="wfp-table-search">
                            <input type="text" id="wfp-table-search" placeholder="<?php _e('Search...', 'workflux-pro'); ?>">
                        </div>
                    <?php endif; ?>
                    <?php if ($args['export']): ?>
                        <div class="wfp-table-export">
                            <button type="button" class="button" id="wfp-export-csv">
                                <?php _e('Export CSV', 'workflux-pro'); ?>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <table class="<?php echo esc_attr($args['class']); ?>" <?php echo $args['id'] ? 'id="' . esc_attr($args['id']) . '"' : ''; ?>>
                <thead>
                    <tr>
                        <?php foreach ($columns as $key => $label): ?>
                            <th data-column="<?php echo esc_attr($key); ?>">
                                <?php echo esc_html($label); ?>
                            </th>
                        <?php endforeach; ?>
                        <?php if (!empty($args['actions'])): ?>
                            <th class="wfp-actions-column"><?php _e('Actions', 'workflux-pro'); ?></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data)): ?>
                        <tr class="wfp-no-data">
                            <td colspan="<?php echo count($columns) + (empty($args['actions']) ? 0 : 1); ?>">
                                <?php _e('No data available', 'workflux-pro'); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data as $row): ?>
                            <tr>
                                <?php foreach ($columns as $key => $label): ?>
                                    <td data-column="<?php echo esc_attr($key); ?>">
                                        <?php 
                                        $value = is_object($row) ? ($row->$key ?? '') : ($row[$key] ?? '');
                                        echo esc_html($value);
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                                <?php if (!empty($args['actions'])): ?>
                                    <td class="wfp-actions-column">
                                        <?php foreach ($args['actions'] as $action): ?>
                                            <a href="<?php echo esc_url($action['url']); ?>" 
                                               class="button button-small <?php echo esc_attr($action['class'] ?? ''); ?>"
                                               <?php echo isset($action['confirm']) ? 'onclick="return confirm(\'' . esc_js($action['confirm']) . '\')"' : ''; ?>>
                                                <?php echo esc_html($action['label']); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if ($args['pagination']): ?>
                <div class="wfp-table-pagination">
                    <!-- Pagination will be handled by JavaScript -->
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render status badge
     *
     * @param string $status
     * @param array $status_map
     */
    public static function render_status_badge($status, $status_map = array()) {
        $default_map = array(
            'active' => array('label' => __('Active', 'workflux-pro'), 'class' => 'success'),
            'inactive' => array('label' => __('Inactive', 'workflux-pro'), 'class' => 'secondary'),
            'pending' => array('label' => __('Pending', 'workflux-pro'), 'class' => 'warning'),
            'approved' => array('label' => __('Approved', 'workflux-pro'), 'class' => 'success'),
            'rejected' => array('label' => __('Rejected', 'workflux-pro'), 'class' => 'error'),
            'completed' => array('label' => __('Completed', 'workflux-pro'), 'class' => 'success'),
            'cancelled' => array('label' => __('Cancelled', 'workflux-pro'), 'class' => 'error')
        );
        
        $status_map = wp_parse_args($status_map, $default_map);
        $status_info = $status_map[$status] ?? array('label' => $status, 'class' => 'secondary');
        
        ?>
        <span class="wfp-status-badge wfp-status-<?php echo esc_attr($status_info['class']); ?>">
            <?php echo esc_html($status_info['label']); ?>
        </span>
        <?php
    }
}