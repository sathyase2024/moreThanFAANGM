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
        
        <!-- Audio Advice Style Layout -->
        <div class="trd-audio-advice-layout">
            
            <!-- Left Side - Controls Panel (Audio Advice Style) -->
            <div class="trd-left-controls">
                
                <!-- Room Dimensions Section -->
                <div class="trd-control-section">
                    <h3><?php _e('Room Dimensions', 'theater-room-designer'); ?></h3>
                    <div class="trd-form-row">
                        <label><?php _e('Length', 'theater-room-designer'); ?> (ft)</label>
                        <input type="number" id="trd-room-length" value="<?php echo esc_attr($settings['default_room_length']); ?>" step="0.5" min="8" max="40">
                    </div>
                    <div class="trd-form-row">
                        <label><?php _e('Width', 'theater-room-designer'); ?> (ft)</label>
                        <input type="number" id="trd-room-width" value="<?php echo esc_attr($settings['default_room_width']); ?>" step="0.5" min="8" max="30">
                    </div>
                    <div class="trd-form-row">
                        <label><?php _e('Height', 'theater-room-designer'); ?> (ft)</label>
                        <input type="number" id="trd-room-height" value="<?php echo esc_attr($settings['default_room_height']); ?>" step="0.5" min="7" max="12">
                    </div>
                </div>
                
                <!-- Screen Configuration Section -->
                <div class="trd-control-section">
                    <h3><?php _e('Screen', 'theater-room-designer'); ?></h3>
                    <div class="trd-form-row">
                        <label><?php _e('Type', 'theater-room-designer'); ?></label>
                        <select id="trd-screen-type">
                            <option value="tv"><?php _e('TV', 'theater-room-designer'); ?></option>
                            <option value="projector"><?php _e('Projector', 'theater-room-designer'); ?></option>
                        </select>
                    </div>
                    <div class="trd-form-row">
                        <label><?php _e('Size', 'theater-room-designer'); ?> (inches)</label>
                        <input type="number" id="trd-screen-size" value="<?php echo esc_attr($settings['default_screen_size']); ?>" min="40" max="120" step="5">
                    </div>
                    <div class="trd-form-row">
                        <label><?php _e('Position', 'theater-room-designer'); ?></label>
                        <select id="trd-screen-position">
                            <option value="front"><?php _e('Front Wall', 'theater-room-designer'); ?></option>
                            <option value="back"><?php _e('Back Wall', 'theater-room-designer'); ?></option>
                        </select>
                    </div>
                </div>
                
                <!-- Seating Configuration Section -->
                <div class="trd-control-section">
                    <h3><?php _e('Seating', 'theater-room-designer'); ?></h3>
                    <div class="trd-form-row">
                        <label><?php _e('Rows', 'theater-room-designer'); ?></label>
                        <select id="trd-seating-rows">
                            <option value="1">1</option>
                            <option value="2" selected>2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                        </select>
                    </div>
                    <div class="trd-form-row">
                        <label><?php _e('Seats per Row', 'theater-room-designer'); ?></label>
                        <select id="trd-seats-per-row">
                            <option value="2">2</option>
                            <option value="3" selected>3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                        </select>
                    </div>
                    <div class="trd-form-row">
                        <label><?php _e('Type', 'theater-room-designer'); ?></label>
                        <select id="trd-seating-type">
                            <option value="recliner" selected><?php _e('Recliners', 'theater-room-designer'); ?></option>
                            <option value="sofa"><?php _e('Sofa', 'theater-room-designer'); ?></option>
                            <option value="chair"><?php _e('Chairs', 'theater-room-designer'); ?></option>
                        </select>
                    </div>
                </div>
                
                <!-- Audio Configuration Section -->
                <div class="trd-control-section">
                    <h3><?php _e('Audio', 'theater-room-designer'); ?></h3>
                    <div class="trd-form-row">
                        <label><?php _e('Speaker Setup', 'theater-room-designer'); ?></label>
                        <select id="trd-speaker-config">
                            <option value="2.1">2.1 Stereo</option>
                            <option value="5.1" selected>5.1 Surround</option>
                            <option value="7.1">7.1 Surround</option>
                            <option value="9.1">9.1 Atmos</option>
                        </select>
                    </div>
                    <div class="trd-form-row">
                        <label>
                            <input type="checkbox" id="trd-auto-placement" checked>
                            <?php _e('Auto-optimize placement', 'theater-room-designer'); ?>
                        </label>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="trd-control-section trd-action-buttons">
                    <button id="trd-save-design" class="trd-action-btn trd-save-btn"><?php _e('Save Design', 'theater-room-designer'); ?></button>
                    <button id="trd-load-design" class="trd-action-btn trd-load-btn"><?php _e('Load Design', 'theater-room-designer'); ?></button>
                    <button id="trd-new-design" class="trd-action-btn trd-new-btn"><?php _e('New Design', 'theater-room-designer'); ?></button>
                </div>
                
            </div>
            
            <!-- Right Side - Large 3D Visualization (Audio Advice Style) -->
            <div class="trd-right-viewport">
                <div class="trd-3d-header">
                    <h3><?php _e('3D Room Visualization', 'theater-room-designer'); ?></h3>
                    <div class="trd-view-controls">
                        <button id="trd-reset-view" class="trd-view-btn"><?php _e('Reset View', 'theater-room-designer'); ?></button>
                        <button id="trd-fullscreen" class="trd-view-btn"><?php _e('Fullscreen', 'theater-room-designer'); ?></button>
                    </div>
                </div>
                
                <div class="trd-3d-viewport">
                    <div id="trd-3d-container" class="trd-3d-container">
                        <!-- Three.js canvas will be inserted here -->
                    </div>
                </div>
                
                <!-- Bottom Info Panel -->
                <div class="trd-bottom-info">
                    <div class="trd-info-section">
                        <h4><?php _e('Design Summary', 'theater-room-designer'); ?></h4>
                        <div id="trd-design-summary" class="trd-summary-content">
                            <!-- Summary will be populated by JavaScript -->
                        </div>
                    </div>
                    <div class="trd-info-section">
                        <h4><?php _e('Recommendations', 'theater-room-designer'); ?></h4>
                        <div id="trd-recommendations" class="trd-recommendations-content">
                            <!-- Recommendations will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
                
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