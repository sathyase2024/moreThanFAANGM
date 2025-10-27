(function(){
  function ready(fn){ if(document.readyState !== 'loading'){ fn(); } else { document.addEventListener('DOMContentLoaded', fn); } }

  function setActive(container, index){
    var steps = container.querySelectorAll('.sh-step');
    steps.forEach(function(step, i){
      var tab = step.querySelector('.sh-step__tab');
      var panel = step.querySelector('.sh-step__panel');
      var isActive = (i+1) === index;
      step.classList.toggle('is-active', isActive);
      if(panel){
        panel.dataset.open = (isDesktop() ? (isActive ? 'true' : 'false') : (panel.dataset.open || 'false'));
      }
      if(tab){ tab.setAttribute('aria-selected', isActive ? 'true' : 'false'); }
    });
    container.dataset.active = String(index);
  }

  function isDesktop(){
    var bp = (window.SHProcess && SHProcess.breakpoint) || 992;
    return window.matchMedia('(min-width:'+bp+'px)').matches;
  }

  ready(function(){
    var containers = document.querySelectorAll('.sh-process');
    containers.forEach(function(container){
      var steps = container.querySelectorAll('.sh-step');
      var initial = parseInt(container.dataset.active || '1', 10) || 1;
      // Initialize
      steps.forEach(function(step, i){
        var tab = step.querySelector('.sh-step__tab');
        var panel = step.querySelector('.sh-step__panel');
        if(panel){ panel.dataset.open = 'false'; }
        if(tab){
          tab.addEventListener('click', function(){
            if(isDesktop()){
              setActive(container, i+1);
            } else {
              // accordion toggle
              var open = panel.dataset.open === 'true';
              panel.dataset.open = open ? 'false' : 'true';
              step.classList.toggle('is-active', !open);
            }
          });
          tab.addEventListener('keydown', function(e){
            if(e.key === 'Enter' || e.key === ' '){ e.preventDefault(); tab.click(); }
            if(isDesktop()){
              if(e.key === 'ArrowRight'){ e.preventDefault(); var ni = Math.min(steps.length, (i+1)+1); setActive(container, ni); steps[ni-1].querySelector('.sh-step__tab').focus(); }
              if(e.key === 'ArrowLeft'){ e.preventDefault(); var pi = Math.max(1, (i+1)-1); setActive(container, pi); steps[pi-1].querySelector('.sh-step__tab').focus(); }
            }
          });
        }
      });

      function applyMode(){
        if(isDesktop()){
          setActive(container, initial);
        } else {
          steps.forEach(function(step){
            var panel = step.querySelector('.sh-step__panel');
            var tab = step.querySelector('.sh-step__tab');
            step.classList.remove('is-active');
            if(panel){ panel.dataset.open = 'false'; }
            if(tab){ tab.setAttribute('aria-selected','false'); }
          });
        }
      }

      applyMode();
      window.addEventListener('resize', function(){
        clearTimeout(container._shRaf);
        container._shRaf = setTimeout(applyMode, 120);
      });
    });
  });
})();

