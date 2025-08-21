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
        
        // Scene with Audio Advice style lighting
        scene = new THREE.Scene();
        scene.background = new THREE.Color(0xf0f0f0); // Light background like Audio Advice
        
        // Camera - Audio Advice style positioning
        const aspect = container.clientWidth / container.clientHeight;
        camera = new THREE.PerspectiveCamera(50, aspect, 0.1, 1000); // Audio Advice FOV
        
        // Position camera exactly like Audio Advice screenshots
        // Slightly elevated, angled view showing room layout clearly
        camera.position.set(12, 10, 12); // Audio Advice camera position
        camera.lookAt(0, 1, 0); // Look slightly down at room
        
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
        // Audio Advice style lighting (bright, clear)
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
        scene.add(ambientLight);
        
        // Main directional light (bright, like Audio Advice)
        const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
        directionalLight.position.set(15, 20, 10);
        directionalLight.castShadow = true;
        directionalLight.shadow.mapSize.width = 1024;
        directionalLight.shadow.mapSize.height = 1024;
        scene.add(directionalLight);
        
        // Additional bright fill light
        const fillLight = new THREE.DirectionalLight(0xffffff, 0.4);
        fillLight.position.set(-10, 15, -10);
        scene.add(fillLight);
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
        
        // Audio Advice style room materials (light, realistic)
        const wallMaterial = new THREE.MeshLambertMaterial({ 
            color: 0xe8e8e8, // Light gray walls (like Audio Advice)
            transparent: false
        });
        
        const roomGroup = new THREE.Group();
        
        // Create room walls with proper thickness
        const wallThickness = 0.5;
        
        // Front wall
        const frontWallGeometry = new THREE.BoxGeometry(width, height, wallThickness);
        const frontWall = new THREE.Mesh(frontWallGeometry, wallMaterial);
        frontWall.position.set(0, height/2, -length/2 - wallThickness/2);
        roomGroup.add(frontWall);
        
        // Back wall
        const backWallGeometry = new THREE.BoxGeometry(width, height, wallThickness);
        const backWall = new THREE.Mesh(backWallGeometry, wallMaterial);
        backWall.position.set(0, height/2, length/2 + wallThickness/2);
        roomGroup.add(backWall);
        
        // Left wall
        const leftWallGeometry = new THREE.BoxGeometry(wallThickness, height, length + wallThickness * 2);
        const leftWall = new THREE.Mesh(leftWallGeometry, wallMaterial);
        leftWall.position.set(-width/2 - wallThickness/2, height/2, 0);
        roomGroup.add(leftWall);
        
        // Right wall
        const rightWallGeometry = new THREE.BoxGeometry(wallThickness, height, length + wallThickness * 2);
        const rightWall = new THREE.Mesh(rightWallGeometry, wallMaterial);
        rightWall.position.set(width/2 + wallThickness/2, height/2, 0);
        roomGroup.add(rightWall);
        
        scene.add(roomGroup);
        roomMesh = roomGroup;
        
        // Audio Advice style floor (light, realistic)
        const floorGeometry = new THREE.PlaneGeometry(width, length);
        let floorColor = 0xd4c4a8; // Light wood/laminate floor (like Audio Advice)
        
        if (currentDesign.features.carpet) {
            floorColor = 0xb8a082; // Light carpet color
        }
        
        const floorMaterial = new THREE.MeshLambertMaterial({ 
            color: floorColor
        });
        
        floorMesh = new THREE.Mesh(floorGeometry, floorMaterial);
        floorMesh.rotation.x = -Math.PI/2;
        floorMesh.receiveShadow = true;
        scene.add(floorMesh);
        
        // Audio Advice style ceiling (light)
        const ceilingGeometry = new THREE.PlaneGeometry(width, length);
        const ceilingMaterial = new THREE.MeshLambertMaterial({ 
            color: 0xf5f5f5 // Light ceiling
        });
        ceilingMesh = new THREE.Mesh(ceilingGeometry, ceilingMaterial);
        ceilingMesh.rotation.x = Math.PI/2;
        ceilingMesh.position.y = height;
        scene.add(ceilingMesh);
        
        // Add room dimension labels (like Audio Advice)
        addRoomDimensionLabels(width, length, height);
    }
    
    function addRoomDimensionLabels(width, length, height) {
        // This would add text labels showing room dimensions
        // For now, we'll skip this but Audio Advice shows dimensions on the 3D model
    }
    
    function updateScreen() {
        // Remove existing screen
        if (screen) {
            scene.remove(screen);
        }
        
        const screenSize = currentDesign.screen.size;
        const screenWidth = screenSize * 0.87 * 0.1; // Convert to scene units
        const screenHeight = screenWidth * 9/16; // 16:9 aspect ratio
        
        // Create screen group for realistic appearance
        const screenGroup = new THREE.Group();
        
        if (currentDesign.screen.type === 'projector') {
            // Projector screen with frame
            const screenGeometry = new THREE.PlaneGeometry(screenWidth, screenHeight);
            
            // Screen surface (Audio Advice style - simple black screen)
            const screenMaterial = new THREE.MeshLambertMaterial({ 
                color: 0x1a1a1a, // Dark screen (like Audio Advice)
                transparent: false
            });
            
            const screenSurface = new THREE.Mesh(screenGeometry, screenMaterial);
            screenGroup.add(screenSurface);
            
            // Add screen frame
            const frameThickness = 0.1;
            const frameGeometry = new THREE.BoxGeometry(screenWidth + 0.4, screenHeight + 0.4, frameThickness);
            const frameMaterial = new THREE.MeshLambertMaterial({ color: 0x2a2a2a });
            const frame = new THREE.Mesh(frameGeometry, frameMaterial);
            frame.position.z = -frameThickness/2;
            screenGroup.add(frame);
            
        } else {
            // TV/Display with realistic bezel and screen
            const bezelThickness = 0.3;
            const bezelSize = 0.2;
            
            // TV bezel (black frame)
            const bezelGeometry = new THREE.BoxGeometry(
                screenWidth + bezelSize, 
                screenHeight + bezelSize, 
                bezelThickness
            );
            const bezelMaterial = new THREE.MeshLambertMaterial({ color: 0x0a0a0a });
            const bezel = new THREE.Mesh(bezelGeometry, bezelMaterial);
            screenGroup.add(bezel);
            
            // TV screen surface (Audio Advice style - simple dark screen)
            const screenGeometry = new THREE.PlaneGeometry(screenWidth, screenHeight);
            const screenMaterial = new THREE.MeshLambertMaterial({ 
                color: 0x1a1a1a // Simple dark screen (like Audio Advice)
            });
            const screenSurface = new THREE.Mesh(screenGeometry, screenMaterial);
            screenSurface.position.z = bezelThickness/2 + 0.01;
            screenGroup.add(screenSurface);
        }
        
        // Position screen based on wall selection
        const roomWidth = currentDesign.room.width;
        const roomLength = currentDesign.room.length;
        const roomHeight = currentDesign.room.height;
        
        // Optimal screen height (center at eye level when seated)
        const screenCenterHeight = 3.8; // 3.8 feet from floor (eye level when seated)
        
        switch(currentDesign.screen.position) {
            case 'front':
                screenGroup.position.set(0, screenCenterHeight, -roomLength/2 + 0.3);
                break;
            case 'back':
                screenGroup.position.set(0, screenCenterHeight, roomLength/2 - 0.3);
                screenGroup.rotation.y = Math.PI;
                break;
            case 'left':
                screenGroup.position.set(-roomWidth/2 + 0.3, screenCenterHeight, 0);
                screenGroup.rotation.y = Math.PI/2;
                break;
            case 'right':
                screenGroup.position.set(roomWidth/2 - 0.3, screenCenterHeight, 0);
                screenGroup.rotation.y = -Math.PI/2;
                break;
        }
        
        screenGroup.castShadow = true;
        scene.add(screenGroup);
        screen = screenGroup;
        
        // Add screen size indicator (like Audio Advice)
        addScreenSizeIndicator(screenSize, screenGroup.position);
    }
    
    function addScreenSizeIndicator(size, position) {
        // This would add a text label showing screen size
        // Audio Advice shows this information overlaid on the 3D view
        // For now we'll skip the text rendering but this is where it would go
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
        
        // Audio Advice professional viewing distance calculation
        // Based on SMPTE and THX standards for home theater
        const screenDiagonalInches = screenSize;
        const screenWidthInches = screenDiagonalInches * 0.87; // 16:9 aspect ratio
        const screenWidthFeet = screenWidthInches / 12;
        
        // Professional theater viewing distances (Audio Advice methodology)
        let optimalDistanceMin, optimalDistanceMax;
        
        // SMPTE standard: 30-degree viewing angle (most common for home theater)
        // THX standard: 36-degree viewing angle (more immersive)
        
        if (screenSize <= 55) {
            // Smaller screens - can sit closer
            optimalDistanceMin = screenWidthFeet * 1.2;
            optimalDistanceMax = screenWidthFeet * 2.0;
        } else if (screenSize <= 75) {
            // Medium screens - standard distances
            optimalDistanceMin = screenWidthFeet * 1.5;
            optimalDistanceMax = screenWidthFeet * 2.5;
        } else if (screenSize <= 100) {
            // Large screens - need more distance
            optimalDistanceMin = screenWidthFeet * 2.0;
            optimalDistanceMax = screenWidthFeet * 3.0;
        } else {
            // Very large screens/projectors - maximum distance
            optimalDistanceMin = screenWidthFeet * 2.5;
            optimalDistanceMax = screenWidthFeet * 3.5;
        }
        
        // Use Audio Advice's preferred distance (closer to max for comfort)
        const baseDistance = optimalDistanceMin + (optimalDistanceMax - optimalDistanceMin) * 0.7;
        
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
        
        // Audio Advice style seat materials (realistic furniture colors)
        const seatMaterial = new THREE.MeshLambertMaterial({ color: 0x8B7355 }); // Brown leather/fabric
        const cushionMaterial = new THREE.MeshLambertMaterial({ color: 0x9B8265 }); // Lighter cushion
        const baseMaterial = new THREE.MeshLambertMaterial({ color: 0x654321 }); // Darker base
        
        switch(type) {
            case 'recliner':
                // Audio Advice style recliner (like in screenshots)
                
                // Seat cushion (rounded)
                const seatGeometry = new THREE.BoxGeometry(width * 0.9, height * 0.3, depth * 0.6);
                const seatCushion = new THREE.Mesh(seatGeometry, cushionMaterial);
                seatCushion.position.set(0, height * 0.25, depth * 0.1);
                seatGroup.add(seatCushion);
                
                // Backrest (angled like real recliner)
                const backGeometry = new THREE.BoxGeometry(width * 0.9, height * 0.7, 0.4);
                const backrest = new THREE.Mesh(backGeometry, seatMaterial);
                backrest.position.set(0, height * 0.6, -depth/2 + 0.2);
                backrest.rotation.x = -0.1; // Slight angle
                seatGroup.add(backrest);
                
                // Armrests (realistic shape)
                const armGeometry = new THREE.BoxGeometry(0.4, height * 0.5, depth * 0.7);
                const leftArm = new THREE.Mesh(armGeometry, seatMaterial);
                leftArm.position.set(-width/2 + 0.2, height * 0.45, 0);
                seatGroup.add(leftArm);
                
                const rightArm = new THREE.Mesh(armGeometry, seatMaterial);
                rightArm.position.set(width/2 - 0.2, height * 0.45, 0);
                seatGroup.add(rightArm);
                
                // Base/frame
                const baseGeometry = new THREE.BoxGeometry(width, 0.2, depth);
                const base = new THREE.Mesh(baseGeometry, baseMaterial);
                base.position.y = 0.1;
                seatGroup.add(base);
                break;
                
            case 'sofa':
                // Audio Advice style sofa
                const sofaGeometry = new THREE.BoxGeometry(width, height * 0.35, depth * 0.8);
                const sofaBase = new THREE.Mesh(sofaGeometry, cushionMaterial);
                sofaBase.position.y = height * 0.25;
                seatGroup.add(sofaBase);
                
                // Sofa back
                const sofaBackGeometry = new THREE.BoxGeometry(width, height * 0.6, 0.5);
                const sofaBack = new THREE.Mesh(sofaBackGeometry, seatMaterial);
                sofaBack.position.set(0, height * 0.5, -depth/2 + 0.25);
                seatGroup.add(sofaBack);
                
                // Sofa arms
                const sofaArmGeometry = new THREE.BoxGeometry(0.5, height * 0.4, depth * 0.6);
                const sofaLeftArm = new THREE.Mesh(sofaArmGeometry, seatMaterial);
                sofaLeftArm.position.set(-width/2 + 0.25, height * 0.4, 0);
                seatGroup.add(sofaLeftArm);
                
                const sofaRightArm = new THREE.Mesh(sofaArmGeometry, seatMaterial);
                sofaRightArm.position.set(width/2 - 0.25, height * 0.4, 0);
                seatGroup.add(sofaRightArm);
                break;
                
            case 'chair':
                // Audio Advice style chair
                const chairGeometry = new THREE.BoxGeometry(width * 0.9, height * 0.3, depth * 0.8);
                const chairSeat = new THREE.Mesh(chairGeometry, cushionMaterial);
                chairSeat.position.y = height * 0.25;
                seatGroup.add(chairSeat);
                
                const chairBackGeometry = new THREE.BoxGeometry(width * 0.8, height * 0.6, 0.3);
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
        
        // Calculate immersion score (like Audio Advice)
        const immersionData = calculateImmersionScore();
        
        // Display immersion score prominently
        recs.push({
            type: 'immersion',
            title: `Theater Experience Score: ${immersionData.score}/100`,
            text: immersionData.description
        });
        
        // Viewing distance analysis
        if (immersionData.viewingDistance.status === 'optimal') {
            recs.push({
                type: 'success',
                title: 'Viewing Distance ✓',
                text: `Perfect! ${immersionData.viewingDistance.distance}ft provides excellent immersion for your ${currentDesign.screen.size}" screen.`
            });
        } else if (immersionData.viewingDistance.status === 'too_close') {
            recs.push({
                type: 'warning',
                title: 'Viewing Distance ⚠️',
                text: `Too close! Move seating back ${immersionData.viewingDistance.adjustment}ft for better comfort.`
            });
        } else {
            recs.push({
                type: 'info',
                title: 'Viewing Distance',
                text: `Good distance. You could sit ${immersionData.viewingDistance.adjustment}ft closer for more immersion.`
            });
        }
        
        // Audio immersion
        const audioScore = immersionData.audioScore;
        if (audioScore >= 80) {
            recs.push({
                type: 'success',
                title: 'Audio Setup ✓',
                text: `Excellent ${currentDesign.speakers.config} configuration will provide immersive surround sound.`
            });
        } else if (audioScore >= 60) {
            recs.push({
                type: 'info',
                title: 'Audio Setup',
                text: `Good audio setup. Consider upgrading to 7.1 or adding Atmos for better immersion.`
            });
        } else {
            recs.push({
                type: 'warning',
                title: 'Audio Setup',
                text: `Basic audio. Upgrade to 5.1 or 7.1 surround sound for true theater experience.`
            });
        }
        
        // Room acoustics
        const roomVolume = currentDesign.room.width * currentDesign.room.length * currentDesign.room.height;
        if (roomVolume < 1000) {
            recs.push({
                type: 'info',
                title: 'Room Acoustics',
                text: `Cozy space! Add acoustic panels and carpet to reduce reflections and improve sound quality.`
            });
        } else if (roomVolume > 3000) {
            recs.push({
                type: 'info',
                title: 'Room Acoustics',
                text: `Large room provides excellent bass response. Consider additional subwoofers for even coverage.`
            });
        }
        
        // Seating comfort
        const totalSeats = currentDesign.seating.rows * currentDesign.seating.seatsPerRow;
        if (currentDesign.seating.type === 'recliner' && totalSeats <= 8) {
            recs.push({
                type: 'success',
                title: 'Seating Comfort ✓',
                text: `Perfect! Theater recliners provide the ultimate viewing experience for ${totalSeats} people.`
            });
        }
        
        // Experience prediction
        recs.push({
            type: 'experience',
            title: '🎬 Your Theater Experience',
            text: generateExperienceDescription(immersionData)
        });
        
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
    
    function calculateImmersionScore() {
        const screenSize = currentDesign.screen.size;
        const screenWidthFeet = (screenSize * 0.87) / 12;
        const roomLength = currentDesign.room.length;
        
        // Calculate actual viewing distance from front row
        let actualDistance = roomLength * 0.4; // Approximate front row distance
        
        // Optimal distance calculation
        const optimalMin = screenWidthFeet * 1.5;
        const optimalMax = screenWidthFeet * 2.5;
        const optimalDistance = (optimalMin + optimalMax) / 2;
        
        // Distance score (0-40 points)
        let distanceScore = 0;
        let distanceStatus = 'optimal';
        let distanceAdjustment = 0;
        
        if (actualDistance >= optimalMin && actualDistance <= optimalMax) {
            distanceScore = 40;
            distanceStatus = 'optimal';
        } else if (actualDistance < optimalMin) {
            distanceScore = Math.max(20, 40 - (optimalMin - actualDistance) * 5);
            distanceStatus = 'too_close';
            distanceAdjustment = Math.round((optimalMin - actualDistance) * 10) / 10;
        } else {
            distanceScore = Math.max(25, 40 - (actualDistance - optimalMax) * 3);
            distanceStatus = 'too_far';
            distanceAdjustment = Math.round((actualDistance - optimalDistance) * 10) / 10;
        }
        
        // Audio score (0-30 points)
        const audioConfigs = {
            '2.1': 15,
            '5.1': 25,
            '7.1': 28,
            '9.1': 30
        };
        const audioScore = audioConfigs[currentDesign.speakers.config] || 10;
        
        // Room score (0-20 points)
        const roomVolume = currentDesign.room.width * currentDesign.room.length * currentDesign.room.height;
        let roomScore = 15;
        if (roomVolume < 800) roomScore = 10;
        if (roomVolume > 2000) roomScore = 20;
        
        // Seating score (0-10 points)
        let seatingScore = 5;
        if (currentDesign.seating.type === 'recliner') seatingScore = 10;
        if (currentDesign.seating.type === 'sofa') seatingScore = 7;
        
        const totalScore = Math.round(distanceScore + audioScore + roomScore + seatingScore);
        
        let description = '';
        if (totalScore >= 90) {
            description = 'Outstanding! This setup will deliver a truly cinematic experience that rivals commercial theaters.';
        } else if (totalScore >= 75) {
            description = 'Excellent setup! You\'ll enjoy immersive movie nights with great picture and sound quality.';
        } else if (totalScore >= 60) {
            description = 'Good theater setup. A few improvements could make it even better.';
        } else {
            description = 'Basic setup. Consider the recommendations below to enhance your theater experience.';
        }
        
        return {
            score: totalScore,
            description: description,
            viewingDistance: {
                status: distanceStatus,
                distance: Math.round(actualDistance * 10) / 10,
                adjustment: distanceAdjustment
            },
            audioScore: audioScore,
            roomScore: roomScore,
            seatingScore: seatingScore
        };
    }
    
    function generateExperienceDescription(data) {
        const screenSize = currentDesign.screen.size;
        const audioConfig = currentDesign.speakers.config;
        const totalSeats = currentDesign.seating.rows * currentDesign.seating.seatsPerRow;
        
        let experience = `With your ${screenSize}" ${currentDesign.screen.type} and ${audioConfig} surround sound, `;
        
        if (data.score >= 85) {
            experience += `movie nights will feel like premium cinema experiences. Action scenes will be thrilling, dialogue crystal clear, and you'll feel immersed in every scene.`;
        } else if (data.score >= 70) {
            experience += `you'll enjoy excellent movie nights with great picture quality and immersive sound that brings films to life.`;
        } else if (data.score >= 55) {
            experience += `you'll have enjoyable movie nights with good picture and sound quality for entertainment with family and friends.`;
        } else {
            experience += `you'll have a basic viewing setup. Consider the recommendations above to create a more immersive experience.`;
        }
        
        return experience;
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
                // Overview angle - shows whole theater layout
                camera.position.set(15, 12, 15);
                camera.lookAt(0, 0, 0);
                break;
            case 'top':
                // Top-down view for layout planning
                camera.position.set(0, room.height + 8, 0);
                camera.lookAt(0, 0, 0);
                break;
            case 'side':
                // Side view to see seating elevation and screen height
                camera.position.set(room.width + 8, room.height/2, 0);
                camera.lookAt(0, room.height/2, 0);
                break;
            case 'viewer':
                // CUSTOMER EXPERIENCE VIEW - from the best seat
                positionCameraAsViewer();
                break;
        }
        
        controls.update();
    }
    
    // NEW: Customer experience view - see what they'll actually see
    function positionCameraAsViewer() {
        // Find the center seat in the front row (best viewing position)
        if (seats.length > 0) {
            const frontRowSeats = seats.filter(seat => seat.position.z === Math.min(...seats.map(s => s.position.z)));
            const centerSeat = frontRowSeats[Math.floor(frontRowSeats.length / 2)];
            
            if (centerSeat) {
                // Position camera at eye level of person sitting in center front seat
                const viewerHeight = 3.8; // Eye level when seated (3.8 feet)
                camera.position.set(
                    centerSeat.position.x,
                    viewerHeight,
                    centerSeat.position.z
                );
                
                // Look directly at the center of the screen
                if (screen) {
                    camera.lookAt(screen.position.x, screen.position.y, screen.position.z);
                } else {
                    // Look toward front of room if no screen
                    camera.lookAt(0, viewerHeight, -currentDesign.room.length/2);
                }
                
                // Disable controls in viewer mode for immersive experience
                if (controls) {
                    controls.enabled = false;
                    setTimeout(() => {
                        if (controls) controls.enabled = true;
                    }, 3000); // Re-enable after 3 seconds
                }
            }
        }
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