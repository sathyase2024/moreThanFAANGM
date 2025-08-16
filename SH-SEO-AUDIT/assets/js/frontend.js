(function($){
	function formatMs(ms){
		if(ms === null || typeof ms === 'undefined') return '—';
		return new Intl.NumberFormat().format(ms) + ' ms';
	}

	function renderResults(container, data){
		var html = '';
		var resultsData = (data && (data.results || data.partial)) || {};
		if(!resultsData.mobile && !resultsData.desktop) return;
		html += '<div class="wpsa-summary">';
		html += '<h3>Results for ' + $('<div/>').text(data.url || (data.lead && data.lead.url) || '').html() + '</h3>';
		['mobile','desktop'].forEach(function(strategy){
			var r = (data.results && data.results[strategy]) || (data.partial && data.partial[strategy]);
			if(!r) return;
			html += '<div class="wpsa-card">';
			html += '<h4>' + strategy.charAt(0).toUpperCase() + strategy.slice(1) + '</h4>';
			html += '<div class="wpsa-grid">';
			html += '<div><strong>Performance</strong><div class="wpsa-score">' + (r.scores.performance ?? '—') + '</div></div>';
			html += '<div><strong>SEO</strong><div class="wpsa-score">' + (r.scores.seo ?? '—') + '</div></div>';
			html += '<div><strong>Accessibility</strong><div class="wpsa-score">' + (r.scores.accessibility ?? '—') + '</div></div>';
			html += '<div><strong>Best Practices</strong><div class="wpsa-score">' + (r.scores.best_practices ?? '—') + '</div></div>';
			html += '</div>';
			html += '<div class="wpsa-grid vitals">';
			html += '<div><strong>LCP</strong><div>' + formatMs(r.web_vitals.lcp_ms) + '</div></div>';
			html += '<div><strong>FCP</strong><div>' + formatMs(r.web_vitals.fcp_ms) + '</div></div>';
			html += '<div><strong>INP</strong><div>' + formatMs(r.web_vitals.inp_ms) + '</div></div>';
			html += '<div><strong>CLS</strong><div>' + (typeof r.web_vitals.cls === 'number' ? r.web_vitals.cls.toFixed(3) : '—') + '</div></div>';
			html += '<div><strong>TBT</strong><div>' + formatMs(r.web_vitals.tbt_ms) + '</div></div>';
			html += '<div><strong>Speed Index</strong><div>' + formatMs(r.web_vitals.si_ms) + '</div></div>';
			html += '<div><strong>TTI</strong><div>' + formatMs(r.web_vitals.tti_ms) + '</div></div>';
			html += '</div>';
			if(r.final_screenshot){
				html += '<div class="wpsa-screenshot"><img alt="Final screenshot" src="' + r.final_screenshot + '" /></div>';
			}
			if(r.opportunities && r.opportunities.length){
				html += '<div class="wpsa-opps"><h5>Top Opportunities</h5><ul>';
				(r.opportunities.slice(0,6)).forEach(function(o){
					html += '<li><span>' + $('<div/>').text(o.title).html() + '</span>' + (o.estimated_ms? ' <em>(' + formatMs(o.estimated_ms) + ')</em>' : '') + '</li>';
				});
				html += '</ul></div>';
			}
			html += '</div>';
		});
		html += '</div>';
		container.html(html).prop('hidden', false);
	}

	function setProgress(step, percent){
		var $p = $('.wpsa-progress');
		$p.prop('hidden', false);
		$p.find('.wpsa-progress-fill').css('width', percent + '%');
		$p.find('.wpsa-progress-steps li').each(function(){
			var s = $(this).data('step');
			$(this).toggleClass('active', s === step);
			if(percent >= 100 && s === 'desktop') $(this).addClass('done');
		});
	}

	function runStep(step, payload){
		return $.ajax({
			url: wpsa_ajax.ajax_url,
			type: 'POST',
			dataType: 'json',
			data: $.extend({}, payload, { action: 'run_wpsa_audit_step', nonce: wpsa_ajax.nonce, step: step })
		});
	}

	$(document).on('submit', '#wpsa-form', function(e){
		e.preventDefault();
		var $form = $(this);
		var company = $.trim($form.find('#wpsa_company').val());
		var email = $.trim($form.find('#wpsa_email').val());
		var phone = $.trim($form.find('#wpsa_phone').val());
		var url = $.trim($form.find('#wpsa_url').val());
		var nocache = $form.find('#wpsa_nocache').is(':checked');
		var $message = $('.wpsa-message');
		var $results = $('.wpsa-results');
		var payload = { company: company, email: email, phone: phone, url: url };
		if(nocache){ payload.bypass_cache = 1; }

		$results.prop('hidden', true).empty();
		$message.removeClass('error').text('');

		try {
			var u = new URL(url);
			if(!/^https?:$/.test(u.protocol)) throw new Error('bad');
		} catch(err){
			$message.addClass('error').text(wpsa_ajax.i18n.invalidUrl);
			return;
		}

		setProgress('mobile', 5);
		$message.text(wpsa_ajax.i18n.progress.mobile);

		runStep('mobile', payload)
			.done(function(resp){
				if(!(resp && resp.success)) throw resp;
				setProgress('desktop', 60);
				$message.text(wpsa_ajax.i18n.progress.desktop);
				renderResults($results, resp.data);
				payload.job_id = resp.data.job_id;
				return runStep('desktop', payload);
			})
			.done(function(resp){
				if(!(resp && resp.success)) throw resp;
				setProgress('desktop', 100);
				$message.text(wpsa_ajax.i18n.progress.done);
				renderResults($results, resp.data);
			})
			.fail(function(xhr){
				var msg = 'Audit failed';
				if(xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message){
					msg = xhr.responseJSON.data.message;
				}
				$message.addClass('error').text(msg);
			});
	});
})(jQuery);