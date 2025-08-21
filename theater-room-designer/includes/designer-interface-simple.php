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
    'measurement_unit' => 'feet',
));
?>

<div id="theater-room-designer" class="trd-container" style="width: <?php echo esc_attr($atts['width']); ?>; height: <?php echo esc_attr($atts['height']); ?>;">
    
    <!-- Loading Screen -->
    <div id="trd-loading" class="trd-loading">
        <div class="trd-spinner"></div>
        <p>Loading Theater Room Designer...</p>
    </div>
    
    <!-- Main Interface -->
    <div id="trd-main-interface" class="trd-main-interface" style="display: none;">
        
        <!-- Audio Advice Style Layout -->
        <div class="trd-audio-advice-layout">
            
            <!-- Left Side - Controls Panel -->
            <div class="trd-left-controls">
                
                <!-- Room Dimensions Section -->
                <div class="trd-control-section">
                    <h3>Room Dimensions</h3>
                    <div class="trd-form-row">
                        <label>Length (ft)</label>
                        <input type="number" id="trd-room-length" value="<?php echo esc_attr($settings['default_room_length']); ?>" step="0.5" min="8" max="40">
                    </div>
                    <div class="trd-form-row">
                        <label>Width (ft)</label>
                        <input type="number" id="trd-room-width" value="<?php echo esc_attr($settings['default_room_width']); ?>" step="0.5" min="8" max="30">
                    </div>
                    <div class="trd-form-row">
                        <label>Height (ft)</label>
                        <input type="number" id="trd-room-height" value="<?php echo esc_attr($settings['default_room_height']); ?>" step="0.5" min="7" max="12">
                    </div>
                </div>
                
                <!-- Screen Configuration Section -->
                <div class="trd-control-section">
                    <h3>Screen</h3>
                    <div class="trd-form-row">
                        <label>Type</label>
                        <select id="trd-screen-type">
                            <option value="tv">TV</option>
                            <option value="projector">Projector</option>
                        </select>
                    </div>
                    <div class="trd-form-row">
                        <label>Size (inches)</label>
                        <input type="number" id="trd-screen-size" value="<?php echo esc_attr($settings['default_screen_size']); ?>" min="40" max="120" step="5">
                    </div>
                    <div class="trd-form-row">
                        <label>Position</label>
                        <select id="trd-screen-position">
                            <option value="front">Front Wall</option>
                            <option value="back">Back Wall</option>
                        </select>
                    </div>
                </div>
                
                <!-- Seating Configuration Section -->
                <div class="trd-control-section">
                    <h3>Seating</h3>
                    <div class="trd-form-row">
                        <label>Rows</label>
                        <select id="trd-seating-rows">
                            <option value="1">1</option>
                            <option value="2" selected>2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                        </select>
                    </div>
                    <div class="trd-form-row">
                        <label>Seats per Row</label>
                        <select id="trd-seats-per-row">
                            <option value="2">2</option>
                            <option value="3" selected>3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                        </select>
                    </div>
                    <div class="trd-form-row">
                        <label>Type</label>
                        <select id="trd-seating-type">
                            <option value="recliner" selected>Recliners</option>
                            <option value="sofa">Sofa</option>
                            <option value="chair">Chairs</option>
                        </select>
                    </div>
                </div>
                
                <!-- Audio Configuration Section -->
                <div class="trd-control-section">
                    <h3>Audio</h3>
                    <div class="trd-form-row">
                        <label>Speaker Setup</label>
                        <select id="trd-speaker-config">
                            <option value="2.1">2.1 Stereo</option>
                            <option value="5.1" selected>5.1 Surround</option>
                            <option value="7.1">7.1 Surround</option>
                            <option value="9.1">9.1 Atmos</option>
                        </select>
                    </div>
                </div>
                
            </div>
            
            <!-- Right Side - Large 3D Visualization -->
            <div class="trd-right-viewport">
                <div class="trd-3d-header">
                    <h3>3D Room Visualization</h3>
                    <div class="trd-view-controls">
                        <button id="trd-reset-view" class="trd-view-btn">Reset View</button>
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
                        <h4>Design Summary</h4>
                        <div id="trd-design-summary" class="trd-summary-content">
                            Loading...
                        </div>
                    </div>
                    <div class="trd-info-section">
                        <h4>Recommendations</h4>
                        <div id="trd-recommendations" class="trd-recommendations-content">
                            Loading...
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
    </div>
</div>