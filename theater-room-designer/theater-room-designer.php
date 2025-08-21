<?php
/**
 * Plugin Name: Theater Room Designer
 * Plugin URI: https://www.srihayavadhana.com/theater-room-designer
 * Description: A comprehensive 3D theater room design tool similar to Audio Advice's Home Theater Designer. Allows users to design and visualize home theater setups with room dimensions, seating arrangements, and speaker configurations.
 * Version: 1.0.0
 * Author: Sri Hayavadhana Info-Tech
 * Author URI: https://www.srihayavadhana.com/
 * License: GPL v2 or later
 * Text Domain: theater-room-designer
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TRD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TRD_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('TRD_PLUGIN_VERSION', '1.0.0');

// Main plugin class
class TheaterRoomDesigner {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_shortcode('theater_room_designer', array($this, 'shortcode_handler'));
        
        // AJAX handlers
        add_action('wp_ajax_save_room_design', array($this, 'save_room_design'));
        add_action('wp_ajax_nopriv_save_room_design', array($this, 'save_room_design'));
        add_action('wp_ajax_load_room_design', array($this, 'load_room_design'));
        add_action('wp_ajax_nopriv_load_room_design', array($this, 'load_room_design'));
        add_action('wp_ajax_get_room_designs', array($this, 'get_room_designs'));
        add_action('wp_ajax_nopriv_get_room_designs', array($this, 'get_room_designs'));
        
        // Database setup
        register_activation_hook(__FILE__, array($this, 'create_tables'));
    }
    
    public function init() {
        load_plugin_textdomain('theater-room-designer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('three-js', 'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js', array(), '128', true);
        wp_enqueue_script('orbit-controls', 'https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js', array('three-js'), '128', true);
        wp_enqueue_script('trd-main', TRD_PLUGIN_URL . 'assets/js/theater-designer.js', array('jquery', 'three-js'), TRD_PLUGIN_VERSION, true);
        wp_enqueue_style('trd-style', TRD_PLUGIN_URL . 'assets/css/theater-designer.css', array(), TRD_PLUGIN_VERSION);
        
        // Localize script for AJAX
        wp_localize_script('trd-main', 'trd_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('trd_nonce')
        ));
    }
    
    public function admin_enqueue_scripts($hook) {
        if ($hook === 'toplevel_page_theater-room-designer') {
            wp_enqueue_script('trd-admin', TRD_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), TRD_PLUGIN_VERSION, true);
            wp_enqueue_style('trd-admin-style', TRD_PLUGIN_URL . 'assets/css/admin.css', array(), TRD_PLUGIN_VERSION);
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('Theater Room Designer', 'theater-room-designer'),
            __('Theater Designer', 'theater-room-designer'),
            'manage_options',
            'theater-room-designer',
            array($this, 'admin_page'),
            'dashicons-video-alt2',
            30
        );
        
        add_submenu_page(
            'theater-room-designer',
            __('Settings', 'theater-room-designer'),
            __('Settings', 'theater-room-designer'),
            'manage_options',
            'theater-room-designer-settings',
            array($this, 'settings_page')
        );
    }
    
    public function admin_page() {
        include TRD_PLUGIN_PATH . 'includes/admin-page.php';
    }
    
    public function settings_page() {
        include TRD_PLUGIN_PATH . 'includes/settings-page.php';
    }
    
    public function shortcode_handler($atts) {
        $atts = shortcode_atts(array(
            'width' => '100%',
            'height' => '600px',
            'show_saved' => 'true',
            'allow_save' => 'true'
        ), $atts, 'theater_room_designer');
        
        ob_start();
        include TRD_PLUGIN_PATH . 'includes/designer-interface.php';
        return ob_get_clean();
    }
    
    public function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'theater_room_designs';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT 0,
            design_name varchar(255) NOT NULL,
            room_data longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function save_room_design() {
        check_ajax_referer('trd_nonce', 'nonce');
        
        global $wpdb;
        
        $design_name = sanitize_text_field($_POST['design_name']);
        $room_data = wp_unslash($_POST['room_data']);
        $user_id = get_current_user_id();
        
        $table_name = $wpdb->prefix . 'theater_room_designs';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'design_name' => $design_name,
                'room_data' => $room_data
            ),
            array('%d', '%s', '%s')
        );
        
        if ($result !== false) {
            wp_send_json_success(array('id' => $wpdb->insert_id));
        } else {
            wp_send_json_error(__('Failed to save design', 'theater-room-designer'));
        }
    }
    
    public function load_room_design() {
        check_ajax_referer('trd_nonce', 'nonce');
        
        global $wpdb;
        
        $design_id = intval($_POST['design_id']);
        $user_id = get_current_user_id();
        
        $table_name = $wpdb->prefix . 'theater_room_designs';
        
        $design = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND (user_id = %d OR user_id = 0)",
            $design_id,
            $user_id
        ));
        
        if ($design) {
            wp_send_json_success($design);
        } else {
            wp_send_json_error(__('Design not found', 'theater-room-designer'));
        }
    }
    
    public function get_room_designs() {
        check_ajax_referer('trd_nonce', 'nonce');
        
        global $wpdb;
        
        $user_id = get_current_user_id();
        $table_name = $wpdb->prefix . 'theater_room_designs';
        
        $designs = $wpdb->get_results($wpdb->prepare(
            "SELECT id, design_name, created_at FROM $table_name WHERE user_id = %d OR user_id = 0 ORDER BY created_at DESC",
            $user_id
        ));
        
        wp_send_json_success($designs);
    }
    
    // Admin helper methods
    public function get_total_designs() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'theater_room_designs';
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }
    
    public function get_active_users() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'theater_room_designs';
        return $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $table_name WHERE user_id > 0");
    }
    
    public function get_designs_this_month() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'theater_room_designs';
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    }
    
    public function display_recent_designs() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'theater_room_designs';
        
        $designs = $wpdb->get_results("
            SELECT d.*, u.display_name 
            FROM $table_name d 
            LEFT JOIN {$wpdb->users} u ON d.user_id = u.ID 
            ORDER BY d.created_at DESC 
            LIMIT 10
        ");
        
        if (empty($designs)) {
            echo '<p>' . __('No designs found.', 'theater-room-designer') . '</p>';
            return;
        }
        
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . __('Design Name', 'theater-room-designer') . '</th>';
        echo '<th>' . __('User', 'theater-room-designer') . '</th>';
        echo '<th>' . __('Created', 'theater-room-designer') . '</th>';
        echo '<th>' . __('Actions', 'theater-room-designer') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($designs as $design) {
            echo '<tr>';
            echo '<td><strong>' . esc_html($design->design_name) . '</strong></td>';
            echo '<td>' . ($design->display_name ? esc_html($design->display_name) : __('Guest', 'theater-room-designer')) . '</td>';
            echo '<td>' . date_i18n(get_option('date_format'), strtotime($design->created_at)) . '</td>';
            echo '<td>';
            echo '<button class="button button-small trd-view-design" data-id="' . $design->id . '">' . __('View', 'theater-room-designer') . '</button> ';
            echo '<button class="button button-small trd-delete-design" data-id="' . $design->id . '">' . __('Delete', 'theater-room-designer') . '</button>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
}

// Initialize the plugin
new TheaterRoomDesigner();