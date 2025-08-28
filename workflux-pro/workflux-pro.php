<?php
/**
 * Plugin Name: WorkFlux Pro
 * Plugin URI: https://srihayavadhana.com/workflux-pro
 * Description: Comprehensive employee management system with role-based permissions, time tracking, project management, and leave management.
 * Version: 1.0.0
 * Author: Nagarajarao C R
 * Author URI: https://srihayavadhana.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: workflux-pro
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 *
 * @package WorkFluxPro
 * @author Nagarajarao C R
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WORKFLUX_PRO_VERSION', '1.0.0');
define('WORKFLUX_PRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WORKFLUX_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WORKFLUX_PRO_PLUGIN_FILE', __FILE__);
define('WORKFLUX_PRO_DB_VERSION', '1.0.0');

/**
 * Main WorkFlux Pro Class
 */
class WorkFluxPro {
    
    /**
     * Single instance of the class
     *
     * @var WorkFluxPro
     */
    private static $instance = null;
    
    /**
     * Get single instance of the class
     *
     * @return WorkFluxPro
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->includes();
        $this->init_classes();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Include required files
     */
    private function includes() {
        // Core includes
        require_once WORKFLUX_PRO_PLUGIN_DIR . 'includes/class-database.php';
        require_once WORKFLUX_PRO_PLUGIN_DIR . 'includes/class-roles.php';
        require_once WORKFLUX_PRO_PLUGIN_DIR . 'includes/class-permissions.php';
        require_once WORKFLUX_PRO_PLUGIN_DIR . 'includes/class-user-management.php';
        require_once WORKFLUX_PRO_PLUGIN_DIR . 'includes/class-project-management.php';
        require_once WORKFLUX_PRO_PLUGIN_DIR . 'includes/class-time-tracking.php';
        require_once WORKFLUX_PRO_PLUGIN_DIR . 'includes/class-leave-management.php';
        require_once WORKFLUX_PRO_PLUGIN_DIR . 'includes/class-external-duty.php';
        require_once WORKFLUX_PRO_PLUGIN_DIR . 'includes/class-reports.php';
        
        // Admin includes
        if (is_admin()) {
            require_once WORKFLUX_PRO_PLUGIN_DIR . 'admin/class-admin.php';
            require_once WORKFLUX_PRO_PLUGIN_DIR . 'admin/class-admin-menu.php';
            require_once WORKFLUX_PRO_PLUGIN_DIR . 'admin/class-admin-dashboard.php';
        }
        
        // Frontend includes
        require_once WORKFLUX_PRO_PLUGIN_DIR . 'frontend/class-frontend.php';
        require_once WORKFLUX_PRO_PLUGIN_DIR . 'frontend/class-shortcodes.php';
        
        // AJAX includes
        require_once WORKFLUX_PRO_PLUGIN_DIR . 'includes/class-ajax.php';
        
        // API includes
        require_once WORKFLUX_PRO_PLUGIN_DIR . 'includes/class-rest-api.php';
    }
    
    /**
     * Initialize classes
     */
    private function init_classes() {
        new WorkFluxPro_Database();
        new WorkFluxPro_Roles();
        new WorkFluxPro_Permissions();
        new WorkFluxPro_User_Management();
        new WorkFluxPro_Project_Management();
        new WorkFluxPro_Time_Tracking();
        new WorkFluxPro_Leave_Management();
        new WorkFluxPro_External_Duty();
        new WorkFluxPro_Reports();
        
        if (is_admin()) {
            new WorkFluxPro_Admin();
            new WorkFluxPro_Admin_Menu();
            new WorkFluxPro_Admin_Dashboard();
        }
        
        new WorkFluxPro_Frontend();
        new WorkFluxPro_Shortcodes();
        new WorkFluxPro_Ajax();
        new WorkFluxPro_REST_API();
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        WorkFluxPro_Database::create_tables();
        
        // Add custom roles and capabilities
        WorkFluxPro_Roles::add_custom_roles();
        
        // Set default options
        add_option('workflux_pro_version', WORKFLUX_PRO_VERSION);
        add_option('workflux_pro_db_version', WORKFLUX_PRO_DB_VERSION);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up temporary data
        wp_clear_scheduled_hook('workflux_pro_daily_cleanup');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Load text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'workflux-pro',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Schedule daily cleanup
        if (!wp_next_scheduled('workflux_pro_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'workflux_pro_daily_cleanup');
        }
        
        // Add custom post types and taxonomies if needed
        $this->register_post_types();
        
        // Initialize AJAX actions
        $this->init_ajax_actions();
    }
    
    /**
     * Register custom post types
     */
    private function register_post_types() {
        // Register project post type
        register_post_type('wfp_project', array(
            'labels' => array(
                'name' => __('Projects', 'workflux-pro'),
                'singular_name' => __('Project', 'workflux-pro'),
            ),
            'public' => false,
            'show_ui' => false,
            'supports' => array('title', 'editor', 'custom-fields'),
        ));
        
        // Register task post type
        register_post_type('wfp_task', array(
            'labels' => array(
                'name' => __('Tasks', 'workflux-pro'),
                'singular_name' => __('Task', 'workflux-pro'),
            ),
            'public' => false,
            'show_ui' => false,
            'supports' => array('title', 'editor', 'custom-fields'),
        ));
    }
    
    /**
     * Initialize AJAX actions
     */
    private function init_ajax_actions() {
        // Public AJAX actions
        add_action('wp_ajax_wfp_clock_in', array('WorkFluxPro_Ajax', 'clock_in'));
        add_action('wp_ajax_wfp_clock_out', array('WorkFluxPro_Ajax', 'clock_out'));
        add_action('wp_ajax_wfp_start_project', array('WorkFluxPro_Ajax', 'start_project'));
        add_action('wp_ajax_wfp_stop_project', array('WorkFluxPro_Ajax', 'stop_project'));
        add_action('wp_ajax_wfp_submit_leave_request', array('WorkFluxPro_Ajax', 'submit_leave_request'));
        add_action('wp_ajax_wfp_submit_external_duty', array('WorkFluxPro_Ajax', 'submit_external_duty'));
        add_action('wp_ajax_wfp_get_dashboard_data', array('WorkFluxPro_Ajax', 'get_dashboard_data'));
        add_action('wp_ajax_wfp_get_reports', array('WorkFluxPro_Ajax', 'get_reports'));
        
        // Admin AJAX actions
        add_action('wp_ajax_wfp_approve_leave', array('WorkFluxPro_Ajax', 'approve_leave'));
        add_action('wp_ajax_wfp_approve_external_duty', array('WorkFluxPro_Ajax', 'approve_external_duty'));
        add_action('wp_ajax_wfp_create_project', array('WorkFluxPro_Ajax', 'create_project'));
        add_action('wp_ajax_wfp_assign_project', array('WorkFluxPro_Ajax', 'assign_project'));
        add_action('wp_ajax_wfp_update_user_role', array('WorkFluxPro_Ajax', 'update_user_role'));
    }
}

// Initialize the plugin
function workflux_pro() {
    return WorkFluxPro::get_instance();
}

// Start the plugin
workflux_pro();