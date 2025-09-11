(function(){
	function onReady(fn){ if(document.readyState!=='loading'){ fn(); } else { document.addEventListener('DOMContentLoaded', fn); } }
	onReady(function(){
		var items = document.querySelectorAll('.sh-ct-item');
		if(!('IntersectionObserver' in window)){
			for(var i=0;i<items.length;i++){ items[i].classList.add('is-visible'); }
			return;
		}
		var io = new IntersectionObserver(function(entries){
			entries.forEach(function(entry){
				if(entry.isIntersecting){ entry.target.classList.add('is-visible'); io.unobserve(entry.target); }
			});
		},{ threshold: 0.15 });
		for(var j=0;j<items.length;j++){ io.observe(items[j]); }
	});
})();

