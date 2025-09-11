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

    var total = 0;

    // Update visible rates in each row and compute in one pass
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
      total += lineCost;
    });

    var totalEl = container.querySelector('[data-total]');
    if(totalEl){ totalEl.textContent = formatCurrency(total); }
  }

  function ordinal(n){
    var s=['th','st','nd','rd'], v=n%100; return n+(s[(v-20)%10]||s[v]||s[0]);
  }

  function renderBuiltupInputs(){
    var container = document.querySelector('.constructo-wrapper');
    if(!container){ return; }
    var row = container.querySelector('.constructo-row[data-key="builtup"] .c-col.area');
    var floorsSelect = container.querySelector('.constructo-floor');
    if(!row || !floorsSelect){ return; }
    var numAdditional = parseInt(floorsSelect.value || '0', 10);
    var totalFloors = 1 + (isFinite(numAdditional) ? numAdditional : 0); // ground + N

    var wrap = document.createElement('div');
    wrap.className = 'grid-2';

    for(var i=0;i<totalFloors;i++){
      var input = document.createElement('input');
      input.type = 'number';
      input.min = '0';
      input.step = '1';
      if(i===0){
        input.placeholder = 'Ground sqft';
      } else {
        input.placeholder = ordinal(i) + ' floor sqft';
      }
      wrap.appendChild(input);
    }

    row.innerHTML = '';
    row.appendChild(wrap);
  }

  function bind(){
    var container = document.querySelector('.constructo-wrapper');
    if(!container){ return; }
    container.addEventListener('input', function(e){
      if(e.target.matches('input[type="number"]')){ recalc(); }
    });
    container.addEventListener('change', function(e){
      if(e.target.matches('.constructo-package')){ recalc(); }
      if(e.target.matches('.constructo-floor')){ renderBuiltupInputs(); recalc(); }
    });
    var openBtn = container.querySelector('.constructo-button');
    var modal = container.querySelector('.constructo-modal');
    var closeBtn = container.querySelector('.constructo-modal__close');
    var form = container.querySelector('.constructo-form');

    function syncTotalToForm(){
      var totalEl = container.querySelector('[data-total]');
      var raw = totalEl?.textContent || '0';
      var num = parseFloat((raw || '0').replace(/[^0-9.]/g, '')) || 0;
      if(form){
        var disp = form.querySelector('input[name="estimated_total_display"]');
        var hidden = form.querySelector('input[name="estimated_total"]');
        if(disp){ disp.value = raw; }
        if(hidden){ hidden.value = num; }
      }
    }

    if(openBtn && modal){
      openBtn.addEventListener('click', function(e){
        e.preventDefault();
        syncTotalToForm();
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('constructo-no-scroll');
      });
    }
    if(closeBtn && modal){
      closeBtn.addEventListener('click', function(){
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('constructo-no-scroll');
      });
      modal.addEventListener('click', function(e){
        if(e.target === modal){
          modal.setAttribute('aria-hidden', 'true');
          document.body.classList.remove('constructo-no-scroll');
        }
      });
    }

    if(form){
      form.addEventListener('submit', function(e){
        e.preventDefault();
        syncTotalToForm();
        var status = form.querySelector('.constructo-form__status');
        if(status){ status.textContent = 'Sending...'; }
        var fd = new FormData(form);
        fd.append('action', 'construction_calc_submit');
        fd.append('nonce', (window.CONSTRUCTION_CALC && window.CONSTRUCTION_CALC.ajax && window.CONSTRUCTION_CALC.ajax.nonce) || '');
        fetch((window.CONSTRUCTION_CALC && window.CONSTRUCTION_CALC.ajax && window.CONSTRUCTION_CALC.ajax.url) || '', {
          method: 'POST',
          credentials: 'same-origin',
          body: fd
        }).then(function(r){ return r.json(); }).then(function(res){
          if(res && res.success){
            if(status){ status.textContent = res.data && res.data.message ? res.data.message : 'Sent!'; }
            form.reset();
          } else {
            if(status){ status.textContent = (res && res.data && res.data.message) ? res.data.message : 'Failed. Please try again.'; }
          }
        }).catch(function(){
          if(status){ status.textContent = 'Network error. Please try later.'; }
        });
      });
    }
    renderBuiltupInputs();
    recalc();
  }

  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', bind);
  } else {
    bind();
  }
})();

