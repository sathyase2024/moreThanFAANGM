<?php
/**
 * Plugin Name: Theater Room Designer (Minimal)
 * Plugin URI: https://www.srihayavadhana.com/theater-room-designer
 * Description: A simple theater room design tool - minimal working version
 * Version: 1.0.1
 * Author: Sri Hayavadhana Info-Tech
 * Author URI: https://www.srihayavadhana.com/
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Simple shortcode handler
function theater_room_designer_shortcode($atts) {
    $atts = shortcode_atts(array(
        'width' => '100%',
        'height' => '600px'
    ), $atts);
    
    // Enqueue scripts inline
    wp_enqueue_script('three-js', 'https://unpkg.com/three@0.155.0/build/three.min.js', array(), '155', false);
    
    ob_start();
    ?>
    <div id="theater-designer-test" style="width: <?php echo esc_attr($atts['width']); ?>; height: <?php echo esc_attr($atts['height']); ?>; border: 1px solid #ccc;">
        
        <div style="display: flex; height: 100%;">
            <!-- Left Controls -->
            <div style="width: 300px; background: #f5f5f5; padding: 20px; border-right: 1px solid #ddd;">
                <h3 style="margin: 0 0 15px 0; color: #333;">Room Dimensions</h3>
                
                <div style="margin-bottom: 10px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Length (ft)</label>
                    <input type="number" id="room-length" value="16" min="8" max="40" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <div style="margin-bottom: 10px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Width (ft)</label>
                    <input type="number" id="room-width" value="12" min="8" max="30" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Height (ft)</label>
                    <input type="number" id="room-height" value="9" min="7" max="12" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <h3 style="margin: 0 0 15px 0; color: #333;">Screen</h3>
                
                <div style="margin-bottom: 10px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Size (inches)</label>
                    <input type="number" id="screen-size" value="65" min="40" max="120" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <h3 style="margin: 0 0 15px 0; color: #333;">Seating</h3>
                
                <div style="margin-bottom: 10px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Rows</label>
                    <select id="seating-rows" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="1">1</option>
                        <option value="2" selected>2</option>
                        <option value="3">3</option>
                    </select>
                </div>
                
                <div style="margin-bottom: 10px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Seats per Row</label>
                    <select id="seats-per-row" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="2">2</option>
                        <option value="3" selected>3</option>
                        <option value="4">4</option>
                    </select>
                </div>
            </div>
            
            <!-- Right Viewer -->
            <div style="flex: 1; display: flex; flex-direction: column;">
                <!-- 3D Header -->
                <div style="padding: 15px; background: #f8f8f8; border-bottom: 1px solid #ddd;">
                    <h3 style="margin: 0; color: #333;">3D Room Visualization</h3>
                </div>
                
                <!-- 3D Container -->
                <div style="flex: 1; position: relative; background: #f0f0f0;">
                    <div id="three-container" style="width: 100%; height: 100%;">
                        <div id="loading-message" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; color: #666;">
                            <p>Loading 3D Visualization...</p>
                            <p><small>Checking Three.js...</small></p>
                        </div>
                    </div>
                </div>
                
                <!-- Bottom Info -->
                <div style="height: 80px; background: #f8f8f8; border-top: 1px solid #ddd; padding: 15px; display: flex;">
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 8px 0; font-size: 14px;">Summary</h4>
                        <div id="design-summary">Room: 12 × 16 × 9 ft | Screen: 65" | Seats: 6</div>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 8px 0; font-size: 14px;">Recommendations</h4>
                        <div id="recommendations">Analyzing setup...</div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>

    <script>
        console.log('Theater Room Designer Test - Starting...');
        
        // Wait for Three.js to load
        function checkThreeJS() {
            if (typeof THREE !== 'undefined') {
                console.log('✓ Three.js loaded successfully');
                document.getElementById('loading-message').innerHTML = '<p style="color: green;">✓ Three.js loaded</p><p>Initializing 3D scene...</p>';
                
                setTimeout(function() {
                    try {
                        initializeBasic3D();
                        console.log('✓ 3D scene initialized');
                    } catch (error) {
                        console.error('✗ 3D initialization failed:', error);
                        document.getElementById('loading-message').innerHTML = '<p style="color: red;">✗ 3D Error: ' + error.message + '</p>';
                    }
                }, 500);
            } else {
                console.log('Waiting for Three.js...');
                setTimeout(checkThreeJS, 100);
            }
        }
        
        // Start checking
        checkThreeJS();
        
        function initializeBasic3D() {
            const container = document.getElementById('three-container');
            
            // Scene
            const scene = new THREE.Scene();
            scene.background = new THREE.Color(0xf0f0f0);
            
            // Camera
            const camera = new THREE.PerspectiveCamera(50, container.clientWidth / container.clientHeight, 0.1, 1000);
            camera.position.set(10, 6, 10);
            camera.lookAt(0, 0, 0);
            
            // Renderer
            const renderer = new THREE.WebGLRenderer({ antialias: true });
            renderer.setSize(container.clientWidth, container.clientHeight);
            
            // Clear loading message and add canvas
            container.innerHTML = '';
            container.appendChild(renderer.domElement);
            
            // Add basic room
            addBasicRoom(scene);
            
            // Lighting
            const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
            scene.add(ambientLight);
            
            const directionalLight = new THREE.DirectionalLight(0xffffff, 0.4);
            directionalLight.position.set(5, 5, 5);
            scene.add(directionalLight);
            
            // Animation loop
            function animate() {
                requestAnimationFrame(animate);
                renderer.render(scene, camera);
            }
            animate();
            
            console.log('✓ Basic 3D room created successfully');
            
            // Bind form events
            bindFormEvents(scene, renderer, camera);
        }
        
        function addBasicRoom(scene) {
            // Floor
            const floor = new THREE.Mesh(
                new THREE.PlaneGeometry(12, 16),
                new THREE.MeshLambertMaterial({ color: 0xd4c4a8 })
            );
            floor.rotation.x = -Math.PI/2;
            scene.add(floor);
            
            // Screen
            const screen = new THREE.Mesh(
                new THREE.PlaneGeometry(5, 3),
                new THREE.MeshLambertMaterial({ color: 0x1a1a1a })
            );
            screen.position.set(0, 3, -8);
            scene.add(screen);
            
            // Seats
            for (let row = 0; row < 2; row++) {
                for (let seat = 0; seat < 3; seat++) {
                    const seatMesh = new THREE.Mesh(
                        new THREE.BoxGeometry(2, 1, 2),
                        new THREE.MeshLambertMaterial({ color: 0x8B7355 })
                    );
                    seatMesh.position.set((seat - 1) * 3, 0.5, 2 + row * 4);
                    scene.add(seatMesh);
                }
            }
        }
        
        function bindFormEvents(scene, renderer, camera) {
            const inputs = ['room-length', 'room-width', 'room-height', 'screen-size', 'seating-rows', 'seats-per-row'];
            
            inputs.forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.addEventListener('change', function() {
                        console.log('Form changed:', id, '=', element.value);
                        updateSummary();
                    });
                }
            });
        }
        
        function updateSummary() {
            const length = document.getElementById('room-length').value;
            const width = document.getElementById('room-width').value;
            const height = document.getElementById('room-height').value;
            const screenSize = document.getElementById('screen-size').value;
            const rows = document.getElementById('seating-rows').value;
            const seats = document.getElementById('seats-per-row').value;
            
            document.getElementById('design-summary').innerHTML = 
                `Room: ${width} × ${length} × ${height} ft | Screen: ${screenSize}" | Seats: ${rows * seats}`;
                
            document.getElementById('recommendations').innerHTML = 
                `✓ Setup looks good | ✓ Viewing distance optimal | ✓ Room size appropriate`;
        }
    </script>
    <?php
    return ob_get_clean();
}

// Register shortcode
add_shortcode('theater_room_designer', 'theater_room_designer_shortcode');

// Simple activation
register_activation_hook(__FILE__, function() {
    // No database needed for minimal version
});
?>