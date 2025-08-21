<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get plugin settings
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

<div id="theater-room-designer" class="trd-container" style="width: <?php echo esc_attr($atts['width']); ?>; height: <?php echo esc_attr($atts['height']); ?>;">
    
    <!-- Loading Screen -->
    <div id="trd-loading" class="trd-loading">
        <div class="trd-spinner"></div>
        <p><?php _e('Loading Theater Room Designer...', 'theater-room-designer'); ?></p>
    </div>
    
    <!-- Main Interface -->
    <div id="trd-main-interface" class="trd-main-interface" style="display: none;">
        
        <!-- Toolbar -->
        <div class="trd-toolbar">
            <div class="trd-toolbar-section">
                <button id="trd-new-design" class="trd-btn trd-btn-primary">
                    <span class="trd-icon">⊞</span>
                    <?php _e('New Design', 'theater-room-designer'); ?>
                </button>
                
                <?php if ($atts['allow_save'] === 'true'): ?>
                <button id="trd-save-design" class="trd-btn trd-btn-secondary">
                    <span class="trd-icon">💾</span>
                    <?php _e('Save', 'theater-room-designer'); ?>
                </button>
                <?php endif; ?>
                
                <?php if ($atts['show_saved'] === 'true'): ?>
                <button id="trd-load-design" class="trd-btn trd-btn-secondary">
                    <span class="trd-icon">📁</span>
                    <?php _e('Load', 'theater-room-designer'); ?>
                </button>
                <?php endif; ?>
                
                <button id="trd-export-design" class="trd-btn trd-btn-secondary">
                    <span class="trd-icon">📤</span>
                    <?php _e('Export', 'theater-room-designer'); ?>
                </button>
            </div>
            
            <div class="trd-toolbar-section">
                <button id="trd-toggle-view" class="trd-btn trd-btn-secondary">
                    <span class="trd-icon">👁</span>
                    <?php _e('3D View', 'theater-room-designer'); ?>
                </button>
                
                <button id="trd-help" class="trd-btn trd-btn-secondary">
                    <span class="trd-icon">❓</span>
                    <?php _e('Help', 'theater-room-designer'); ?>
                </button>
            </div>
        </div>
        
        <!-- Main Content Area - Audio Advice Layout -->
        <div class="trd-content trd-audio-advice-layout">
            
            <!-- Left Panel - Controls (Audio Advice Style) -->
            <div class="trd-panel trd-panel-left trd-controls-panel" id="trd-controls-panel">
                
                <!-- Room Dimensions -->
                <div class="trd-section">
                    <h3 class="trd-section-title">
                        <span class="trd-icon">📐</span>
                        <?php _e('Room Dimensions', 'theater-room-designer'); ?>
                    </h3>
                    <div class="trd-form-group">
                        <label for="trd-room-width"><?php _e('Width', 'theater-room-designer'); ?> (<?php echo $settings['measurement_unit']; ?>)</label>
                        <input type="number" id="trd-room-width" class="trd-input" value="<?php echo esc_attr($settings['default_room_width']); ?>" step="0.1" min="1" max="100">
                    </div>
                    <div class="trd-form-group">
                        <label for="trd-room-length"><?php _e('Length', 'theater-room-designer'); ?> (<?php echo $settings['measurement_unit']; ?>)</label>
                        <input type="number" id="trd-room-length" class="trd-input" value="<?php echo esc_attr($settings['default_room_length']); ?>" step="0.1" min="1" max="100">
                    </div>
                    <div class="trd-form-group">
                        <label for="trd-room-height"><?php _e('Height', 'theater-room-designer'); ?> (<?php echo $settings['measurement_unit']; ?>)</label>
                        <input type="number" id="trd-room-height" class="trd-input" value="<?php echo esc_attr($settings['default_room_height']); ?>" step="0.1" min="1" max="20">
                    </div>
                </div>
                
                <!-- Screen Configuration -->
                <div class="trd-section">
                    <h3 class="trd-section-title">
                        <span class="trd-icon">📺</span>
                        <?php _e('Screen Setup', 'theater-room-designer'); ?>
                    </h3>
                    <div class="trd-form-group">
                        <label for="trd-screen-type"><?php _e('Screen Type', 'theater-room-designer'); ?></label>
                        <select id="trd-screen-type" class="trd-select">
                            <option value="tv"><?php _e('TV/Display', 'theater-room-designer'); ?></option>
                            <option value="projector"><?php _e('Projector Screen', 'theater-room-designer'); ?></option>
                        </select>
                    </div>
                    <div class="trd-form-group">
                        <label for="trd-screen-size"><?php _e('Screen Size', 'theater-room-designer'); ?> (inches)</label>
                        <input type="number" id="trd-screen-size" class="trd-input" value="<?php echo esc_attr($settings['default_screen_size']); ?>" min="32" max="150">
                    </div>
                    <div class="trd-form-group">
                        <label for="trd-screen-position"><?php _e('Screen Position', 'theater-room-designer'); ?></label>
                        <select id="trd-screen-position" class="trd-select">
                            <option value="front"><?php _e('Front Wall', 'theater-room-designer'); ?></option>
                            <option value="back"><?php _e('Back Wall', 'theater-room-designer'); ?></option>
                            <option value="left"><?php _e('Left Wall', 'theater-room-designer'); ?></option>
                            <option value="right"><?php _e('Right Wall', 'theater-room-designer'); ?></option>
                        </select>
                    </div>
                </div>
                
                <!-- Speaker Configuration -->
                <div class="trd-section">
                    <h3 class="trd-section-title">
                        <span class="trd-icon">🔊</span>
                        <?php _e('Audio Setup', 'theater-room-designer'); ?>
                    </h3>
                    <div class="trd-form-group">
                        <label for="trd-speaker-config"><?php _e('Speaker Configuration', 'theater-room-designer'); ?></label>
                        <select id="trd-speaker-config" class="trd-select">
                            <option value="2.1" <?php selected($settings['default_speaker_config'], '2.1'); ?>>2.1 Stereo</option>
                            <option value="5.1" <?php selected($settings['default_speaker_config'], '5.1'); ?>>5.1 Surround</option>
                            <option value="7.1" <?php selected($settings['default_speaker_config'], '7.1'); ?>>7.1 Surround</option>
                            <option value="9.1" <?php selected($settings['default_speaker_config'], '9.1'); ?>>9.1 Atmos</option>
                        </select>
                    </div>
                    <div class="trd-form-group">
                        <label>
                            <input type="checkbox" id="trd-auto-placement" checked>
                            <?php _e('Auto-optimize speaker placement', 'theater-room-designer'); ?>
                        </label>
                    </div>
                </div>
                
                <!-- Seating Configuration -->
                <div class="trd-section">
                    <h3 class="trd-section-title">
                        <span class="trd-icon">🪑</span>
                        <?php _e('Seating Arrangement', 'theater-room-designer'); ?>
                    </h3>
                    <div class="trd-form-group">
                        <label for="trd-seating-rows"><?php _e('Number of Rows', 'theater-room-designer'); ?></label>
                        <input type="number" id="trd-seating-rows" class="trd-input" value="2" min="1" max="5">
                    </div>
                    <div class="trd-form-group">
                        <label for="trd-seats-per-row"><?php _e('Seats per Row', 'theater-room-designer'); ?></label>
                        <input type="number" id="trd-seats-per-row" class="trd-input" value="3" min="1" max="10">
                    </div>
                    <div class="trd-form-group">
                        <label for="trd-seating-type"><?php _e('Seating Type', 'theater-room-designer'); ?></label>
                        <select id="trd-seating-type" class="trd-select">
                            <option value="recliner"><?php _e('Recliners', 'theater-room-designer'); ?></option>
                            <option value="sofa"><?php _e('Sofas', 'theater-room-designer'); ?></option>
                            <option value="chair"><?php _e('Chairs', 'theater-room-designer'); ?></option>
                        </select>
                    </div>
                </div>
                
                <!-- Additional Features -->
                <div class="trd-section">
                    <h3 class="trd-section-title">
                        <span class="trd-icon">⚙️</span>
                        <?php _e('Additional Features', 'theater-room-designer'); ?>
                    </h3>
                    <div class="trd-form-group">
                        <label>
                            <input type="checkbox" id="trd-add-bar">
                            <?php _e('Add snack bar/counter', 'theater-room-designer'); ?>
                        </label>
                    </div>
                    <div class="trd-form-group">
                        <label>
                            <input type="checkbox" id="trd-add-lighting">
                            <?php _e('Add ambient lighting', 'theater-room-designer'); ?>
                        </label>
                    </div>
                    <div class="trd-form-group">
                        <label>
                            <input type="checkbox" id="trd-add-carpet">
                            <?php _e('Add carpet/flooring', 'theater-room-designer'); ?>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Center Panel - 3D Visualization -->
            <div class="trd-panel trd-panel-center" id="trd-viewer-panel">
                <div class="trd-viewport">
                    <div id="trd-3d-container" class="trd-3d-container">
                        <!-- Three.js canvas will be inserted here -->
                    </div>
                    <div class="trd-viewport-controls">
                        <button id="trd-reset-view" class="trd-btn trd-btn-small">
                            <span class="trd-icon">🔄</span>
                            <?php _e('Reset View', 'theater-room-designer'); ?>
                        </button>
                        <button id="trd-fullscreen" class="trd-btn trd-btn-small">
                            <span class="trd-icon">⛶</span>
                            <?php _e('Fullscreen', 'theater-room-designer'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- View Mode Tabs -->
                <div class="trd-view-tabs">
                    <button class="trd-tab-btn active" data-view="3d">
                        <span class="trd-icon">🏗️</span>
                        <?php _e('Room Layout', 'theater-room-designer'); ?>
                    </button>
                    <button class="trd-tab-btn" data-view="viewer">
                        <span class="trd-icon">👁️</span>
                        <?php _e('Customer View', 'theater-room-designer'); ?>
                    </button>
                    <button class="trd-tab-btn" data-view="top">
                        <span class="trd-icon">⬛</span>
                        <?php _e('Top View', 'theater-room-designer'); ?>
                    </button>
                    <button class="trd-tab-btn" data-view="side">
                        <span class="trd-icon">▭</span>
                        <?php _e('Side View', 'theater-room-designer'); ?>
                    </button>
                </div>
            </div>
            
            <!-- Right Panel - Information & Recommendations -->
            <div class="trd-panel trd-panel-right" id="trd-info-panel">
                
                <!-- Design Summary -->
                <div class="trd-section">
                    <h3 class="trd-section-title">
                        <span class="trd-icon">📊</span>
                        <?php _e('Design Summary', 'theater-room-designer'); ?>
                    </h3>
                    <div id="trd-design-summary" class="trd-summary">
                        <!-- Summary will be populated by JavaScript -->
                    </div>
                </div>
                
                <!-- Recommendations -->
                <div class="trd-section">
                    <h3 class="trd-section-title">
                        <span class="trd-icon">💡</span>
                        <?php _e('Recommendations', 'theater-room-designer'); ?>
                    </h3>
                    <div id="trd-recommendations" class="trd-recommendations">
                        <!-- Recommendations will be populated by JavaScript -->
                    </div>
                </div>
                
                <!-- Equipment List -->
                <div class="trd-section">
                    <h3 class="trd-section-title">
                        <span class="trd-icon">🛒</span>
                        <?php _e('Equipment List', 'theater-room-designer'); ?>
                    </h3>
                    <div id="trd-equipment-list" class="trd-equipment-list">
                        <!-- Equipment list will be populated by JavaScript -->
                    </div>
                </div>
                
                <?php if ($settings['enable_social_sharing']): ?>
                <!-- Social Sharing -->
                <div class="trd-section">
                    <h3 class="trd-section-title">
                        <span class="trd-icon">📤</span>
                        <?php _e('Share Design', 'theater-room-designer'); ?>
                    </h3>
                    <div class="trd-share-buttons">
                        <button id="trd-share-facebook" class="trd-btn trd-btn-social trd-btn-facebook">Facebook</button>
                        <button id="trd-share-twitter" class="trd-btn trd-btn-social trd-btn-twitter">Twitter</button>
                        <button id="trd-share-pinterest" class="trd-btn trd-btn-social trd-btn-pinterest">Pinterest</button>
                        <button id="trd-copy-link" class="trd-btn trd-btn-social trd-btn-link"><?php _e('Copy Link', 'theater-room-designer'); ?></button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($settings['show_branding']): ?>
        <!-- Plugin Branding -->
        <div class="trd-branding">
            <p><?php _e('Powered by Theater Room Designer Plugin', 'theater-room-designer'); ?></p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Modals -->
    
    <!-- Save Design Modal -->
    <div id="trd-save-modal" class="trd-modal" style="display: none;">
        <div class="trd-modal-content">
            <div class="trd-modal-header">
                <h3><?php _e('Save Design', 'theater-room-designer'); ?></h3>
                <button class="trd-modal-close">&times;</button>
            </div>
            <div class="trd-modal-body">
                <form id="trd-save-form">
                    <div class="trd-form-group">
                        <label for="trd-design-name"><?php _e('Design Name', 'theater-room-designer'); ?></label>
                        <input type="text" id="trd-design-name" class="trd-input" required placeholder="<?php _e('Enter design name...', 'theater-room-designer'); ?>">
                    </div>
                    <div class="trd-form-group">
                        <label for="trd-design-description"><?php _e('Description (Optional)', 'theater-room-designer'); ?></label>
                        <textarea id="trd-design-description" class="trd-textarea" rows="3" placeholder="<?php _e('Add a description...', 'theater-room-designer'); ?>"></textarea>
                    </div>
                </form>
            </div>
            <div class="trd-modal-footer">
                <button id="trd-save-confirm" class="trd-btn trd-btn-primary"><?php _e('Save Design', 'theater-room-designer'); ?></button>
                <button class="trd-btn trd-btn-secondary trd-modal-close"><?php _e('Cancel', 'theater-room-designer'); ?></button>
            </div>
        </div>
    </div>
    
    <!-- Load Design Modal -->
    <div id="trd-load-modal" class="trd-modal" style="display: none;">
        <div class="trd-modal-content">
            <div class="trd-modal-header">
                <h3><?php _e('Load Design', 'theater-room-designer'); ?></h3>
                <button class="trd-modal-close">&times;</button>
            </div>
            <div class="trd-modal-body">
                <div id="trd-saved-designs" class="trd-saved-designs">
                    <!-- Saved designs will be loaded here -->
                </div>
            </div>
            <div class="trd-modal-footer">
                <button class="trd-btn trd-btn-secondary trd-modal-close"><?php _e('Close', 'theater-room-designer'); ?></button>
            </div>
        </div>
    </div>
    
    <!-- Help Modal -->
    <div id="trd-help-modal" class="trd-modal" style="display: none;">
        <div class="trd-modal-content">
            <div class="trd-modal-header">
                <h3><?php _e('How to Use Theater Room Designer', 'theater-room-designer'); ?></h3>
                <button class="trd-modal-close">&times;</button>
            </div>
            <div class="trd-modal-body">
                <div class="trd-help-content">
                    <h4><?php _e('Getting Started', 'theater-room-designer'); ?></h4>
                    <ol>
                        <li><?php _e('Enter your room dimensions in the left panel', 'theater-room-designer'); ?></li>
                        <li><?php _e('Choose your screen type and size', 'theater-room-designer'); ?></li>
                        <li><?php _e('Select your speaker configuration', 'theater-room-designer'); ?></li>
                        <li><?php _e('Configure your seating arrangement', 'theater-room-designer'); ?></li>
                        <li><?php _e('View your design in the 3D visualization', 'theater-room-designer'); ?></li>
                    </ol>
                    
                    <h4><?php _e('3D Controls', 'theater-room-designer'); ?></h4>
                    <ul>
                        <li><?php _e('Left click and drag to rotate the view', 'theater-room-designer'); ?></li>
                        <li><?php _e('Right click and drag to pan the view', 'theater-room-designer'); ?></li>
                        <li><?php _e('Scroll to zoom in and out', 'theater-room-designer'); ?></li>
                        <li><?php _e('Use the Reset View button to return to the default view', 'theater-room-designer'); ?></li>
                    </ul>
                    
                    <h4><?php _e('Tips for Best Results', 'theater-room-designer'); ?></h4>
                    <ul>
                        <li><?php _e('Keep the viewing distance 1.5-2.5 times the screen width', 'theater-room-designer'); ?></li>
                        <li><?php _e('Position speakers at ear level when seated', 'theater-room-designer'); ?></li>
                        <li><?php _e('Leave space behind seating for speaker placement', 'theater-room-designer'); ?></li>
                        <li><?php _e('Consider acoustic treatment for better sound quality', 'theater-room-designer'); ?></li>
                    </ul>
                </div>
            </div>
            <div class="trd-modal-footer">
                <button class="trd-btn trd-btn-primary trd-modal-close"><?php _e('Got It!', 'theater-room-designer'); ?></button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
// Pass PHP settings to JavaScript
window.trdSettings = <?php echo json_encode($settings); ?>;
window.trdAtts = <?php echo json_encode($atts); ?>;
</script>