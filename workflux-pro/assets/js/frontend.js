(function(){
  // eslint-disable-next-line no-undef
  var api = (typeof WFP !== 'undefined') ? WFP.rest : null;
  function onClick(e){
    var btn = e.target.closest('[data-action]');
    if(!btn || !api) return;
    var action = btn.getAttribute('data-action');
    if(action !== 'clock-in' && action !== 'clock-out') return;
    var payload = { action: action === 'clock-in' ? 'in' : 'out' };
    fetch(api.root + 'attendance/clock', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': api.nonce
      },
      body: JSON.stringify(payload)
    })
    .then(function(r){return r.json()})
    .then(function(data){
      var log = document.getElementById('wfp-log');
      if (log) {
        var p = document.createElement('div');
        p.textContent = 'Status: ' + (data && data.status ? data.status : 'ok');
        log.prepend(p);
      }
    })
    .catch(function(err){
      var log = document.getElementById('wfp-log');
      if (log) {
        var p = document.createElement('div');
        p.textContent = 'Error: ' + err;
        log.prepend(p);
      }
    });
  }
  document.addEventListener('click', onClick);
})();

