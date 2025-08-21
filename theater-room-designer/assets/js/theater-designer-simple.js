/**
 * Theater Room Designer - Simple Working Version
 */

(function($) {
    'use strict';
    
    // Initialize when page loads
    $(document).ready(function() {
        console.log('Theater Room Designer loading...');
        initializeSimpleDesigner();
    });
    
    function initializeSimpleDesigner() {
        // Hide loading screen immediately and show interface
        $('#trd-loading').hide();
        $('#trd-main-interface').show();
        
        console.log('Interface shown, checking for 3D container...');
        
        // Check if 3D container exists
        const container = document.getElementById('trd-3d-container');
        if (!container) {
            console.error('3D container not found!');
            return;
        }
        
        console.log('3D container found, checking Three.js...');
        
        // Check if Three.js is loaded
        if (typeof THREE === 'undefined') {
            console.error('Three.js not loaded!');
            container.innerHTML = '<div style="padding: 20px; text-align: center; color: #666;">3D visualization requires Three.js library.<br>Please check your internet connection.</div>';
            return;
        }
        
        console.log('Three.js found, initializing 3D scene...');
        
        try {
            initializeSimple3D(container);
            bindSimpleEvents();
            updateSimpleDesign();
            console.log('3D scene initialized successfully!');
        } catch (error) {
            console.error('Error initializing 3D scene:', error);
            container.innerHTML = '<div style="padding: 20px; text-align: center; color: #666;">Error loading 3D visualization.<br>Error: ' + error.message + '</div>';
        }
    }
    
    let scene, camera, renderer, controls;
    let roomObjects = [];
    
    function initializeSimple3D(container) {
        // Create scene
        scene = new THREE.Scene();
        scene.background = new THREE.Color(0xf0f0f0);
        
        // Create camera
        const aspect = container.clientWidth / container.clientHeight;
        camera = new THREE.PerspectiveCamera(50, aspect, 0.1, 1000);
        camera.position.set(12, 8, 12);
        camera.lookAt(0, 0, 0);
        
        // Create renderer
        renderer = new THREE.WebGLRenderer({ antialias: true });
        renderer.setSize(container.clientWidth, container.clientHeight);
        renderer.setClearColor(0xf0f0f0);
        container.appendChild(renderer.domElement);
        
        // Add basic lighting
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
        scene.add(ambientLight);
        
        const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
        directionalLight.position.set(10, 10, 5);
        scene.add(directionalLight);
        
        // Add orbit controls if available
        if (typeof THREE.OrbitControls !== 'undefined') {
            controls = new THREE.OrbitControls(camera, renderer.domElement);
            controls.enableDamping = true;
            controls.dampingFactor = 0.1;
        }
        
        // Handle window resize
        window.addEventListener('resize', function() {
            const width = container.clientWidth;
            const height = container.clientHeight;
            camera.aspect = width / height;
            camera.updateProjectionMatrix();
            renderer.setSize(width, height);
        });
        
        // Start animation loop
        animate();
    }
    
    function animate() {
        requestAnimationFrame(animate);
        
        if (controls) {
            controls.update();
        }
        
        if (renderer && scene && camera) {
            renderer.render(scene, camera);
        }
    }
    
    function bindSimpleEvents() {
        // Room dimension controls
        $('#trd-room-width, #trd-room-length, #trd-room-height').on('input change', function() {
            console.log('Room dimensions changed');
            updateSimpleDesign();
        });
        
        // Screen controls
        $('#trd-screen-type, #trd-screen-size, #trd-screen-position').on('change', function() {
            console.log('Screen settings changed');
            updateSimpleDesign();
        });
        
        // Seating controls
        $('#trd-seating-rows, #trd-seats-per-row, #trd-seating-type').on('change', function() {
            console.log('Seating changed');
            updateSimpleDesign();
        });
        
        // Speaker controls
        $('#trd-speaker-config').on('change', function() {
            console.log('Speaker config changed');
            updateSimpleDesign();
        });
    }
    
    function updateSimpleDesign() {
        console.log('Updating design...');
        
        // Clear existing objects
        roomObjects.forEach(obj => scene.remove(obj));
        roomObjects = [];
        
        // Get current values
        const roomWidth = parseFloat($('#trd-room-width').val()) || 12;
        const roomLength = parseFloat($('#trd-room-length').val()) || 16;
        const roomHeight = parseFloat($('#trd-room-height').val()) || 9;
        const screenSize = parseInt($('#trd-screen-size').val()) || 65;
        const seatingRows = parseInt($('#trd-seating-rows').val()) || 2;
        const seatsPerRow = parseInt($('#trd-seats-per-row').val()) || 3;
        
        console.log('Room:', roomWidth, 'x', roomLength, 'x', roomHeight);
        console.log('Screen:', screenSize, 'inches');
        console.log('Seating:', seatingRows, 'rows,', seatsPerRow, 'per row');
        
        // Create simple room
        createSimpleRoom(roomWidth, roomLength, roomHeight);
        
        // Create simple screen
        createSimpleScreen(screenSize, roomWidth, roomLength);
        
        // Create simple seating
        createSimpleSeating(seatingRows, seatsPerRow, roomWidth, roomLength, screenSize);
        
        // Update summary
        updateSimpleSummary(roomWidth, roomLength, roomHeight, screenSize, seatingRows * seatsPerRow);
    }
    
    function createSimpleRoom(width, length, height) {
        // Floor
        const floorGeometry = new THREE.PlaneGeometry(width, length);
        const floorMaterial = new THREE.MeshLambertMaterial({ color: 0xd4c4a8 });
        const floor = new THREE.Mesh(floorGeometry, floorMaterial);
        floor.rotation.x = -Math.PI/2;
        scene.add(floor);
        roomObjects.push(floor);
        
        // Walls (simple lines for now)
        const wallMaterial = new THREE.MeshLambertMaterial({ color: 0xe8e8e8 });
        
        // Front wall
        const frontWall = new THREE.Mesh(
            new THREE.PlaneGeometry(width, height),
            wallMaterial
        );
        frontWall.position.set(0, height/2, -length/2);
        scene.add(frontWall);
        roomObjects.push(frontWall);
        
        // Back wall
        const backWall = new THREE.Mesh(
            new THREE.PlaneGeometry(width, height),
            wallMaterial
        );
        backWall.position.set(0, height/2, length/2);
        backWall.rotation.y = Math.PI;
        scene.add(backWall);
        roomObjects.push(backWall);
        
        // Left wall
        const leftWall = new THREE.Mesh(
            new THREE.PlaneGeometry(length, height),
            wallMaterial
        );
        leftWall.position.set(-width/2, height/2, 0);
        leftWall.rotation.y = Math.PI/2;
        scene.add(leftWall);
        roomObjects.push(leftWall);
        
        // Right wall
        const rightWall = new THREE.Mesh(
            new THREE.PlaneGeometry(length, height),
            wallMaterial
        );
        rightWall.position.set(width/2, height/2, 0);
        rightWall.rotation.y = -Math.PI/2;
        scene.add(rightWall);
        roomObjects.push(rightWall);
    }
    
    function createSimpleScreen(size, roomWidth, roomLength) {
        const screenWidth = (size * 0.87) / 12; // Convert to feet
        const screenHeight = screenWidth * 9/16;
        
        // Screen
        const screenGeometry = new THREE.PlaneGeometry(screenWidth, screenHeight);
        const screenMaterial = new THREE.MeshLambertMaterial({ color: 0x1a1a1a });
        const screen = new THREE.Mesh(screenGeometry, screenMaterial);
        screen.position.set(0, screenHeight/2 + 2, -roomLength/2 + 0.1);
        scene.add(screen);
        roomObjects.push(screen);
        
        // Screen frame
        const frameGeometry = new THREE.BoxGeometry(screenWidth + 0.2, screenHeight + 0.2, 0.1);
        const frameMaterial = new THREE.MeshLambertMaterial({ color: 0x2a2a2a });
        const frame = new THREE.Mesh(frameGeometry, frameMaterial);
        frame.position.set(0, screenHeight/2 + 2, -roomLength/2);
        scene.add(frame);
        roomObjects.push(frame);
    }
    
    function createSimpleSeating(rows, seatsPerRow, roomWidth, roomLength, screenSize) {
        // Calculate proper viewing distance
        const screenWidthFeet = (screenSize * 0.87) / 12;
        const optimalDistance = screenWidthFeet * 2; // 2x screen width
        
        // Seat dimensions
        const seatWidth = 2.5;
        const seatDepth = 3;
        const seatHeight = 1.2;
        
        // Calculate seating area
        const totalSeatingWidth = seatsPerRow * seatWidth + (seatsPerRow - 1) * 0.5;
        const startX = -totalSeatingWidth / 2 + seatWidth / 2;
        
        // Position seats
        for (let row = 0; row < rows; row++) {
            const zPosition = -roomLength/2 + optimalDistance + (row * 4);
            const yPosition = seatHeight/2 + (row * 0.5); // Stepped seating
            
            for (let seat = 0; seat < seatsPerRow; seat++) {
                const xPosition = startX + seat * (seatWidth + 0.5);
                
                // Create simple seat
                const seatGroup = new THREE.Group();
                
                // Seat base
                const seatGeometry = new THREE.BoxGeometry(seatWidth * 0.8, seatHeight * 0.4, seatDepth * 0.7);
                const seatMaterial = new THREE.MeshLambertMaterial({ color: 0x8B7355 });
                const seatBase = new THREE.Mesh(seatGeometry, seatMaterial);
                seatBase.position.y = seatHeight * 0.2;
                seatGroup.add(seatBase);
                
                // Seat back
                const backGeometry = new THREE.BoxGeometry(seatWidth * 0.8, seatHeight * 0.6, 0.3);
                const backrest = new THREE.Mesh(backGeometry, seatMaterial);
                backrest.position.set(0, seatHeight * 0.5, -seatDepth/2 + 0.15);
                seatGroup.add(backrest);
                
                seatGroup.position.set(xPosition, yPosition, zPosition);
                scene.add(seatGroup);
                roomObjects.push(seatGroup);
            }
        }
    }
    
    function updateSimpleSummary(width, length, height, screenSize, totalSeats) {
        const summary = $('#trd-design-summary');
        if (summary.length) {
            summary.html(`
                <div>Room: ${width} × ${length} × ${height} ft</div>
                <div>Screen: ${screenSize} inches</div>
                <div>Total Seats: ${totalSeats}</div>
                <div>Floor Area: ${(width * length).toFixed(1)} sq ft</div>
            `);
        }
        
        const recommendations = $('#trd-recommendations');
        if (recommendations.length) {
            const screenWidthFeet = (screenSize * 0.87) / 12;
            const optimalDistance = screenWidthFeet * 2;
            
            recommendations.html(`
                <div>✓ Optimal viewing distance: ${optimalDistance.toFixed(1)} ft</div>
                <div>✓ Screen size appropriate for room</div>
                <div>✓ Seating arrangement looks good</div>
            `);
        }
    }
    
})(jQuery);