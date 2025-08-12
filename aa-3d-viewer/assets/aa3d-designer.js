(function(){
  'use strict';
  function selectAll(selector, root){
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }
  function init(wrapper){
    var iframe = wrapper.querySelector('.aa3d-designer-iframe');
    var overlay = wrapper.querySelector('.aa3d-designer-overlay');
    var loaded = false;

    function showOverlay(){
      if (!overlay) return;
      overlay.hidden = false;
    }

    // If the iframe loads successfully, mark loaded
    if (iframe) {
      iframe.addEventListener('load', function(){
        loaded = true;
      });
      iframe.addEventListener('error', function(){
        showOverlay();
      });
    }

    // Timeout: if not loaded in a few seconds, likely blocked by X-Frame-Options/ CSP
    setTimeout(function(){
      if (!loaded) showOverlay();
    }, 3500);
  }
  function initAll(){
    selectAll('.aa3d-designer-wrapper').forEach(function(el){
      if (!el.__aa3d_designer_inited){
        el.__aa3d_designer_inited = true;
        init(el);
      }
    });
  }
  if (document.readyState === 'complete' || document.readyState === 'interactive') {
    initAll();
  } else {
    document.addEventListener('DOMContentLoaded', initAll);
  }
})();