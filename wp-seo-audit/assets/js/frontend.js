(function($){
	function formatMs(ms){
		if(ms === null || typeof ms === 'undefined') return '—';
		return new Intl.NumberFormat().format(ms) + ' ms';
	}

	function renderResults(container, data){
		var html = '';
		if(!data || !data.results) return;
		html += '<div class="wpsa-summary">';
		html += '<h3>Results for ' + $('<div/>').text(data.url).html() + '</h3>';
		['mobile','desktop'].forEach(function(strategy){
			if(!data.results[strategy]) return;
			var r = data.results[strategy];
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

	$(document).on('submit', '#wpsa-form', function(e){
		e.preventDefault();
		var $form = $(this);
		var url = $.trim($form.find('#wpsa_url').val());
		var $message = $('.wpsa-message');
		var $results = $('.wpsa-results');

		$results.prop('hidden', true).empty();
		$message.removeClass('error').text('');

		try {
			var u = new URL(url);
			if(!/^https?:$/.test(u.protocol)) throw new Error('bad');
		} catch(err){
			$message.addClass('error').text(wpsa_ajax.i18n.invalidUrl);
			return;
		}

		$message.text(wpsa_ajax.i18n.running);

		$.ajax({
			url: wpsa_ajax.ajax_url,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'run_wpsa_audit',
				nonce: wpsa_ajax.nonce,
				url: url
			}
		}).done(function(resp){
			if(resp && resp.success){
				renderResults($results, resp.data);
				$message.text('');
			}else{
				$message.addClass('error').text((resp && resp.data && resp.data.message) ? resp.data.message : 'Audit failed');
			}
		}).fail(function(xhr){
			var msg = 'Audit failed';
			if(xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message){
				msg = xhr.responseJSON.data.message;
			}
			$message.addClass('error').text(msg);
		});
	});
})(jQuery);