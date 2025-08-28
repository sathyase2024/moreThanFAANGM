/* global jQuery, WFP */
(function ($) {
	$(function () {
		$('#wfp-clock-toggle').on('click', function (e) {
			e.preventDefault();
			var $status = $('.wfp-clock-status');
			$status.text('Submitting...');
			if (typeof WFP !== 'undefined' && WFP.rest) {
				fetch(WFP.rest.url + '/ping', {
					headers: { 'X-WP-Nonce': WFP.nonce }
				})
					.then(function (r) { return r.json(); })
					.then(function () { $status.text('Clock action submitted (stub).'); })
					.catch(function () { $status.text('Failed (stub).'); });
			} else {
				$status.text('Not available.');
			}
		});
	});
})(jQuery);

