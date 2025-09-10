(function(){
  // Placeholder admin JS. Could hydrate React/Vue app later.
  // eslint-disable-next-line no-undef
  if (typeof WFP_ADMIN !== 'undefined') {
    // Simple ping
    fetch(WFP_ADMIN.rest.root + 'ping', { headers: { 'X-WP-Nonce': WFP_ADMIN.rest.nonce }})
      .then(function(r){return r.json()})
      .then(function(data){
        console.debug('WFP Admin API ping:', data);
      })
      .catch(function(err){console.warn('WFP Admin API ping failed', err)});
  }
})();

