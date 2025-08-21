<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="trd-admin-container">
        <div class="trd-admin-header">
            <h2><?php _e('Theater Room Designer Dashboard', 'theater-room-designer'); ?></h2>
            <p><?php _e('Manage your theater room designs and view usage statistics.', 'theater-room-designer'); ?></p>
        </div>
        
        <div class="trd-stats-grid">
            <div class="trd-stat-card">
                <div class="trd-stat-icon">
                    <span class="dashicons dashicons-video-alt2"></span>
                </div>
                <div class="trd-stat-content">
                    <h3><?php echo $trd_instance->get_total_designs(); ?></h3>
                    <p><?php _e('Total Designs', 'theater-room-designer'); ?></p>
                </div>
            </div>
            
            <div class="trd-stat-card">
                <div class="trd-stat-icon">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="trd-stat-content">
                    <h3><?php echo $trd_instance->get_active_users(); ?></h3>
                    <p><?php _e('Active Users', 'theater-room-designer'); ?></p>
                </div>
            </div>
            
            <div class="trd-stat-card">
                <div class="trd-stat-icon">
                    <span class="dashicons dashicons-calendar-alt"></span>
                </div>
                <div class="trd-stat-content">
                    <h3><?php echo $this->get_designs_this_month(); ?></h3>
                    <p><?php _e('This Month', 'theater-room-designer'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="trd-admin-sections">
            <div class="trd-section">
                <h3><?php _e('Quick Actions', 'theater-room-designer'); ?></h3>
                <div class="trd-quick-actions">
                    <a href="<?php echo admin_url('admin.php?page=theater-room-designer-settings'); ?>" class="button button-primary">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <?php _e('Plugin Settings', 'theater-room-designer'); ?>
                    </a>
                    <button id="trd-export-designs" class="button">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export All Designs', 'theater-room-designer'); ?>
                    </button>
                    <button id="trd-clear-cache" class="button">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Clear Cache', 'theater-room-designer'); ?>
                    </button>
                </div>
            </div>
            
            <div class="trd-section">
                <h3><?php _e('Recent Designs', 'theater-room-designer'); ?></h3>
                <div class="trd-recent-designs">
                    <?php $this->display_recent_designs(); ?>
                </div>
            </div>
            
            <div class="trd-section">
                <h3><?php _e('Shortcode Usage', 'theater-room-designer'); ?></h3>
                <div class="trd-shortcode-info">
                    <p><?php _e('Use the following shortcode to embed the Theater Room Designer on any page or post:', 'theater-room-designer'); ?></p>
                    <code class="trd-shortcode">[theater_room_designer]</code>
                    
                    <h4><?php _e('Shortcode Parameters:', 'theater-room-designer'); ?></h4>
                    <ul class="trd-shortcode-params">
                        <li><strong>width</strong> - <?php _e('Set the width (default: 100%)', 'theater-room-designer'); ?></li>
                        <li><strong>height</strong> - <?php _e('Set the height (default: 600px)', 'theater-room-designer'); ?></li>
                        <li><strong>show_saved</strong> - <?php _e('Show saved designs list (default: true)', 'theater-room-designer'); ?></li>
                        <li><strong>allow_save</strong> - <?php _e('Allow users to save designs (default: true)', 'theater-room-designer'); ?></li>
                    </ul>
                    
                    <h4><?php _e('Example:', 'theater-room-designer'); ?></h4>
                    <code class="trd-shortcode">[theater_room_designer width="800px" height="500px" show_saved="false"]</code>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Get the main plugin instance
global $theater_room_designer_instance;
if (!$theater_room_designer_instance) {
    // Fallback if instance not available
    function get_trd_instance() {
        global $wpdb;
        return new class {
            public function get_total_designs() {
                global $wpdb;
                $table_name = $wpdb->prefix . 'theater_room_designs';
                return $wpdb->get_var("SELECT COUNT(*) FROM $table_name") ?: 0;
            }
            
            public function get_active_users() {
                global $wpdb;
                $table_name = $wpdb->prefix . 'theater_room_designs';
                return $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $table_name WHERE user_id > 0") ?: 0;
            }
            
            public function get_designs_this_month() {
                global $wpdb;
                $table_name = $wpdb->prefix . 'theater_room_designs';
                return $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())") ?: 0;
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
        };
    }
    $trd_instance = get_trd_instance();
} else {
    $trd_instance = $theater_room_designer_instance;
}
?>