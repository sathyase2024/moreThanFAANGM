(function(){
  // eslint-disable-next-line no-undef
  if (typeof WFP_ADMIN === 'undefined') return;

  function api(path, opts){
    opts = opts || {};
    opts.headers = Object.assign({ 'X-WP-Nonce': WFP_ADMIN.rest.nonce }, opts.headers || {});
    return fetch(WFP_ADMIN.rest.root + path, opts).then(function(r){
      var ct = r.headers.get('content-type') || '';
      if (!r.ok) {
        if (ct.indexOf('application/json') !== -1) {
          return r.json().then(function(j){ throw (j && (j.message || j.code)) || ('HTTP '+r.status); });
        }
        return r.text().then(function(t){ throw t || ('HTTP '+r.status); });
      }
      if (ct.indexOf('application/json') !== -1) return r.json();
      return r.text();
    });
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

  function renderMyTasks(){
    var body = document.getElementById('wfp-my-tasks');
    if(!body) return;
    api('tasks?mine=1').then(function(data){
      body.innerHTML = '';
      (data.items||[]).forEach(function(t){
        var tr = document.createElement('tr');
        tr.innerHTML = '<td>'+t.id+'</td><td>'+t.project_id+'</td><td>'+t.title+'</td><td>'+t.status+'</td>'+
          '<td><button class="button" data-start-task="'+t.id+'">Start</button> <button class="button" data-stop-task="'+t.id+'">Stop</button></td>';
        body.appendChild(tr);
      });
    }).catch(function(){ body.innerHTML = '<tr><td colspan="5">Failed to load</td></tr>'; });
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
        var projects = (data.items||[]);
        (data.items||[]).forEach(function(p){
          var tr = document.createElement('tr');
          tr.innerHTML = '<td>' + p.name + ' (ID ' + p.id + ')</td><td>' + (p.deadline||'-') + '</td><td>' + (p.status||'-') + '</td>';
          pBody.appendChild(tr);
        });
        // populate datalist
        var dl = document.getElementById('wfp-projects-list');
        if (dl){ dl.innerHTML = ''; projects.forEach(function(p){ var opt = document.createElement('option'); opt.value = p.id + ' - ' + p.name; dl.appendChild(opt); }); }
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
        api('projects', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) })
          .then(function(){ renderProjects(); })
          .catch(function(err){ alert('Create project failed: '+ err); });
      });
    }

    if (tForm){
      tForm.addEventListener('submit', function(e){
        e.preventDefault();
        var data = new FormData(tForm); var payload = {}; data.forEach(function(v,k){ payload[k]=v; });
        api('tasks', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) })
          .then(function(){ renderProjects(); })
          .catch(function(err){ alert('Create task failed: '+ err); });
      });
    }

    if (mForm){
      mForm.addEventListener('submit', function(e){
        e.preventDefault();
        var data = new FormData(mForm); var payload = {}; data.forEach(function(v,k){ payload[k]=v; });
        // parse IDs if typed as "123 - Name"
        function parseId(val){ var m = (val||'').match(/^\s*(\d+)/); return m ? parseInt(m[1],10) : parseInt(val,10) || ''; }
        var projectId = parseId(payload.project_id);
        var userId = parseId(payload.user_id);
        api('projects/'+projectId+'/members', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ project_id: projectId, user_id: userId, role: payload.role||'' }) })
          .then(function(){ loadMembers(projectId); })
          .catch(function(err){ alert('Add member failed: '+err); });
      });
      mForm.querySelector('input[name="project_id"]').addEventListener('change', function(){
        var m = (this.value||'').match(/^(\d+)/); var pid = m ? m[1] : this.value;
        loadMembers(pid);
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
      }).catch(function(err){ mBody.innerHTML = '<tr><td colspan="4">Failed to load members: '+err+'</td></tr>'; });
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

  function populateUsers(){
    var userSelect = document.getElementById('wfp-filter-user');
    var userDL = document.getElementById('wfp-users-list');
    if(!userSelect) return;
    api('users').then(function(users){
      (users||[]).forEach(function(u){
        var opt = document.createElement('option'); opt.value = u.id; opt.textContent = u.name + ' (#'+u.id+')'; userSelect.appendChild(opt);
        if (userDL){ var o2 = document.createElement('option'); o2.value = u.id + ' - ' + u.name; userDL.appendChild(o2); }
      });
    });
  }

  function runReports(){
    var runBtn = document.getElementById('wfp-run-report');
    if(!runBtn) return;
    var userSelect = document.getElementById('wfp-filter-user');
    var from = document.getElementById('wfp-filter-from');
    var to = document.getElementById('wfp-filter-to');
    var attBody = document.getElementById('wfp-report-attendance');
    var tlBody = document.getElementById('wfp-report-time');
    var exportAtt = document.getElementById('wfp-export-attendance');
    var exportTL = document.getElementById('wfp-export-timelogs');

    function toQuery(params){
      var q = Object.keys(params).filter(function(k){ return params[k] !== '' && params[k] !== null && params[k] !== undefined; })
        .map(function(k){ return encodeURIComponent(k)+'='+encodeURIComponent(params[k]); }).join('&');
      return q ? ('?'+q) : '';
    }

    function render(){
      var params = { user_id: userSelect.value || '', from: from.value || '', to: to.value || '' };
      api('reports/attendance'+toQuery(params)).then(function(data){
        attBody.innerHTML = '';
        (data.items||[]).forEach(function(r){
          var tr = document.createElement('tr');
          tr.innerHTML = '<td>'+(r.user_name||r.user_id)+'</td><td>'+ (r.clock_in||'') +'</td><td>'+ (r.clock_out||'') +'</td><td>'+ (r.activity||'') +'</td>';
          attBody.appendChild(tr);
        });
      });
      api('reports/time-logs'+toQuery(params)).then(function(data){
        tlBody.innerHTML = '';
        (data.items||[]).forEach(function(r){
          var tr = document.createElement('tr');
          tr.innerHTML = '<td>'+(r.user_name||r.user_id)+'</td><td>'+ (r.project_name||r.project_id) +'</td><td>'+ (r.task_title||r.task_id) +'</td><td>'+ (r.started_at||'') +'</td><td>'+ (r.ended_at||'') +'</td><td>'+ (r.duration_minutes||0) +'</td>';
          tlBody.appendChild(tr);
        });
      });
    }

    function exportTable(tbody, headers, filename){
      var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr')).map(function(tr){
        return Array.prototype.slice.call(tr.children).map(function(td){ return '"'+(td.textContent||'').replace(/"/g,'""')+'"'; }).join(',');
      });
      rows.unshift(headers.join(','));
      var blob = new Blob([rows.join('\n')], { type: 'text/csv' });
      var url = URL.createObjectURL(blob);
      var a = document.createElement('a'); a.href = url; a.download = filename; a.click(); URL.revokeObjectURL(url);
    }

    runBtn.addEventListener('click', function(){ render(); });
    if (exportAtt) exportAtt.addEventListener('click', function(){ exportTable(attBody, ['User','Clock In','Clock Out','Activity'], 'attendance.csv'); });
    if (exportTL) exportTL.addEventListener('click', function(){ exportTable(tlBody, ['User','Project','Task','Start','End','Minutes'], 'time_logs.csv'); });
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

  function onTaskClick(e){
    var a = e.target;
    if (a.matches('[data-start-task]')){
      var id = a.getAttribute('data-start-task');
      api('time-logs/start', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ task_id: parseInt(id,10) }) })
        .then(function(){ a.disabled=true; setTimeout(function(){ a.disabled=false; }, 800); })
        .catch(function(err){ alert('Start failed: '+err); });
    }
    if (a.matches('[data-stop-task]')){
      var id2 = a.getAttribute('data-stop-task');
      api('time-logs/stop', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ task_id: parseInt(id2,10) }) })
        .then(function(){ a.disabled=true; setTimeout(function(){ a.disabled=false; }, 800); })
        .catch(function(err){ alert('Stop failed: '+err); });
    }
  }

  document.addEventListener('click', onClick);
  document.addEventListener('click', onAdminClick);
  document.addEventListener('click', onTaskClick);
  renderAttendance();
  renderLeaves();
  renderEmployees();
  renderProjects();
  renderSettings();
  populateUsers();
  runReports();
  renderMyTasks();
})();

