(function($){
	function initGallery($gallery){
		var $slides = $gallery.find('.sh-tl-slide');
		if($slides.length <= 1){
			$slides.addClass('is-active');
			return;
		}
		var interval = parseInt($gallery.data('interval'), 10);
		if(isNaN(interval) || interval < 1000){ interval = 3500; }
		var idx = 0;
		$slides.removeClass('is-active').eq(0).addClass('is-active');
		var timer = null;
		function next(){
			var nextIdx = (idx + 1) % $slides.length;
			$slides.eq(idx).removeClass('is-active');
			$slides.eq(nextIdx).addClass('is-active');
			idx = nextIdx;
		}
		function start(){
			if(timer) return;
			timer = setInterval(next, interval);
		}
		function stop(){
			if(timer){ clearInterval(timer); timer = null; }
		}
		if(parseInt($gallery.data('autoplay'), 10) !== 0){
			start();
		}
		$gallery.on('mouseenter', stop).on('mouseleave', start);
	}

	$(document).ready(function(){
		$('.sh-tl-gallery').each(function(){ initGallery($(this)); });
	});
})(jQuery);

