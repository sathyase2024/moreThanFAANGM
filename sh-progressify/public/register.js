(function(){
  if (!('serviceWorker' in navigator)) return;
  if (window.location.protocol !== 'https:') return;

  window.addEventListener('load', function(){
    navigator.serviceWorker.register(SHP_PWA.swUrl).catch(function(){});
  });

  // Minimal install banner for beforeinstallprompt (Chrome/Edge)
  var deferred;
  window.addEventListener('beforeinstallprompt', function(e){
    e.preventDefault();
    deferred = e;
    ensureBanner();
    showBanner();
  });

  function ensureBanner(){
    if (document.querySelector('.shp-install-banner')) return;
    var el = document.createElement('div');
    el.className = 'shp-install-banner';
    el.innerHTML = '<span>Install our web app</span> <button class="button-install">Install</button> <button class="button-dismiss">Dismiss</button>';
    document.body.appendChild(el);
    el.querySelector('.button-install').addEventListener('click', function(){
      if (!deferred) return hideBanner();
      deferred.prompt();
      deferred.userChoice.finally(function(){ hideBanner(); deferred = null; });
    });
    el.querySelector('.button-dismiss').addEventListener('click', hideBanner);
  }

  function showBanner(){
    var el = document.querySelector('.shp-install-banner');
    if (el) el.style.display = 'block';
  }
  function hideBanner(){
    var el = document.querySelector('.shp-install-banner');
    if (el) el.style.display = 'none';
  }
})();

