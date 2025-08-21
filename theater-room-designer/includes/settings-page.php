<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['submit']) && wp_verify_nonce($_POST['trd_settings_nonce'], 'trd_settings')) {
    $options = array(
        'default_room_width' => floatval($_POST['default_room_width']),
        'default_room_length' => floatval($_POST['default_room_length']),
        'default_room_height' => floatval($_POST['default_room_height']),
        'default_screen_size' => intval($_POST['default_screen_size']),
        'enable_guest_saving' => isset($_POST['enable_guest_saving']) ? 1 : 0,
        'max_saved_designs' => intval($_POST['max_saved_designs']),
        'show_branding' => isset($_POST['show_branding']) ? 1 : 0,
        'enable_social_sharing' => isset($_POST['enable_social_sharing']) ? 1 : 0,
        'default_speaker_config' => sanitize_text_field($_POST['default_speaker_config']),
        'measurement_unit' => sanitize_text_field($_POST['measurement_unit']),
    );
    
    update_option('trd_settings', $options);
    echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'theater-room-designer') . '</p></div>';
}

// Get current settings
$settings = get_option('trd_settings', array(
    'default_room_width' => 12,
    'default_room_length' => 16,
    'default_room_height' => 9,
    'default_screen_size' => 65,
    'enable_guest_saving' => 0,
    'max_saved_designs' => 10,
    'show_branding' => 1,
    'enable_social_sharing' => 1,
    'default_speaker_config' => '5.1',
    'measurement_unit' => 'feet',
));
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('trd_settings', 'trd_settings_nonce'); ?>
        
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="measurement_unit"><?php _e('Measurement Unit', 'theater-room-designer'); ?></label>
                    </th>
                    <td>
                        <select id="measurement_unit" name="measurement_unit">
                            <option value="feet" <?php selected($settings['measurement_unit'], 'feet'); ?>><?php _e('Feet', 'theater-room-designer'); ?></option>
                            <option value="meters" <?php selected($settings['measurement_unit'], 'meters'); ?>><?php _e('Meters', 'theater-room-designer'); ?></option>
                        </select>
                        <p class="description"><?php _e('Choose the default measurement unit for room dimensions.', 'theater-room-designer'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="default_room_width"><?php _e('Default Room Width', 'theater-room-designer'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="default_room_width" name="default_room_width" value="<?php echo esc_attr($settings['default_room_width']); ?>" step="0.1" min="1" max="100" />
                        <p class="description"><?php _e('Default width when creating a new room design.', 'theater-room-designer'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="default_room_length"><?php _e('Default Room Length', 'theater-room-designer'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="default_room_length" name="default_room_length" value="<?php echo esc_attr($settings['default_room_length']); ?>" step="0.1" min="1" max="100" />
                        <p class="description"><?php _e('Default length when creating a new room design.', 'theater-room-designer'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="default_room_height"><?php _e('Default Room Height', 'theater-room-designer'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="default_room_height" name="default_room_height" value="<?php echo esc_attr($settings['default_room_height']); ?>" step="0.1" min="1" max="20" />
                        <p class="description"><?php _e('Default height when creating a new room design.', 'theater-room-designer'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="default_screen_size"><?php _e('Default Screen Size', 'theater-room-designer'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="default_screen_size" name="default_screen_size" value="<?php echo esc_attr($settings['default_screen_size']); ?>" min="32" max="150" />
                        <p class="description"><?php _e('Default screen size in inches.', 'theater-room-designer'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="default_speaker_config"><?php _e('Default Speaker Configuration', 'theater-room-designer'); ?></label>
                    </th>
                    <td>
                        <select id="default_speaker_config" name="default_speaker_config">
                            <option value="2.1" <?php selected($settings['default_speaker_config'], '2.1'); ?>>2.1 Stereo</option>
                            <option value="5.1" <?php selected($settings['default_speaker_config'], '5.1'); ?>>5.1 Surround</option>
                            <option value="7.1" <?php selected($settings['default_speaker_config'], '7.1'); ?>>7.1 Surround</option>
                            <option value="9.1" <?php selected($settings['default_speaker_config'], '9.1'); ?>>9.1 Atmos</option>
                        </select>
                        <p class="description"><?php _e('Default speaker configuration for new designs.', 'theater-room-designer'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Guest Features', 'theater-room-designer'); ?></th>
                    <td>
                        <fieldset>
                            <label for="enable_guest_saving">
                                <input type="checkbox" id="enable_guest_saving" name="enable_guest_saving" value="1" <?php checked($settings['enable_guest_saving'], 1); ?> />
                                <?php _e('Allow guests to save designs', 'theater-room-designer'); ?>
                            </label>
                            <p class="description"><?php _e('Enable non-logged-in users to save their room designs.', 'theater-room-designer'); ?></p>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="max_saved_designs"><?php _e('Maximum Saved Designs', 'theater-room-designer'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="max_saved_designs" name="max_saved_designs" value="<?php echo esc_attr($settings['max_saved_designs']); ?>" min="1" max="100" />
                        <p class="description"><?php _e('Maximum number of designs a user can save (0 for unlimited).', 'theater-room-designer'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Display Options', 'theater-room-designer'); ?></th>
                    <td>
                        <fieldset>
                            <label for="show_branding">
                                <input type="checkbox" id="show_branding" name="show_branding" value="1" <?php checked($settings['show_branding'], 1); ?> />
                                <?php _e('Show plugin branding', 'theater-room-designer'); ?>
                            </label>
                            <br>
                            <label for="enable_social_sharing">
                                <input type="checkbox" id="enable_social_sharing" name="enable_social_sharing" value="1" <?php checked($settings['enable_social_sharing'], 1); ?> />
                                <?php _e('Enable social sharing buttons', 'theater-room-designer'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <h2><?php _e('Advanced Settings', 'theater-room-designer'); ?></h2>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><?php _e('Performance', 'theater-room-designer'); ?></th>
                    <td>
                        <button type="button" id="trd-clear-cache" class="button">
                            <?php _e('Clear 3D Model Cache', 'theater-room-designer'); ?>
                        </button>
                        <p class="description"><?php _e('Clear cached 3D models to free up space and force reload.', 'theater-room-designer'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Data Management', 'theater-room-designer'); ?></th>
                    <td>
                        <button type="button" id="trd-export-data" class="button">
                            <?php _e('Export All Data', 'theater-room-designer'); ?>
                        </button>
                        <button type="button" id="trd-import-data" class="button">
                            <?php _e('Import Data', 'theater-room-designer'); ?>
                        </button>
                        <p class="description"><?php _e('Export or import room design data for backup or migration.', 'theater-room-designer'); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    $('#trd-clear-cache').on('click', function() {
        if (confirm('<?php _e('Are you sure you want to clear the cache?', 'theater-room-designer'); ?>')) {
            // AJAX call to clear cache
            $.post(ajaxurl, {
                action: 'trd_clear_cache',
                nonce: '<?php echo wp_create_nonce('trd_admin_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    alert('<?php _e('Cache cleared successfully!', 'theater-room-designer'); ?>');
                } else {
                    alert('<?php _e('Error clearing cache.', 'theater-room-designer'); ?>');
                }
            });
        }
    });
    
    $('#trd-export-data').on('click', function() {
        window.location.href = ajaxurl + '?action=trd_export_data&nonce=<?php echo wp_create_nonce('trd_admin_nonce'); ?>';
    });
});
</script>