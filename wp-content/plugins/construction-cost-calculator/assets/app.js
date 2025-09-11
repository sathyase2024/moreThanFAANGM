(function(){
  function formatCurrency(value){
    var amount = isFinite(value) ? value : 0;
    return 'Rs. ' + new Intl.NumberFormat(undefined, {maximumFractionDigits:0}).format(Math.round(amount));
  }

  function calculate(){
    var container = document.querySelector('.constructo-wrapper');
    if(!container){ return; }

    var rates = (window.CONSTRUCTO_CALC && window.CONSTRUCTO_CALC.rates) || {};

    var area = parseFloat(container.querySelector('.input-area')?.value || '0');
    var sump = parseFloat(container.querySelector('.input-sump')?.value || '0');
    var septic = parseFloat(container.querySelector('.input-septic')?.value || '0');
    var wallLen = parseFloat(container.querySelector('.input-wall-length')?.value || '0');
    var wallHt = parseFloat(container.querySelector('.input-wall-height')?.value || '0');

    var builtupCost = area * (rates.base_rate || 0);
    var sumpCost = sump * (rates.sump_rate || 0);
    var septicCost = septic * (rates.septic_rate || 0);
    var wallArea = wallLen * wallHt;
    var wallCost = wallArea * (rates.wall_rate || 0);

    var lineCosts = [builtupCost, sumpCost, septicCost, wallCost];
    var costEls = container.querySelectorAll('[data-line] [data-cost]');
    [builtupCost, sumpCost, septicCost, wallCost].forEach(function(v, i){
      if(costEls[i]) costEls[i].textContent = formatCurrency(v);
    });

    var total = lineCosts.reduce(function(a,b){return a+b;}, 0);
    var totalEl = container.querySelector('[data-total]');
    if(totalEl){ totalEl.textContent = formatCurrency(total); }
  }

  function bind(){
    var container = document.querySelector('.constructo-wrapper');
    if(!container){ return; }
    container.addEventListener('input', function(e){
      var t = e.target;
      if(t.matches('input[type="number"]')){
        calculate();
      }
    });
    calculate();
  }

  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', bind);
  } else {
    bind();
  }
})();

