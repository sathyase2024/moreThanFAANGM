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

  function renderEmployees(){
    var body = document.getElementById('wfp-employees-body');
    if(!body) return;
    api('employees').then(function(data){
      var items = (data && data.items) ? data.items : [];
      body.innerHTML = '';
      if(items.length === 0){
        var tr = document.createElement('tr');
        var td = document.createElement('td'); td.colSpan = 5; td.textContent = 'No users'; tr.appendChild(td); body.appendChild(tr); return;
      }
      items.forEach(function(u){
        var tr = document.createElement('tr');
        tr.innerHTML = '<td>' + u.name + '</td>'+
                       '<td>' + (u.email||'') + '</td>'+
                       '<td><select data-emp-type="' + u.id + '"><option value="">-</option><option value="Tester"'+(u.type==='Tester'?' selected':'')+'>Tester</option><option value="Developer"'+(u.type==='Developer'?' selected':'')+'>Developer</option><option value="Designer"'+(u.type==='Designer'?' selected':'')+'>Designer</option></select></td>'+
                       '<td><select data-emp-status="' + u.id + '"><option value="pending"'+(u.status==='pending'?' selected':'')+'>Pending</option><option value="active"'+(u.status==='active'?' selected':'')+'>Active</option><option value="disabled"'+(u.status==='disabled'?' selected':'')+'>Disabled</option></select></td>'+
                       '<td><button class="button" data-emp-save="' + u.id + '">Save</button></td>';
        body.appendChild(tr);
      });
    });
  }

  function renderProjects(){
    var pBody = document.getElementById('wfp-projects-body');
    var tBody = document.getElementById('wfp-tasks-body');
    var mBody = document.getElementById('wfp-members-body');
    var pForm = document.getElementById('wfp-project-form');
    var tForm = document.getElementById('wfp-task-form');
    var mForm = document.getElementById('wfp-member-form');

    if (pBody){
      api('projects').then(function(data){
        pBody.innerHTML = '';
        (data.items||[]).forEach(function(p){
          var tr = document.createElement('tr');
          tr.innerHTML = '<td>' + p.name + ' (ID ' + p.id + ')</td><td>' + (p.deadline||'-') + '</td><td>' + (p.status||'-') + '</td>';
          pBody.appendChild(tr);
        });
      });
    }

    if (tBody){
      api('tasks').then(function(data){
        tBody.innerHTML = '';
        (data.items||[]).forEach(function(t){
          var tr = document.createElement('tr');
          tr.innerHTML = '<td>' + t.id + '</td><td>' + t.project_id + '</td><td>' + t.title + '</td><td>' + (t.assignee_id||'-') + '</td><td>' + (t.status||'-') + '</td>';
          tBody.appendChild(tr);
        });
      });
    }

    if (pForm){
      pForm.addEventListener('submit', function(e){
        e.preventDefault();
        var data = new FormData(pForm); var payload = {}; data.forEach(function(v,k){ payload[k]=v; });
        api('projects', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) }).then(function(){ renderProjects(); });
      });
    }

    if (tForm){
      tForm.addEventListener('submit', function(e){
        e.preventDefault();
        var data = new FormData(tForm); var payload = {}; data.forEach(function(v,k){ payload[k]=v; });
        api('tasks', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) }).then(function(){ renderProjects(); });
      });
    }

    if (mForm){
      mForm.addEventListener('submit', function(e){
        e.preventDefault();
        var data = new FormData(mForm); var payload = {}; data.forEach(function(v,k){ payload[k]=v; });
        api('projects/'+payload.project_id+'/members', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) }).then(function(){ loadMembers(payload.project_id); });
      });
      mForm.querySelector('input[name="project_id"]').addEventListener('change', function(){
        loadMembers(this.value);
      });
    }

    function loadMembers(projectId){
      if(!mBody || !projectId) return;
      api('projects/'+projectId+'/members').then(function(data){
        mBody.innerHTML = '';
        (data.items||[]).forEach(function(m){
          var tr = document.createElement('tr');
          tr.innerHTML = '<td>'+ projectId +'</td><td>'+ m.user_id +'</td><td>'+(m.role||'-')+'</td><td><button class="button" data-remove-member="'+projectId+':'+m.user_id+'">Remove</button></td>';
          mBody.appendChild(tr);
        });
      });
    }
  }

  function renderSettings(){
    var form = document.getElementById('wfp-settings-form');
    var status = document.getElementById('wfp-settings-status');
    if(!form) return;
    api('settings').then(function(data){
      form.workweek_days.value = (data.workweek_days||6);
      form.leave_categories.value = (data.leave_categories||[]).join(',');
    });
    form.addEventListener('submit', function(e){
      e.preventDefault();
      var cats = form.leave_categories.value.split(',').map(function(s){ return s.trim(); }).filter(Boolean);
      api('settings', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ workweek_days: parseInt(form.workweek_days.value, 10), leave_categories: cats }) }).then(function(){ status.textContent='Saved'; setTimeout(function(){ status.textContent=''; }, 1000); });
    });
  }

  function onAdminClick(e){
    var a = e.target;
    if (a.matches('[data-leave-approve]')){
      var id = a.getAttribute('data-leave-approve');
      api('leaves/'+id, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({status:'approved'}) }).then(function(){ renderLeaves(); });
    }
    if (a.matches('[data-leave-reject]')){
      var idr = a.getAttribute('data-leave-reject');
      api('leaves/'+idr, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({status:'rejected'}) }).then(function(){ renderLeaves(); });
    }
    if (a.matches('[data-emp-save]')){
      var uid = a.getAttribute('data-emp-save');
      var typeSel = document.querySelector('[data-emp-type="'+uid+'"]');
      var statusSel = document.querySelector('[data-emp-status="'+uid+'"]');
      api('employees/'+uid, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ type: typeSel ? typeSel.value : '', status: statusSel ? statusSel.value : 'pending' }) }).then(function(){ a.disabled=true; setTimeout(function(){ a.disabled=false; }, 600); });
    }
    if (a.matches('[data-remove-member]')){
      var parts = a.getAttribute('data-remove-member').split(':');
      api('projects/'+parts[0]+'/members/'+parts[1], { method:'DELETE' }).then(function(){ a.closest('tr').remove(); });
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
  document.addEventListener('click', onAdminClick);
  renderAttendance();
  renderLeaves();
  renderEmployees();
  renderProjects();
  renderSettings();
})();

