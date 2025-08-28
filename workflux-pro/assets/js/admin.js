/* global jQuery, WFP */
(function ($) {
	$(function () {
		if (typeof WFP !== 'undefined' && WFP.rest) {
			fetch(WFP.rest.url + '/ping', {
				headers: { 'X-WP-Nonce': WFP.nonce }
			})
				.then(function (r) { return r.json(); })
				.then(function (data) {
					$('.wrap').find('.wfp-admin-ping').remove();
					$('.wrap').prepend('<div class="notice notice-success wfp-admin-ping"><p>WorkFlux Pro active (v ' + (data.version || '') + ').</p></div>');
				})
				.catch(function () {});
		}
	});
})(jQuery);

