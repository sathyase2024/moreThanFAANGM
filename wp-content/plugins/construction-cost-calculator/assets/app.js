(function(){
  function formatCurrency(value){
    var amount = isFinite(value) ? value : 0;
    return 'Rs. ' + new Intl.NumberFormat(undefined, {maximumFractionDigits:0}).format(Math.round(amount));
  }

  function getPackageRate(packages, selectedKey){
    return packages[selectedKey] || 0;
  }

  function recalc(){
    var container = document.querySelector('.constructo-wrapper');
    if(!container){ return; }

    var data = window.CONSTRUCTION_CALC || {};
    var packages = data.packages || {};
    var rates = data.rates || {};
    var selectedPackage = container.querySelector('.constructo-package')?.value;
    var packageRate = getPackageRate(packages, selectedPackage);

    // Update visible rates in each row
    container.querySelectorAll('.constructo-row[data-key]').forEach(function(row){
      var rateKey = row.getAttribute('data-rate-key');
      var rateValue = rateKey === 'package' ? packageRate : (rates[rateKey] || 0);
      var rateEl = row.querySelector('[data-rate]');
      if(rateEl){ rateEl.textContent = 'Rs. ' + new Intl.NumberFormat().format(rateValue); }

      var inputs = Array.from(row.querySelectorAll('input[type="number"]'));
      var qty = 0;
      if(row.getAttribute('data-math') === 'product'){
        qty = inputs.reduce(function(acc, el){
          var v = parseFloat(el.value || '0');
          return acc * (isFinite(v) ? v : 0);
        }, 1);
      } else {
        qty = inputs.reduce(function(acc, el){
          var v = parseFloat(el.value || '0');
          return acc + (isFinite(v) ? v : 0);
        }, 0);
      }

      var lineCost = qty * rateValue;
      var costEl = row.querySelector('[data-cost]');
      if(costEl){ costEl.textContent = formatCurrency(lineCost); }
    });

    var total = 0;
    container.querySelectorAll('.constructo-row [data-cost]').forEach(function(el){
      var text = el.textContent.replace(/[^0-9.]/g, '') || '0';
      total += parseFloat(text || '0');
    });
    var totalEl = container.querySelector('[data-total]');
    if(totalEl){ totalEl.textContent = formatCurrency(total); }
  }

  function bind(){
    var container = document.querySelector('.constructo-wrapper');
    if(!container){ return; }
    container.addEventListener('input', function(e){
      if(e.target.matches('input[type="number"]')){ recalc(); }
    });
    container.addEventListener('change', function(e){
      if(e.target.matches('.constructo-package')){ recalc(); }
    });
    container.querySelector('.constructo-button')?.addEventListener('click', function(e){
      e.preventDefault();
      alert('Thank you! A contact form can open here.');
    });
    recalc();
  }

  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', bind);
  } else {
    bind();
  }
})();

