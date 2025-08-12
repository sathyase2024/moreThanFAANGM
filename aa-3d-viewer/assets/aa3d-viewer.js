(function(){
  'use strict';

  function selectAll(selector, root){
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function showError(wrapper, message){
    if (!wrapper) return;
    var el = document.createElement('div');
    el.style.position = 'absolute';
    el.style.inset = '0';
    el.style.display = 'grid';
    el.style.placeItems = 'center';
    el.style.background = 'rgba(0,0,0,0.6)';
    el.style.color = '#fff';
    el.style.zIndex = '3';
    el.style.textAlign = 'center';
    el.style.padding = '16px';
    el.textContent = message;
    wrapper.appendChild(el);
  }

  function parseBoolean(value, fallback){
    if (typeof value === 'boolean') return value;
    if (typeof value === 'string') {
      const v = value.toLowerCase();
      if (v === 'true') return true;
      if (v === 'false') return false;
    }
    return !!fallback;
  }

  function parseNumber(value, fallback){
    const n = Number(value);
    return Number.isFinite(n) ? n : fallback;
  }

  function fitCameraToObject(camera, object, controls, fitOffset){
    fitOffset = typeof fitOffset === 'number' ? fitOffset : 1.2;

    const box = new THREE.Box3();
    box.setFromObject(object);

    const size = new THREE.Vector3();
    box.getSize(size);

    const center = new THREE.Vector3();
    box.getCenter(center);

    const maxSize = Math.max(size.x, size.y, size.z);
    const fov = camera.fov * (Math.PI / 180);
    const cameraZ = Math.abs(maxSize / (2 * Math.tan(fov / 2))) * fitOffset;

    const direction = new THREE.Vector3()
      .subVectors(camera.position, controls && controls.target ? controls.target : new THREE.Vector3())
      .normalize();

    camera.position.copy(center.clone().add(direction.multiplyScalar(cameraZ)));

    camera.near = cameraZ / 100;
    camera.far = cameraZ * 100;
    camera.updateProjectionMatrix();

    if (controls) {
      controls.target.copy(center);
      controls.update();
    }
  }

  function initCanvas(canvas){
    const wrapper = canvas.parentElement;
    if (typeof THREE === 'undefined' || !THREE.WebGLRenderer) {
      showError(wrapper, '3D dependencies failed to load. Check internet connection and plugin scripts.');
      return;
    }

    const posterEl = wrapper.querySelector('.aa3d-poster');
    const arButton = wrapper.querySelector('.aa3d-ar-button');

    const width = canvas.clientWidth;
    const height = canvas.clientHeight;

    const background = canvas.getAttribute('data-background') || '#111111';
    const src = canvas.getAttribute('data-src');
    const exposure = parseNumber(canvas.getAttribute('data-exposure'), 1.0);
    const autoRotate = parseBoolean(canvas.getAttribute('data-autorotate'), true);
    const cameraFov = parseNumber(canvas.getAttribute('data-camera-fov'), 45);

    const maxAzimuthAngle = canvas.getAttribute('data-max-azimuth-angle');
    const minAzimuthAngle = canvas.getAttribute('data-min-azimuth-angle');
    const maxPolarAngle = canvas.getAttribute('data-max-polar-angle');
    const minPolarAngle = canvas.getAttribute('data-min-polar-angle');

    let renderer;
    try {
      renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true, alpha: false, powerPreference: 'high-performance' });
    } catch (e) {
      showError(wrapper, 'WebGL initialization failed. Your browser or device may not support WebGL.');
      return;
    }
    renderer.setSize(width, height, false);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    if (renderer.outputColorSpace !== undefined && THREE.SRGBColorSpace !== undefined) {
      renderer.outputColorSpace = THREE.SRGBColorSpace;
    } else if (renderer.outputEncoding !== undefined && THREE.sRGBEncoding !== undefined) {
      renderer.outputEncoding = THREE.sRGBEncoding;
    }
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = exposure;
    renderer.shadowMap.enabled = true;

    const scene = new THREE.Scene();
    scene.background = new THREE.Color(background);

    const camera = new THREE.PerspectiveCamera(cameraFov, width / height, 0.1, 1000);
    camera.position.set(0, 1, 3);

    const controls = new THREE.OrbitControls(camera, canvas);
    controls.enableDamping = true;
    controls.dampingFactor = 0.05;
    controls.enablePan = true;
    controls.autoRotate = !!autoRotate;
    controls.autoRotateSpeed = 1.0;
    if (maxAzimuthAngle) controls.maxAzimuthAngle = parseNumber(maxAzimuthAngle, Infinity);
    if (minAzimuthAngle) controls.minAzimuthAngle = parseNumber(minAzimuthAngle, -Infinity);
    if (maxPolarAngle) controls.maxPolarAngle = parseNumber(maxPolarAngle, Math.PI);
    if (minPolarAngle) controls.minPolarAngle = parseNumber(minPolarAngle, 0);

    const hemiLight = new THREE.HemisphereLight(0xffffff, 0x444444, 1.0);
    hemiLight.position.set(0, 1, 0);
    scene.add(hemiLight);

    const dirLight = new THREE.DirectionalLight(0xffffff, 1.25);
    dirLight.position.set(5, 10, 7.5);
    dirLight.castShadow = true;
    scene.add(dirLight);

    if (!THREE.GLTFLoader) {
      showError(wrapper, 'GLTFLoader not available.');
      return;
    }
    const loader = new THREE.GLTFLoader();

    let modelRoot = null;

    function onModelLoaded(gltf){
      modelRoot = gltf.scene || gltf.scenes[0];
      modelRoot.traverse(function(obj){
        if (obj.isMesh) {
          obj.castShadow = true;
          obj.receiveShadow = true;
        }
      });
      scene.add(modelRoot);
      fitCameraToObject(camera, modelRoot, controls, 1.3);

      if (posterEl) {
        posterEl.classList.add('aa3d-hidden');
        posterEl.setAttribute('aria-hidden', 'true');
      }
    }

    function onProgress(evt){ }

    function onError(error){
      console.error('[AA3D] Failed to load model', error);
      showError(wrapper, 'Failed to load 3D model. Check the src URL.');
      if (posterEl) {
        posterEl.classList.add('aa3d-error');
      }
    }

    if (src) {
      loader.load(src, onModelLoaded, onProgress, onError);
    } else {
      showError(wrapper, 'Missing model src attribute.');
    }

    function resizeRendererToDisplaySize(){
      const w = canvas.clientWidth;
      const h = canvas.clientHeight;
      const needResize = canvas.width !== Math.floor(w * renderer.getPixelRatio()) || canvas.height !== Math.floor(h * renderer.getPixelRatio());
      if (needResize) {
        renderer.setSize(w, h, false);
      }
      camera.aspect = w / h;
      camera.updateProjectionMatrix();
    }

    function render(){
      resizeRendererToDisplaySize();
      controls.update();
      renderer.render(scene, camera);
      requestAnimationFrame(render);
    }

    requestAnimationFrame(render);

    window.addEventListener('resize', function(){
      const w = canvas.clientWidth;
      const h = canvas.clientHeight;
      renderer.setSize(w, h, false);
      camera.aspect = w / h;
      camera.updateProjectionMatrix();
    });

    if (arButton && navigator.xr && navigator.xr.isSessionSupported) {
      navigator.xr.isSessionSupported('immersive-ar').then(function(supported){
        if (supported) { arButton.hidden = false; }
      }).catch(function(){});
    }

    return { scene: scene, camera: camera, renderer: renderer, controls: controls };
  }

  function initAll(){
    selectAll('canvas.aa3d-canvas').forEach(function(canvas){
      if (!canvas.__aa3d_inited) {
        canvas.__aa3d_inited = true;
        initCanvas(canvas);
      }
    });
  }

  if (document.readyState === 'complete' || document.readyState === 'interactive') {
    initAll();
  } else {
    document.addEventListener('DOMContentLoaded', initAll);
  }
})();