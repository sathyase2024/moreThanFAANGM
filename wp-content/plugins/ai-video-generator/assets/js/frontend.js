(function(){
	function qs(root, sel){ return root.querySelector(sel); }
	function qsa(root, sel){ return Array.prototype.slice.call(root.querySelectorAll(sel)); }
	function setHidden(el, hidden){ if(!el) return; el.hidden = !!hidden; }
	function setProgress(container, percent){
		var bar = qs(container, '.aivid-progress-bar');
		if(bar){ bar.style.width = (percent||0) + '%'; }
	}
	function htmlEscape(str){
		return String(str).replace(/[&<>"']/g, function(m){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#039;'}[m]); });
	}

	document.addEventListener('DOMContentLoaded', function(){
		qsa(document, '.aivid-form').forEach(function(form){
			form.addEventListener('submit', function(ev){
				ev.preventDefault();
				var containerId = form.getAttribute('data-container-id');
				var container = document.getElementById(containerId) || form.closest('.aivid-container');
				var statusEl = qs(container, '.aivid-status');
				var progressEl = qs(container, '.aivid-progress');
				var resultEl = qs(container, '.aivid-result');
				setHidden(statusEl, false);
				setHidden(progressEl, false);
				setHidden(resultEl, true);
				statusEl.textContent = 'Submitting…';
				setProgress(container, 0);

				var prompt = qs(form, 'textarea[name="prompt"]').value.trim();
				var duration = parseInt(qs(form, 'select[name="duration"]').value, 10) || 5;
				var aspect = qs(form, 'select[name="aspect_ratio"]').value;

				form.querySelector('button[type="submit"]').disabled = true;

				fetch((AIVID && AIVID.restBase ? AIVID.restBase : '/wp-json/ai-video/v1') + '/jobs', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': (AIVID && AIVID.ajaxNonce) ? AIVID.ajaxNonce : ''
					},
					body: JSON.stringify({ prompt: prompt, duration: duration, aspect_ratio: aspect })
				}).then(function(r){ return r.json().then(function(j){ return { ok: r.ok, status: r.status, body: j }; }); })
				.then(function(res){
					if(!res.ok){ throw new Error((res.body && res.body.message) || 'Request failed (' + res.status + ')'); }
					statusEl.textContent = 'Queued. Starting…';
					pollJob(res.body.job_id);
				})
				.catch(function(err){
					statusEl.textContent = 'Error: ' + err.message;
					form.querySelector('button[type="submit"]').disabled = false;
				});

				function pollJob(jobId){
					var url = (AIVID && AIVID.restBase ? AIVID.restBase : '/wp-json/ai-video/v1') + '/jobs/' + jobId;
					fetch(url, { headers: { 'X-WP-Nonce': (AIVID && AIVID.ajaxNonce) ? AIVID.ajaxNonce : '' } })
						.then(function(r){ return r.json().then(function(j){ return { ok: r.ok, status: r.status, body: j }; }); })
						.then(function(res){
							if(!res.ok){ throw new Error((res.body && res.body.message) || 'Polling failed (' + res.status + ')'); }
							var b = res.body;
							setProgress(container, b.progress || 0);
							if(b.status === 'succeeded' && (b.media_url || b.result_url)){
								statusEl.textContent = 'Done.';
								setHidden(resultEl, false);
								var videoUrl = b.media_url || b.result_url;
								resultEl.innerHTML = '<video controls src="' + encodeURI(videoUrl) + '"></video>';
								form.querySelector('button[type="submit"]').disabled = false;
							}
							else if(b.status === 'failed' || b.status === 'canceled'){
								statusEl.textContent = 'Job ' + b.status + '.';
								form.querySelector('button[type="submit"]').disabled = false;
							}
							else {
								statusEl.textContent = 'Generating… ' + (b.progress || 0) + '%';
								setTimeout(function(){ pollJob(jobId); }, 2000);
							}
						})
						.catch(function(err){
							statusEl.textContent = 'Error: ' + err.message;
							form.querySelector('button[type="submit"]').disabled = false;
						});
				}
			});
		});
	});
})();