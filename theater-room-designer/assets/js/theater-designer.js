/**
 * Theater Room Designer - Main JavaScript
 */

(function($) {
    'use strict';
    
    // Global variables
    let scene, camera, renderer, controls;
    let roomMesh, floorMesh, ceilingMesh;
    let seats = [];
    let speakers = [];
    let screen = null;
    let currentDesign = {
        room: {
            width: 12,
            length: 16,
            height: 9
        },
        screen: {
            type: 'tv',
            size: 65,
            position: 'front'
        },
        speakers: {
            config: '5.1',
            autoPlacement: true
        },
        seating: {
            rows: 2,
            seatsPerRow: 3,
            type: 'recliner'
        },
        features: {
            bar: false,
            lighting: false,
            carpet: false
        }
    };
    
    // Initialize the plugin
    $(document).ready(function() {
        initializeDesigner();
    });
    
    function initializeDesigner() {
        // Hide loading screen and show main interface
        setTimeout(function() {
            $('#trd-loading').fadeOut(300, function() {
                $('#trd-main-interface').fadeIn(300);
                initializeThreeJS();
                bindEvents();
                updateDesign();
            });
        }, 1000);
    }
    
    function initializeThreeJS() {
        const container = document.getElementById('trd-3d-container');
        if (!container) return;
        
        // Scene
        scene = new THREE.Scene();
        scene.background = new THREE.Color(0x1a1a1a);
        
        // Camera - positioned for better theater room viewing
        const aspect = container.clientWidth / container.clientHeight;
        camera = new THREE.PerspectiveCamera(60, aspect, 0.1, 1000); // Reduced FOV for more realistic view
        
        // Position camera like a person standing in the back of the room
        camera.position.set(0, 8, 25); // Behind seating area, elevated view
        camera.lookAt(0, 2, 0); // Look toward front of room
        
        // Renderer with mobile optimizations
        renderer = new THREE.WebGLRenderer({ 
            antialias: window.devicePixelRatio <= 1, // Disable antialiasing on high DPI for performance
            powerPreference: "high-performance",
            precision: "mediump" // Use medium precision on mobile
        });
        
        // Set pixel ratio for crisp rendering on mobile
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        renderer.setSize(container.clientWidth, container.clientHeight);
        renderer.shadowMap.enabled = true;
        renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        
        // Mobile-specific renderer settings
        if (isMobile()) {
            renderer.shadowMap.enabled = false; // Disable shadows on mobile for performance
            renderer.setPixelRatio(1); // Reduce pixel ratio on mobile
        }
        
        container.appendChild(renderer.domElement);
        
        // Controls with mobile touch support
        if (typeof THREE.OrbitControls !== 'undefined') {
            controls = new THREE.OrbitControls(camera, renderer.domElement);
            controls.enableDamping = true;
            controls.dampingFactor = 0.1;
            controls.maxPolarAngle = Math.PI / 2;
            controls.minDistance = 5;
            controls.maxDistance = 100;
            
            // Mobile touch optimizations
            if (isMobile()) {
                controls.enablePan = true;
                controls.enableZoom = true;
                controls.enableRotate = true;
                controls.touches = {
                    ONE: THREE.TOUCH.ROTATE,
                    TWO: THREE.TOUCH.DOLLY_PAN
                };
            }
        }
        
        // Lights
        setupLighting();
        
        // Handle window resize
        window.addEventListener('resize', onWindowResize);
        
        // Mobile orientation change
        window.addEventListener('orientationchange', function() {
            setTimeout(onWindowResize, 100);
        });
        
        // Start animation loop
        animate();
    }
    
    function isMobile() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || 
               window.innerWidth <= 768;
    }
    
    function setupLighting() {
        // Ambient light
        const ambientLight = new THREE.AmbientLight(0x404040, 0.4);
        scene.add(ambientLight);
        
        // Main directional light
        const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
        directionalLight.position.set(10, 20, 5);
        directionalLight.castShadow = true;
        directionalLight.shadow.mapSize.width = 2048;
        directionalLight.shadow.mapSize.height = 2048;
        scene.add(directionalLight);
        
        // Additional fill lights
        const fillLight1 = new THREE.DirectionalLight(0xffffff, 0.3);
        fillLight1.position.set(-10, 10, -5);
        scene.add(fillLight1);
        
        const fillLight2 = new THREE.DirectionalLight(0xffffff, 0.2);
        fillLight2.position.set(0, 5, 10);
        scene.add(fillLight2);
    }
    
    function animate() {
        requestAnimationFrame(animate);
        
        if (controls) {
            controls.update();
        }
        
        renderer.render(scene, camera);
    }
    
    function onWindowResize() {
        const container = document.getElementById('trd-3d-container');
        if (!container) return;
        
        // Get container dimensions
        const width = container.clientWidth;
        const height = container.clientHeight;
        
        // Update camera
        const aspect = width / height;
        camera.aspect = aspect;
        camera.updateProjectionMatrix();
        
        // Update renderer
        renderer.setSize(width, height);
        
        // Update pixel ratio for mobile
        if (isMobile()) {
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));
        } else {
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        }
        
        // Force render
        renderer.render(scene, camera);
    }
    
    function bindEvents() {
        // Room dimension controls
        $('#trd-room-width, #trd-room-length, #trd-room-height').on('input', function() {
            currentDesign.room.width = parseFloat($('#trd-room-width').val());
            currentDesign.room.length = parseFloat($('#trd-room-length').val());
            currentDesign.room.height = parseFloat($('#trd-room-height').val());
            updateRoom();
            updateSummary();
            updateRecommendations();
        });
        
        // Screen controls
        $('#trd-screen-type, #trd-screen-size, #trd-screen-position').on('change input', function() {
            currentDesign.screen.type = $('#trd-screen-type').val();
            currentDesign.screen.size = parseInt($('#trd-screen-size').val());
            currentDesign.screen.position = $('#trd-screen-position').val();
            updateScreen();
            updateSeating();
            updateSummary();
            updateRecommendations();
        });
        
        // Speaker controls
        $('#trd-speaker-config, #trd-auto-placement').on('change', function() {
            currentDesign.speakers.config = $('#trd-speaker-config').val();
            currentDesign.speakers.autoPlacement = $('#trd-auto-placement').is(':checked');
            updateSpeakers();
            updateSummary();
            updateRecommendations();
        });
        
        // Seating controls
        $('#trd-seating-rows, #trd-seats-per-row, #trd-seating-type').on('change input', function() {
            currentDesign.seating.rows = parseInt($('#trd-seating-rows').val());
            currentDesign.seating.seatsPerRow = parseInt($('#trd-seats-per-row').val());
            currentDesign.seating.type = $('#trd-seating-type').val();
            updateSeating();
            updateSummary();
            updateRecommendations();
        });
        
        // Feature toggles
        $('#trd-add-bar, #trd-add-lighting, #trd-add-carpet').on('change', function() {
            currentDesign.features.bar = $('#trd-add-bar').is(':checked');
            currentDesign.features.lighting = $('#trd-add-lighting').is(':checked');
            currentDesign.features.carpet = $('#trd-add-carpet').is(':checked');
            updateFeatures();
        });
        
        // Toolbar buttons
        $('#trd-new-design').on('click', newDesign);
        $('#trd-save-design').on('click', showSaveModal);
        $('#trd-load-design').on('click', showLoadModal);
        $('#trd-export-design').on('click', exportDesign);
        $('#trd-reset-view').on('click', resetView);
        $('#trd-fullscreen').on('click', toggleFullscreen);
        $('#trd-help').on('click', showHelpModal);
        
        // View tabs
        $('.trd-tab-btn').on('click', function() {
            const view = $(this).data('view');
            switchView(view);
            $('.trd-tab-btn').removeClass('active');
            $(this).addClass('active');
        });
        
        // Modal events
        $('.trd-modal-close').on('click', function() {
            $(this).closest('.trd-modal').hide();
        });
        
        // Save form
        $('#trd-save-confirm').on('click', saveDesign);
        
        // Social sharing
        $('#trd-share-facebook').on('click', shareOnFacebook);
        $('#trd-share-twitter').on('click', shareOnTwitter);
        $('#trd-share-pinterest').on('click', shareOnPinterest);
        $('#trd-copy-link').on('click', copyShareLink);
        
        // Click outside modal to close
        $('.trd-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).hide();
            }
        });
        
        // Keyboard shortcuts
        $(document).on('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.which) {
                    case 83: // Ctrl+S
                        e.preventDefault();
                        showSaveModal();
                        break;
                    case 79: // Ctrl+O
                        e.preventDefault();
                        showLoadModal();
                        break;
                    case 78: // Ctrl+N
                        e.preventDefault();
                        newDesign();
                        break;
                }
            }
            
            if (e.which === 27) { // Escape
                $('.trd-modal:visible').hide();
            }
        });
    }
    
    function updateDesign() {
        updateRoom();
        updateScreen();
        updateSpeakers();
        updateSeating();
        updateFeatures();
        updateSummary();
        updateRecommendations();
        updateEquipmentList();
    }
    
    function updateRoom() {
        // Clear existing room
        if (roomMesh) {
            scene.remove(roomMesh);
            scene.remove(floorMesh);
            scene.remove(ceilingMesh);
        }
        
        const width = currentDesign.room.width;
        const length = currentDesign.room.length;
        const height = currentDesign.room.height;
        
        // Create room walls
        const wallGeometry = new THREE.BoxGeometry(1, height, 0.1);
        const wallMaterial = new THREE.MeshLambertMaterial({ color: 0xcccccc });
        
        const roomGroup = new THREE.Group();
        
        // Front wall
        const frontWall = new THREE.Mesh(wallGeometry, wallMaterial);
        frontWall.scale.set(width, 1, 1);
        frontWall.position.set(0, height/2, -length/2);
        roomGroup.add(frontWall);
        
        // Back wall
        const backWall = new THREE.Mesh(wallGeometry, wallMaterial);
        backWall.scale.set(width, 1, 1);
        backWall.position.set(0, height/2, length/2);
        roomGroup.add(backWall);
        
        // Left wall
        const leftWall = new THREE.Mesh(wallGeometry, wallMaterial);
        leftWall.scale.set(length, 1, 1);
        leftWall.rotation.y = Math.PI/2;
        leftWall.position.set(-width/2, height/2, 0);
        roomGroup.add(leftWall);
        
        // Right wall
        const rightWall = new THREE.Mesh(wallGeometry, wallMaterial);
        rightWall.scale.set(length, 1, 1);
        rightWall.rotation.y = Math.PI/2;
        rightWall.position.set(width/2, height/2, 0);
        roomGroup.add(rightWall);
        
        scene.add(roomGroup);
        roomMesh = roomGroup;
        
        // Floor
        const floorGeometry = new THREE.PlaneGeometry(width, length);
        const floorMaterial = new THREE.MeshLambertMaterial({ 
            color: currentDesign.features.carpet ? 0x8B4513 : 0x666666 
        });
        floorMesh = new THREE.Mesh(floorGeometry, floorMaterial);
        floorMesh.rotation.x = -Math.PI/2;
        floorMesh.receiveShadow = true;
        scene.add(floorMesh);
        
        // Ceiling
        const ceilingGeometry = new THREE.PlaneGeometry(width, length);
        const ceilingMaterial = new THREE.MeshLambertMaterial({ color: 0xffffff });
        ceilingMesh = new THREE.Mesh(ceilingGeometry, ceilingMaterial);
        ceilingMesh.rotation.x = Math.PI/2;
        ceilingMesh.position.y = height;
        scene.add(ceilingMesh);
    }
    
    function updateScreen() {
        // Remove existing screen
        if (screen) {
            scene.remove(screen);
        }
        
        const screenSize = currentDesign.screen.size;
        const screenWidth = screenSize * 0.87 * 0.1; // Convert to scene units
        const screenHeight = screenWidth * 9/16; // 16:9 aspect ratio
        
        let screenGeometry, screenMaterial;
        
        if (currentDesign.screen.type === 'projector') {
            // Projector screen (white)
            screenGeometry = new THREE.PlaneGeometry(screenWidth, screenHeight);
            screenMaterial = new THREE.MeshLambertMaterial({ color: 0xffffff });
        } else {
            // TV/Display (black bezel with screen)
            screenGeometry = new THREE.BoxGeometry(screenWidth * 1.1, screenHeight * 1.1, 0.2);
            screenMaterial = new THREE.MeshLambertMaterial({ color: 0x111111 });
        }
        
        screen = new THREE.Mesh(screenGeometry, screenMaterial);
        
        // Position screen based on wall selection
        const roomWidth = currentDesign.room.width;
        const roomLength = currentDesign.room.length;
        const roomHeight = currentDesign.room.height;
        
        switch(currentDesign.screen.position) {
            case 'front':
                screen.position.set(0, screenHeight/2 + 1, -roomLength/2 + 0.2);
                break;
            case 'back':
                screen.position.set(0, screenHeight/2 + 1, roomLength/2 - 0.2);
                screen.rotation.y = Math.PI;
                break;
            case 'left':
                screen.position.set(-roomWidth/2 + 0.2, screenHeight/2 + 1, 0);
                screen.rotation.y = Math.PI/2;
                break;
            case 'right':
                screen.position.set(roomWidth/2 - 0.2, screenHeight/2 + 1, 0);
                screen.rotation.y = -Math.PI/2;
                break;
        }
        
        screen.castShadow = true;
        scene.add(screen);
    }
    
    function updateSpeakers() {
        // Remove existing speakers
        speakers.forEach(speaker => scene.remove(speaker));
        speakers = [];
        
        const config = currentDesign.speakers.config;
        const roomWidth = currentDesign.room.width;
        const roomLength = currentDesign.room.length;
        const roomHeight = currentDesign.room.height;
        
        // Speaker geometry and material
        const speakerGeometry = new THREE.CylinderGeometry(0.3, 0.3, 0.8);
        const speakerMaterial = new THREE.MeshLambertMaterial({ color: 0x333333 });
        
        // Speaker positions based on configuration
        const positions = getSpeakerPositions(config, roomWidth, roomLength, roomHeight);
        
        positions.forEach(pos => {
            const speaker = new THREE.Mesh(speakerGeometry, speakerMaterial);
            speaker.position.copy(pos);
            speaker.castShadow = true;
            speakers.push(speaker);
            scene.add(speaker);
        });
    }
    
    function getSpeakerPositions(config, width, length, height) {
        const positions = [];
        const speakerHeight = 1.2; // Ear level when seated
        
        switch(config) {
            case '2.1':
                // Left, Right, Subwoofer
                positions.push(new THREE.Vector3(-2, speakerHeight, -length/2 + 2));
                positions.push(new THREE.Vector3(2, speakerHeight, -length/2 + 2));
                positions.push(new THREE.Vector3(0, 0.4, -length/2 + 1)); // Sub on floor
                break;
                
            case '5.1':
                // Front L/R, Center, Rear L/R, Subwoofer
                positions.push(new THREE.Vector3(-2.5, speakerHeight, -length/2 + 2));
                positions.push(new THREE.Vector3(2.5, speakerHeight, -length/2 + 2));
                positions.push(new THREE.Vector3(0, speakerHeight, -length/2 + 1));
                positions.push(new THREE.Vector3(-2, speakerHeight, length/2 - 2));
                positions.push(new THREE.Vector3(2, speakerHeight, length/2 - 2));
                positions.push(new THREE.Vector3(-1, 0.4, -length/2 + 3)); // Sub
                break;
                
            case '7.1':
                // 5.1 + Side speakers
                positions.push(new THREE.Vector3(-2.5, speakerHeight, -length/2 + 2));
                positions.push(new THREE.Vector3(2.5, speakerHeight, -length/2 + 2));
                positions.push(new THREE.Vector3(0, speakerHeight, -length/2 + 1));
                positions.push(new THREE.Vector3(-width/2 + 0.5, speakerHeight, 0));
                positions.push(new THREE.Vector3(width/2 - 0.5, speakerHeight, 0));
                positions.push(new THREE.Vector3(-2, speakerHeight, length/2 - 2));
                positions.push(new THREE.Vector3(2, speakerHeight, length/2 - 2));
                positions.push(new THREE.Vector3(-1, 0.4, -length/2 + 3)); // Sub
                break;
                
            case '9.1':
                // 7.1 + Height speakers
                positions.push(new THREE.Vector3(-2.5, speakerHeight, -length/2 + 2));
                positions.push(new THREE.Vector3(2.5, speakerHeight, -length/2 + 2));
                positions.push(new THREE.Vector3(0, speakerHeight, -length/2 + 1));
                positions.push(new THREE.Vector3(-width/2 + 0.5, speakerHeight, 0));
                positions.push(new THREE.Vector3(width/2 - 0.5, speakerHeight, 0));
                positions.push(new THREE.Vector3(-2, speakerHeight, length/2 - 2));
                positions.push(new THREE.Vector3(2, speakerHeight, length/2 - 2));
                positions.push(new THREE.Vector3(-2, height - 0.5, -length/2 + 2)); // Height L
                positions.push(new THREE.Vector3(2, height - 0.5, -length/2 + 2)); // Height R
                positions.push(new THREE.Vector3(-1, 0.4, -length/2 + 3)); // Sub
                break;
        }
        
        return positions;
    }
    
    function updateSeating() {
        // Remove existing seats
        seats.forEach(seat => scene.remove(seat));
        seats = [];
        
        const rows = currentDesign.seating.rows;
        const seatsPerRow = currentDesign.seating.seatsPerRow;
        const seatType = currentDesign.seating.type;
        const roomLength = currentDesign.room.length;
        const roomWidth = currentDesign.room.width;
        const screenSize = currentDesign.screen.size;
        
        // Realistic seat dimensions (in feet, like Audio Advice)
        let seatWidth = 2.5;
        let seatDepth = 2.8;
        let seatHeight = 1.2;
        
        switch(seatType) {
            case 'sofa':
                seatWidth = 7; // Full sofa width
                seatDepth = 3.5;
                seatHeight = 1.0;
                break;
            case 'chair':
                seatWidth = 2.2;
                seatDepth = 2.5;
                seatHeight = 1.1;
                break;
            case 'recliner':
                seatWidth = 2.8; // Theater recliner width
                seatDepth = 3.5; // Recliners need more depth when reclined
                seatHeight = 1.2;
                break;
        }
        
        // Audio Advice style viewing distance calculation
        // THX and SMPTE standards: 36-40 degrees viewing angle
        const screenDiagonalInches = screenSize;
        const screenWidthInches = screenDiagonalInches * 0.87; // 16:9 aspect ratio
        const screenWidthFeet = screenWidthInches / 12;
        
        // Optimal viewing distances (Audio Advice standards)
        let optimalDistanceMin, optimalDistanceMax;
        
        // For 4K content (closer viewing)
        optimalDistanceMin = screenWidthFeet * 1.0; // Can sit closer with 4K
        optimalDistanceMax = screenWidthFeet * 1.6;
        
        // For 1080p content (farther viewing) - most common
        if (screenSize < 75) {
            optimalDistanceMin = screenWidthFeet * 1.5;
            optimalDistanceMax = screenWidthFeet * 2.5;
        } else {
            // Larger screens need more distance
            optimalDistanceMin = screenWidthFeet * 1.8;
            optimalDistanceMax = screenWidthFeet * 3.0;
        }
        
        // Use optimal distance (slightly closer to min for immersion)
        const baseDistance = optimalDistanceMin + (optimalDistanceMax - optimalDistanceMin) * 0.3;
        
        // Calculate screen position and seating area
        let screenZ = 0;
        let seatingStartZ = 0;
        
        switch(currentDesign.screen.position) {
            case 'front':
                screenZ = -roomLength/2 + 0.5; // Screen on front wall
                seatingStartZ = screenZ + baseDistance; // Seats behind screen position
                break;
            case 'back':
                screenZ = roomLength/2 - 0.5; // Screen on back wall
                seatingStartZ = screenZ - baseDistance; // Seats in front of screen
                break;
            case 'left':
                screenZ = 0; // Screen on side wall (center)
                seatingStartZ = baseDistance/2; // Seats positioned for side viewing
                break;
            case 'right':
                screenZ = 0;
                seatingStartZ = -baseDistance/2;
                break;
        }
        
        // Calculate seating layout (Audio Advice style)
        const aisleWidth = 2.0; // 2 feet aisle between seat groups
        const seatSpacing = 0.5; // Space between individual seats
        const rowSpacing = 3.5; // Space between rows (includes walkway)
        
        // Calculate total seating width with proper spacing
        let totalSeatingWidth;
        if (seatsPerRow <= 2) {
            totalSeatingWidth = seatsPerRow * seatWidth + (seatsPerRow - 1) * seatSpacing;
        } else {
            // For more than 2 seats, create center aisle
            const leftSeats = Math.ceil(seatsPerRow / 2);
            const rightSeats = Math.floor(seatsPerRow / 2);
            totalSeatingWidth = (leftSeats * seatWidth) + (rightSeats * seatWidth) + 
                               aisleWidth + ((leftSeats - 1) * seatSpacing) + ((rightSeats - 1) * seatSpacing);
        }
        
        // Ensure seating fits in room width
        if (totalSeatingWidth > roomWidth - 2) {
            // Adjust spacing if too wide
            const availableWidth = roomWidth - 2;
            const scaleFactor = availableWidth / totalSeatingWidth;
            totalSeatingWidth = availableWidth;
        }
        
        // Position seats
        for (let row = 0; row < rows; row++) {
            // Calculate row position with proper spacing
            const zPosition = seatingStartZ + (row * rowSpacing);
            
            // Check if row fits in room
            if (Math.abs(zPosition) > roomLength/2 - seatDepth/2 - 1) {
                continue; // Skip this row if it doesn't fit
            }
            
            // Stepped seating height (12 inches per row like real theaters)
            const yPosition = seatHeight / 2 + (row * 1.0);
            
            // Position seats in this row
            let currentX = -totalSeatingWidth / 2;
            
            for (let seat = 0; seat < seatsPerRow; seat++) {
                // Handle center aisle for larger configurations
                if (seatsPerRow > 2 && seat === Math.ceil(seatsPerRow / 2)) {
                    currentX += aisleWidth; // Add aisle space
                }
                
                const xPosition = currentX + seatWidth / 2;
                
                // Create and position seat
                const seatMesh = createSeat(seatType, seatWidth, seatDepth, seatHeight);
                seatMesh.position.set(xPosition, yPosition, zPosition);
                
                // Rotate seats to face screen
                if (currentDesign.screen.position === 'back') {
                    seatMesh.rotation.y = Math.PI;
                } else if (currentDesign.screen.position === 'left') {
                    seatMesh.rotation.y = Math.PI/2;
                } else if (currentDesign.screen.position === 'right') {
                    seatMesh.rotation.y = -Math.PI/2;
                }
                
                // Angle seats slightly toward screen for better viewing (like Audio Advice)
                if (seatsPerRow > 3) {
                    const centerOffset = xPosition;
                    const angleAdjustment = centerOffset * 0.05; // Subtle angle adjustment
                    seatMesh.rotation.y += angleAdjustment;
                }
                
                seatMesh.castShadow = true;
                seats.push(seatMesh);
                scene.add(seatMesh);
                
                currentX += seatWidth + seatSpacing;
            }
        }
    }
    
    function createSeat(type, width, depth, height) {
        const seatGroup = new THREE.Group();
        
        // Seat materials
        const seatMaterial = new THREE.MeshLambertMaterial({ color: 0x8B4513 });
        const metalMaterial = new THREE.MeshLambertMaterial({ color: 0x666666 });
        
        switch(type) {
            case 'recliner':
                // Seat base
                const seatGeometry = new THREE.BoxGeometry(width, height * 0.4, depth);
                const seatBase = new THREE.Mesh(seatGeometry, seatMaterial);
                seatBase.position.y = height * 0.2;
                seatGroup.add(seatBase);
                
                // Backrest
                const backGeometry = new THREE.BoxGeometry(width, height * 0.8, 0.3);
                const backrest = new THREE.Mesh(backGeometry, seatMaterial);
                backrest.position.set(0, height * 0.6, -depth/2 + 0.15);
                seatGroup.add(backrest);
                
                // Armrests
                const armGeometry = new THREE.BoxGeometry(0.3, height * 0.6, depth * 0.8);
                const leftArm = new THREE.Mesh(armGeometry, seatMaterial);
                leftArm.position.set(-width/2 + 0.15, height * 0.5, 0);
                seatGroup.add(leftArm);
                
                const rightArm = leftArm.clone();
                rightArm.position.x = width/2 - 0.15;
                seatGroup.add(rightArm);
                break;
                
            case 'sofa':
                // Long seat base
                const sofaGeometry = new THREE.BoxGeometry(width, height * 0.4, depth);
                const sofaBase = new THREE.Mesh(sofaGeometry, seatMaterial);
                sofaBase.position.y = height * 0.2;
                seatGroup.add(sofaBase);
                
                // Backrest
                const sofaBackGeometry = new THREE.BoxGeometry(width, height * 0.6, 0.3);
                const sofaBack = new THREE.Mesh(sofaBackGeometry, seatMaterial);
                sofaBack.position.set(0, height * 0.5, -depth/2 + 0.15);
                seatGroup.add(sofaBack);
                break;
                
            case 'chair':
                // Simple chair
                const chairGeometry = new THREE.BoxGeometry(width, height * 0.4, depth);
                const chairBase = new THREE.Mesh(chairGeometry, seatMaterial);
                chairBase.position.y = height * 0.2;
                seatGroup.add(chairBase);
                
                const chairBackGeometry = new THREE.BoxGeometry(width, height * 0.6, 0.3);
                const chairBack = new THREE.Mesh(chairBackGeometry, seatMaterial);
                chairBack.position.set(0, height * 0.5, -depth/2 + 0.15);
                seatGroup.add(chairBack);
                break;
        }
        
        return seatGroup;
    }
    
    function updateFeatures() {
        // This would add additional room features like bars, lighting, etc.
        // For now, just update the floor material for carpet
        if (floorMesh) {
            floorMesh.material.color.setHex(currentDesign.features.carpet ? 0x8B4513 : 0x666666);
        }
    }
    
    function updateSummary() {
        const summary = $('#trd-design-summary');
        const room = currentDesign.room;
        const unit = window.trdSettings.measurement_unit;
        
        summary.html(`
            <div class="trd-summary-item">
                <span class="trd-summary-label">Room Size:</span>
                <span class="trd-summary-value">${room.width} × ${room.length} × ${room.height} ${unit}</span>
            </div>
            <div class="trd-summary-item">
                <span class="trd-summary-label">Floor Area:</span>
                <span class="trd-summary-value">${(room.width * room.length).toFixed(1)} sq ${unit}</span>
            </div>
            <div class="trd-summary-item">
                <span class="trd-summary-label">Screen Size:</span>
                <span class="trd-summary-value">${currentDesign.screen.size}"</span>
            </div>
            <div class="trd-summary-item">
                <span class="trd-summary-label">Audio Config:</span>
                <span class="trd-summary-value">${currentDesign.speakers.config}</span>
            </div>
            <div class="trd-summary-item">
                <span class="trd-summary-label">Total Seats:</span>
                <span class="trd-summary-value">${currentDesign.seating.rows * currentDesign.seating.seatsPerRow}</span>
            </div>
        `);
    }
    
    function updateRecommendations() {
        const recommendations = $('#trd-recommendations');
        const recs = [];
        
        // Viewing distance recommendation
        const screenSize = currentDesign.screen.size;
        const optimalMin = screenSize * 0.1 * 1.5;
        const optimalMax = screenSize * 0.1 * 2.5;
        const roomLength = currentDesign.room.length;
        
        if (roomLength < optimalMin) {
            recs.push({
                type: 'warning',
                title: 'Viewing Distance',
                text: `Room may be too short for optimal viewing. Consider a smaller screen or longer room.`
            });
        } else if (roomLength > optimalMax * 1.5) {
            recs.push({
                type: 'info',
                title: 'Viewing Distance',
                text: `You have plenty of space. Consider a larger screen for better immersion.`
            });
        } else {
            recs.push({
                type: 'success',
                title: 'Viewing Distance',
                text: `Perfect viewing distance for your screen size.`
            });
        }
        
        // Room acoustics
        const roomVolume = currentDesign.room.width * currentDesign.room.length * currentDesign.room.height;
        if (roomVolume < 800) {
            recs.push({
                type: 'info',
                title: 'Acoustics',
                text: `Small room - consider acoustic treatment to reduce reflections.`
            });
        }
        
        // Speaker placement
        if (currentDesign.speakers.config === '5.1' || currentDesign.speakers.config === '7.1') {
            recs.push({
                type: 'info',
                title: 'Speaker Placement',
                text: `Ensure rear speakers are positioned behind the seating area for proper surround effect.`
            });
        }
        
        let html = '';
        recs.forEach(rec => {
            html += `
                <div class="trd-recommendation ${rec.type}">
                    <div class="trd-recommendation-type">${rec.title}</div>
                    <div>${rec.text}</div>
                </div>
            `;
        });
        
        recommendations.html(html);
    }
    
    function updateEquipmentList() {
        const equipment = $('#trd-equipment-list');
        const items = [];
        
        // Screen
        items.push({
            name: `${currentDesign.screen.size}" ${currentDesign.screen.type === 'projector' ? 'Projector Screen' : 'TV/Display'}`,
            quantity: 1
        });
        
        // Speakers
        const speakerCount = getSpeakerCount(currentDesign.speakers.config);
        items.push({
            name: 'Speakers',
            quantity: speakerCount.main
        });
        
        if (speakerCount.sub > 0) {
            items.push({
                name: 'Subwoofer',
                quantity: speakerCount.sub
            });
        }
        
        // Seating
        const totalSeats = currentDesign.seating.rows * currentDesign.seating.seatsPerRow;
        items.push({
            name: `${currentDesign.seating.type}${totalSeats > 1 ? 's' : ''}`,
            quantity: totalSeats
        });
        
        // AV Receiver
        items.push({
            name: 'AV Receiver',
            quantity: 1
        });
        
        let html = '';
        items.forEach(item => {
            html += `
                <div class="trd-equipment-item">
                    <span class="trd-equipment-name">${item.name}</span>
                    <span class="trd-equipment-qty">${item.quantity}</span>
                </div>
            `;
        });
        
        equipment.html(html);
    }
    
    function getSpeakerCount(config) {
        const counts = {
            '2.1': { main: 2, sub: 1 },
            '5.1': { main: 5, sub: 1 },
            '7.1': { main: 7, sub: 1 },
            '9.1': { main: 9, sub: 1 }
        };
        return counts[config] || { main: 2, sub: 1 };
    }
    
    function newDesign() {
        if (confirm('Start a new design? Any unsaved changes will be lost.')) {
            // Reset to default values
            currentDesign = {
                room: {
                    width: parseFloat(window.trdSettings.default_room_width),
                    length: parseFloat(window.trdSettings.default_room_length),
                    height: parseFloat(window.trdSettings.default_room_height)
                },
                screen: {
                    type: 'tv',
                    size: parseInt(window.trdSettings.default_screen_size),
                    position: 'front'
                },
                speakers: {
                    config: window.trdSettings.default_speaker_config,
                    autoPlacement: true
                },
                seating: {
                    rows: 2,
                    seatsPerRow: 3,
                    type: 'recliner'
                },
                features: {
                    bar: false,
                    lighting: false,
                    carpet: false
                }
            };
            
            // Update form values
            $('#trd-room-width').val(currentDesign.room.width);
            $('#trd-room-length').val(currentDesign.room.length);
            $('#trd-room-height').val(currentDesign.room.height);
            $('#trd-screen-type').val(currentDesign.screen.type);
            $('#trd-screen-size').val(currentDesign.screen.size);
            $('#trd-screen-position').val(currentDesign.screen.position);
            $('#trd-speaker-config').val(currentDesign.speakers.config);
            $('#trd-auto-placement').prop('checked', currentDesign.speakers.autoPlacement);
            $('#trd-seating-rows').val(currentDesign.seating.rows);
            $('#trd-seats-per-row').val(currentDesign.seating.seatsPerRow);
            $('#trd-seating-type').val(currentDesign.seating.type);
            $('#trd-add-bar').prop('checked', currentDesign.features.bar);
            $('#trd-add-lighting').prop('checked', currentDesign.features.lighting);
            $('#trd-add-carpet').prop('checked', currentDesign.features.carpet);
            
            updateDesign();
        }
    }
    
    function showSaveModal() {
        if (!window.trdAtts.allow_save || window.trdAtts.allow_save === 'false') {
            alert('Saving is not enabled for this instance.');
            return;
        }
        
        $('#trd-save-modal').show();
        $('#trd-design-name').focus();
    }
    
    function saveDesign() {
        const name = $('#trd-design-name').val().trim();
        if (!name) {
            alert('Please enter a design name.');
            return;
        }
        
        const data = {
            action: 'save_room_design',
            nonce: window.trd_ajax.nonce,
            design_name: name,
            room_data: JSON.stringify(currentDesign)
        };
        
        $.post(window.trd_ajax.ajax_url, data)
            .done(function(response) {
                if (response.success) {
                    alert('Design saved successfully!');
                    $('#trd-save-modal').hide();
                    $('#trd-design-name').val('');
                } else {
                    alert('Error saving design: ' + response.data);
                }
            })
            .fail(function() {
                alert('Error saving design. Please try again.');
            });
    }
    
    function showLoadModal() {
        $('#trd-load-modal').show();
        loadSavedDesigns();
    }
    
    function loadSavedDesigns() {
        const container = $('#trd-saved-designs');
        container.html('<p>Loading designs...</p>');
        
        $.post(window.trd_ajax.ajax_url, {
            action: 'get_room_designs',
            nonce: window.trd_ajax.nonce
        })
        .done(function(response) {
            if (response.success && response.data.length > 0) {
                let html = '';
                response.data.forEach(function(design) {
                    html += `
                        <div class="trd-saved-design" data-id="${design.id}">
                            <div class="trd-saved-design-info">
                                <div class="trd-saved-design-name">${design.design_name}</div>
                                <div class="trd-saved-design-date">${design.created_at}</div>
                            </div>
                            <div class="trd-saved-design-actions">
                                <button class="trd-btn trd-btn-small trd-load-design-btn" data-id="${design.id}">Load</button>
                            </div>
                        </div>
                    `;
                });
                container.html(html);
                
                $('.trd-load-design-btn').on('click', function() {
                    const designId = $(this).data('id');
                    loadDesign(designId);
                });
            } else {
                container.html('<p>No saved designs found.</p>');
            }
        })
        .fail(function() {
            container.html('<p>Error loading designs.</p>');
        });
    }
    
    function loadDesign(designId) {
        $.post(window.trd_ajax.ajax_url, {
            action: 'load_room_design',
            nonce: window.trd_ajax.nonce,
            design_id: designId
        })
        .done(function(response) {
            if (response.success) {
                currentDesign = JSON.parse(response.data.room_data);
                
                // Update form values
                $('#trd-room-width').val(currentDesign.room.width);
                $('#trd-room-length').val(currentDesign.room.length);
                $('#trd-room-height').val(currentDesign.room.height);
                $('#trd-screen-type').val(currentDesign.screen.type);
                $('#trd-screen-size').val(currentDesign.screen.size);
                $('#trd-screen-position').val(currentDesign.screen.position);
                $('#trd-speaker-config').val(currentDesign.speakers.config);
                $('#trd-auto-placement').prop('checked', currentDesign.speakers.autoPlacement);
                $('#trd-seating-rows').val(currentDesign.seating.rows);
                $('#trd-seats-per-row').val(currentDesign.seating.seatsPerRow);
                $('#trd-seating-type').val(currentDesign.seating.type);
                $('#trd-add-bar').prop('checked', currentDesign.features.bar);
                $('#trd-add-lighting').prop('checked', currentDesign.features.lighting);
                $('#trd-add-carpet').prop('checked', currentDesign.features.carpet);
                
                updateDesign();
                $('#trd-load-modal').hide();
                alert('Design loaded successfully!');
            } else {
                alert('Error loading design: ' + response.data);
            }
        })
        .fail(function() {
            alert('Error loading design. Please try again.');
        });
    }
    
    function exportDesign() {
        const data = {
            design: currentDesign,
            timestamp: new Date().toISOString(),
            version: '1.0.0'
        };
        
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'theater-room-design.json';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    
    function resetView() {
        if (camera && controls) {
            camera.position.set(20, 15, 20);
            camera.lookAt(0, 0, 0);
            controls.reset();
        }
    }
    
    function toggleFullscreen() {
        const container = document.getElementById('trd-3d-container');
        if (!document.fullscreenElement) {
            container.requestFullscreen().catch(err => {
                console.log('Error attempting to enable fullscreen:', err);
            });
        } else {
            document.exitFullscreen();
        }
    }
    
    function switchView(view) {
        if (!camera || !controls) return;
        
        const room = currentDesign.room;
        
        switch(view) {
            case '3d':
                camera.position.set(20, 15, 20);
                camera.lookAt(0, 0, 0);
                break;
            case 'top':
                camera.position.set(0, room.height + 10, 0);
                camera.lookAt(0, 0, 0);
                break;
            case 'side':
                camera.position.set(room.width + 10, room.height/2, 0);
                camera.lookAt(0, room.height/2, 0);
                break;
        }
        
        controls.update();
    }
    
    function showHelpModal() {
        $('#trd-help-modal').show();
    }
    
    // Social sharing functions
    function shareOnFacebook() {
        const url = encodeURIComponent(window.location.href);
        const text = encodeURIComponent('Check out my theater room design!');
        window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}&quote=${text}`, '_blank', 'width=600,height=400');
    }
    
    function shareOnTwitter() {
        const url = encodeURIComponent(window.location.href);
        const text = encodeURIComponent('Check out my theater room design created with Theater Room Designer!');
        window.open(`https://twitter.com/intent/tweet?url=${url}&text=${text}`, '_blank', 'width=600,height=400');
    }
    
    function shareOnPinterest() {
        const url = encodeURIComponent(window.location.href);
        const description = encodeURIComponent('My theater room design');
        window.open(`https://pinterest.com/pin/create/button/?url=${url}&description=${description}`, '_blank', 'width=600,height=400');
    }
    
    function copyShareLink() {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(window.location.href).then(function() {
                alert('Link copied to clipboard!');
            });
        } else {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = window.location.href;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            alert('Link copied to clipboard!');
        }
    }
    
})(jQuery);