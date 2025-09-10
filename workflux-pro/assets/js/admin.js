(function(){
  // eslint-disable-next-line no-undef
  if (typeof WFP_ADMIN === 'undefined') return;

  function api(path, opts){
    opts = opts || {};
    opts.headers = Object.assign({ 'X-WP-Nonce': WFP_ADMIN.rest.nonce }, opts.headers || {});
    return fetch(WFP_ADMIN.rest.root + path, opts).then(function(r){return r.json()});
  }

  function renderAttendance(){
    var body = document.getElementById('wfp-attendance-body');
    if(!body) return;
    api('attendance').then(function(data){
      var items = (data && data.items) ? data.items : [];
      body.innerHTML = '';
      if(items.length === 0){
        var tr = document.createElement('tr');
        var td = document.createElement('td');
        td.colSpan = 3; td.textContent = 'No records';
        tr.appendChild(td); body.appendChild(tr); return;
      }
      items.forEach(function(row){
        var tr = document.createElement('tr');
        var tds = [row.clock_in || '-', row.clock_out || '-', row.activity || '-'];
        tds.forEach(function(text){ var td = document.createElement('td'); td.textContent = text; tr.appendChild(td); });
        body.appendChild(tr);
      });
    }).catch(function(){
      body.innerHTML = '<tr><td colspan="3">Failed to load</td></tr>';
    });
  }

  function renderLeaves(){
    var pendingBody = document.getElementById('wfp-leaves-body');
    var myBody = document.getElementById('wfp-my-leaves-body');
    var form = document.getElementById('wfp-leave-form');

    if (pendingBody) {
      api('leaves').then(function(data){
        var items = (data && data.items) ? data.items : [];
        pendingBody.innerHTML = '';
        if(items.length === 0){
          var tr = document.createElement('tr');
          var td = document.createElement('td');
          td.colSpan = 6; td.textContent = 'No pending requests';
          tr.appendChild(td); pendingBody.appendChild(tr); return;
        }
        items.forEach(function(row){
          var tr = document.createElement('tr');
          var dates = (row.start_date || '') + ' → ' + (row.end_date || '');
          tr.innerHTML = '<td>' + (row.user_name || row.user_id) + '</td>'+
                         '<td>' + (row.type || '-') + '</td>'+
                         '<td>' + dates + '</td>'+
                         '<td>' + (row.reason || '-') + '</td>'+
                         '<td>' + (row.status || '-') + '</td>'+
                         '<td>'+
                           '<button class="button" data-leave-approve="' + row.id + '">Approve</button> '+
                           '<button class="button" data-leave-reject="' + row.id + '">Reject</button>'+
                         '</td>';
          pendingBody.appendChild(tr);
        });
      }).catch(function(){
        pendingBody.innerHTML = '<tr><td colspan="6">Failed to load</td></tr>';
      });
    }

    if (myBody) {
      api('leaves').then(function(data){
        var items = (data && data.items) ? data.items : [];
        myBody.innerHTML = '';
        if(items.length === 0){
          var tr = document.createElement('tr');
          var td = document.createElement('td');
          td.colSpan = 4; td.textContent = 'No requests';
          tr.appendChild(td); myBody.appendChild(tr); return;
        }
        items.forEach(function(row){
          var dates = (row.start_date || '') + ' → ' + (row.end_date || '');
          var tr = document.createElement('tr');
          tr.innerHTML = '<td>' + (row.type || '-') + '</td>'+
                         '<td>' + dates + '</td>'+
                         '<td>' + (row.reason || '-') + '</td>'+
                         '<td>' + (row.status || '-') + '</td>';
          myBody.appendChild(tr);
        });
      }).catch(function(){
        myBody.innerHTML = '<tr><td colspan="4">Failed to load</td></tr>';
      });
    }

    if (form) {
      form.addEventListener('submit', function(e){
        e.preventDefault();
        var data = new FormData(form);
        var payload = {};
        data.forEach(function(v,k){ payload[k] = v; });
        api('leaves', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        }).then(function(){ renderLeaves(); });
      });
    }
  }

  function onClick(e){
    var el = e.target.closest('[data-action]');
    if(!el) return;
    var action = el.getAttribute('data-action');
    if(action !== 'clock-in' && action !== 'clock-out') return;
    api('attendance/clock', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: action === 'clock-in' ? 'in' : 'out' })
    }).then(function(){ renderAttendance(); });
  }

  document.addEventListener('click', onClick);
  renderAttendance();
  renderLeaves();
})();

