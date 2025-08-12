(function(){
  'use strict';

  function $(sel, root){ return (root||document).querySelector(sel); }
  function $all(sel, root){ return Array.prototype.slice.call((root||document).querySelectorAll(sel)); }

  var FT_TO_M = 0.3048;

  function createRoomMesh(width_m, length_m, height_m){
    var group = new THREE.Group();

    var wallMat = new THREE.MeshStandardMaterial({ color: 0xbfc3c6, side: THREE.BackSide });
    var floorMat = new THREE.MeshStandardMaterial({ color: 0x888888 });

    var roomGeo = new THREE.BoxGeometry(width_m, height_m, length_m);
    var room = new THREE.Mesh(roomGeo, wallMat);
    room.receiveShadow = true;
    group.add(room);

    var floorGeo = new THREE.PlaneGeometry(width_m, length_m);
    var floor = new THREE.Mesh(floorGeo, floorMat);
    floor.rotation.x = -Math.PI/2;
    floor.position.y = 0.001;
    floor.receiveShadow = true;
    group.add(floor);

    return group;
  }

  function createScreenPlane(diagonal_in, wallWidth_m){
    // Assume 16:9 aspect
    var aspect = 16/9;
    var diag_m = diagonal_in * 0.0254;
    var h = Math.sqrt((diag_m*diag_m) / (aspect*aspect + 1));
    var w = h * aspect;

    var geo = new THREE.PlaneGeometry(w, h);
    var mat = new THREE.MeshStandardMaterial({ color: 0x111111, metalness: 0.0, roughness: 1.0 });
    var mesh = new THREE.Mesh(geo, mat);
    mesh.position.z = - (wallWidth_m/2 - 0.02);
    mesh.position.y = h/2 + 0.5; // raise a bit
    mesh.castShadow = false;
    mesh.receiveShadow = false;
    return mesh;
  }

  function createSeatRow(numSeats, spacing_m){
    var group = new THREE.Group();
    var geo = new THREE.BoxGeometry(0.6, 0.9, 0.8);
    var mat = new THREE.MeshStandardMaterial({ color: 0x2e3a46 });
    for (var i=0;i<numSeats;i++){
      var seat = new THREE.Mesh(geo, mat);
      seat.castShadow = true;
      seat.position.x = (i - (numSeats-1)/2) * spacing_m;
      group.add(seat);
    }
    return group;
  }

  function fitCamera(scene, camera, controls, bboxTarget){
    var box = new THREE.Box3().setFromObject(bboxTarget || scene);
    var size = new THREE.Vector3(); box.getSize(size);
    var center = new THREE.Vector3(); box.getCenter(center);
    var maxSize = Math.max(size.x, size.y, size.z);
    var fov = camera.fov * (Math.PI/180);
    var dist = Math.abs(maxSize / (2 * Math.tan(fov/2))) * 1.6;
    camera.position.set(center.x, center.y + maxSize*0.2, dist);
    camera.near = dist/100; camera.far = dist*100; camera.updateProjectionMatrix();
    controls.target.copy(center);
    controls.update();
  }

  function initCanvas(canvas){
    var wrapper = canvas.parentElement;
    var ui = wrapper.querySelector('.aa3d-room-ui');

    function readConfig(){
      return {
        width_ft: parseFloat(canvas.getAttribute('data-width-ft')||'15'),
        length_ft: parseFloat(canvas.getAttribute('data-length-ft')||'20'),
        height_ft: parseFloat(canvas.getAttribute('data-height-ft')||'9'),
        screen_in: parseFloat(canvas.getAttribute('data-screen-in')||'120')
      };
    }

    var cfg = readConfig();

    var w = canvas.clientWidth || canvas.parentElement.clientWidth || 800;
    var h = canvas.clientHeight || 520;

    var renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true, alpha: false, powerPreference: 'high-performance' });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio||1, 2));
    renderer.setSize(w, h, false);
    renderer.shadowMap.enabled = true;
    if (renderer.outputColorSpace !== undefined && THREE.SRGBColorSpace !== undefined) {
      renderer.outputColorSpace = THREE.SRGBColorSpace;
    } else if (renderer.outputEncoding !== undefined && THREE.sRGBEncoding !== undefined) {
      renderer.outputEncoding = THREE.sRGBEncoding;
    }
    renderer.toneMapping = THREE.ACESFilmicToneMapping;

    var scene = new THREE.Scene();
    scene.background = new THREE.Color(0x0f1114);

    var camera = new THREE.PerspectiveCamera(50, w/h, 0.1, 2000);
    camera.position.set(0, 2, 6);

    var controls = new THREE.OrbitControls(camera, canvas);
    controls.enableDamping = true; controls.dampingFactor = 0.08; controls.enablePan = true;

    var hemi = new THREE.HemisphereLight(0xffffff, 0x444444, 1.0); hemi.position.set(0, 1, 0); scene.add(hemi);
    var dir = new THREE.DirectionalLight(0xffffff, 1.0); dir.position.set(5, 10, 7.5); dir.castShadow = true; scene.add(dir);

    var roomGroup = new THREE.Group(); scene.add(roomGroup);

    var seatRows = [];

    function rebuild(){
      // Clear
      for (var i = roomGroup.children.length - 1; i >= 0; i--) { roomGroup.remove(roomGroup.children[i]); }
      seatRows = [];

      var width_m = cfg.width_ft * FT_TO_M;
      var length_m = cfg.length_ft * FT_TO_M;
      var height_m = cfg.height_ft * FT_TO_M;

      var room = createRoomMesh(width_m, length_m, height_m);
      roomGroup.add(room);

      var screen = createScreenPlane(cfg.screen_in, length_m);
      roomGroup.add(screen);

      var row1 = createSeatRow(3, 0.9);
      row1.position.set(0, 0.45, -length_m*0.25);
      var row2 = createSeatRow(3, 0.9);
      row2.position.set(0, 0.45, -length_m*0.45);
      seatRows.push(row1, row2);
      roomGroup.add(row1); roomGroup.add(row2);

      fitCamera(scene, camera, controls, roomGroup);
    }

    function onInputChange(e){
      var key = e.target.getAttribute('data-key');
      var val = parseFloat(e.target.value);
      if (!isFinite(val)) return;
      if (key in cfg) cfg[key] = val;
      // mirror to data- attrs
      canvas.setAttribute('data-' + key.replace(/_/g,'-'), String(val));
      rebuild();
    }

    if (ui){
      $all('.aa3d-room-input', ui).forEach(function(input){
        input.addEventListener('input', onInputChange);
        input.addEventListener('change', onInputChange);
      });
    }

    rebuild();

    function resize(){
      var W = canvas.clientWidth; var H = canvas.clientHeight; if (!W || !H){ W = wrapper.clientWidth||800; H = 520; }
      renderer.setSize(W, H, false); camera.aspect = W/H; camera.updateProjectionMatrix();
    }

    function tick(){
      controls.update();
      renderer.render(scene, camera);
      requestAnimationFrame(tick);
    }

    window.addEventListener('resize', resize);
    requestAnimationFrame(tick);

    return { scene: scene, camera: camera, renderer: renderer, controls: controls };
  }

  function initAll(){
    $all('.aa3d-room-canvas').forEach(function(canvas){
      if (!canvas.__aa3d_room_inited){ canvas.__aa3d_room_inited = true; initCanvas(canvas); }
    });
  }

  if (document.readyState === 'complete' || document.readyState === 'interactive') initAll();
  else document.addEventListener('DOMContentLoaded', initAll);
})();