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
    function buildWhatsAppMessage(){
      var data = window.CONSTRUCTION_CALC || {};
      var packages = data.packages || {};
      var selectedPackageKey = container.querySelector('.constructo-package')?.value || '';
      var selectedPackageRate = getPackageRate(packages, selectedPackageKey);
      var selectedPackageLabel = container.querySelector('.constructo-package option:checked')?.textContent || selectedPackageKey;

      var floors = parseInt(container.querySelector('.constructo-floor')?.value || '0', 10);
      var totalFloors = 1 + (isFinite(floors)?floors:0);

      var lines = [];
      lines.push('Architectural Construction Cost Calculator 2025 (Tamilnadu)');
      lines.push('Package: ' + selectedPackageLabel);
      lines.push('Floors: Ground + ' + floors);

      // Built-up details
      var builtRow = container.querySelector('.constructo-row[data-key="builtup"]');
      if(builtRow){
        var inputs = Array.from(builtRow.querySelectorAll('input[type="number"]'));
        var labels = [];
        var qty = 0;
        for(var i=0;i<inputs.length;i++){
          var val = parseFloat(inputs[i].value || '0') || 0;
          qty += val;
          labels.push((i===0? 'Ground' : (ordinal(i) + ' Floor')) + ': ' + val + ' sqft');
        }
        var cost = qty * selectedPackageRate;
        if(qty>0){
          lines.push('- Built-up Area: ' + labels.join(', '));
          lines.push('  @ Rs.' + selectedPackageRate + '/sqft = ' + formatCurrency(cost));
        }
      }

      // Other rows
      container.querySelectorAll('.constructo-row[data-key]').forEach(function(row){
        var key = row.getAttribute('data-key');
        if(key === 'builtup'){ return; }
        var name = row.querySelector('.c-col.work')?.textContent.trim() || key;
        var rateKey = row.getAttribute('data-rate-key');
        var rateValue = rateKey === 'package' ? selectedPackageRate : ((data.rates||{})[rateKey] || 0);
        var unit = row.querySelector('.c-col.unit')?.textContent.trim() || '';
        var inputs = Array.from(row.querySelectorAll('input[type="number"]'));
        var qty = 0;
        var detail = '';
        if(row.getAttribute('data-math') === 'product'){
          var a = parseFloat(inputs[0]?.value || '0') || 0;
          var b = parseFloat(inputs[1]?.value || '0') || 0;
          qty = a * b;
          if(a>0 || b>0){ detail = a + ' x ' + b + ' = ' + qty + ' ' + unit; }
        } else {
          qty = inputs.reduce(function(acc, el){ var v = parseFloat(el.value||'0')||0; return acc+v; }, 0);
          if(qty>0){ detail = qty + ' ' + unit; }
        }
        if(qty>0){
          var cost = qty * rateValue;
          lines.push('- ' + name + ': ' + detail);
          lines.push('  @ Rs.' + rateValue + ' = ' + formatCurrency(cost));
        }
      });

      var totalText = container.querySelector('[data-total]')?.textContent || '';
      if(totalText){ lines.push('Total: ' + totalText); }
      return lines.join('\n');
    }

    var openBtn = container.querySelector('.constructo-button');
    if(openBtn){
      openBtn.addEventListener('click', function(e){
        e.preventDefault();
        var phone = '916382088988';
        var msg = buildWhatsAppMessage();
        var url = 'https://api.whatsapp.com/send?phone=' + phone + '&text=' + encodeURIComponent(msg);
        window.open(url, '_blank', 'noopener');
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

