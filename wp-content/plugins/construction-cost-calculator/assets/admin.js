(function($){
  function parseJSON(text, fallback){
    try{ return JSON.parse(text || ''); }catch(e){ return fallback; }
  }
  function renderPackages($wrap, data){
    data = data || {standard:2099,premium:2399,luxury:2699};
    var $items = $('<div class="cc-admin-items"/>');
    Object.keys(data).forEach(function(key){
      var val = data[key];
      $items.append(renderPackageRow(key, val));
    });
    $wrap.empty().append(toolbar('packages')).append($items);
  }
  function renderPackageRow(name, rate){
    var $row = $('<div class="cc-item"/>');
    $row.append('<input type="text" class="cc-name" placeholder="Key (e.g., standard)" value="'+ (name||'') +'"/>');
    $row.append('<input type="number" class="cc-rate" placeholder="Rate per sqft" step="1" min="0" value="'+ (rate||0) +'"/>');
    $row.append('<div class="cc-actions"><button class="button cc-add">Add</button><button class="button button-link-delete cc-del">Delete</button></div>');
    return $row;
  }
  function gatherPackages($wrap){
    var out = {};
    $wrap.find('.cc-item').each(function(){
      var key = $(this).find('.cc-name').val().trim();
      var rate = parseFloat($(this).find('.cc-rate').val()||'0')||0;
      if(key){ out[key]=rate; }
    });
    return out;
  }

  function renderRates($wrap, data){
    data = data || {sump_rate:24,septic_rate:24,wall_rate:425};
    var $items = $('<div class="cc-admin-items"/>');
    Object.keys(data).forEach(function(key){
      $items.append(renderRateRow(key, data[key]));
    });
    $wrap.empty().append(toolbar('rates')).append($items);
  }
  function renderRateRow(name, rate){
    var $row = $('<div class="cc-item"/>');
    $row.append('<input type="text" class="cc-name" placeholder="Key (e.g., sump_rate)" value="'+ (name||'') +'"/>');
    $row.append('<input type="number" class="cc-rate" placeholder="Rate" step="1" min="0" value="'+ (rate||0) +'"/>');
    $row.append('<div class="cc-actions"><button class="button cc-add">Add</button><button class="button button-link-delete cc-del">Delete</button></div>');
    return $row;
  }
  function gatherRates($wrap){
    var out = {};
    $wrap.find('.cc-item').each(function(){
      var key = $(this).find('.cc-name').val().trim();
      var rate = parseFloat($(this).find('.cc-rate').val()||'0')||0;
      if(key){ out[key]=rate; }
    });
    return out;
  }

  function renderWorks($wrap, data){
    data = data || [
      {key:'builtup',label:'Built-up Area (Ground + floors)',unit:'sqft',rate_key:'package',inputs:[{placeholder:'Area in sqft'}]},
      {key:'sump',label:'RCC Water Sump',unit:'ltr',rate_key:'sump_rate',inputs:[{placeholder:'No. of Liters'}]},
      {key:'septic',label:'Septic Tank',unit:'ltr',rate_key:'septic_rate',inputs:[{placeholder:'No. of Liters'}]},
      {key:'wall',label:'Plain Compound Wall',unit:'sqft',rate_key:'wall_rate',math:'product',inputs:[{placeholder:'Length'},{placeholder:'Height'}]}
    ];
    var $items = $('<div class="cc-admin-items"/>');
    data.forEach(function(w){ $items.append(renderWorkRow(w)); });
    $wrap.empty().append(toolbar('works')).append($items);
  }
  function renderWorkRow(w){
    w = w || {key:'',label:'',unit:'sqft',rate_key:'package',inputs:[{placeholder:'Area'}]};
    var $row = $('<div class="cc-item"/>');
    $row.append('<input type="text" class="cc-key" placeholder="Key" value="'+ (w.key||'') +'"/>');
    $row.append('<input type="text" class="cc-label" placeholder="Label" value="'+ (w.label||'') +'"/>');
    $row.append('<input type="text" class="cc-unit" placeholder="Unit" value="'+ (w.unit||'') +'"/>');
    var $rate = $('<select class="cc-rate-key"><option value="package">package</option></select>');
    $rate.val(w.rate_key||'package');
    $row.append($rate);
    var $math = $('<select class="cc-math"><option value="sum">sum</option><option value="product">product</option></select>');
    $math.val(w.math||'sum');
    $row.append($math);
    $row.append('<input type="text" class="cc-inputs" placeholder="Inputs JSON" value="'+ (JSON.stringify(w.inputs||[{placeholder:"Area"}])) +'"/>');
    $row.append('<div class="cc-actions"><button class="button cc-add">Add</button><button class="button button-link-delete cc-del">Delete</button></div>');
    return $row;
  }
  function gatherWorks($wrap){
    var out = [];
    $wrap.find('.cc-item').each(function(){
      var w = {
        key: $(this).find('.cc-key').val().trim(),
        label: $(this).find('.cc-label').val().trim(),
        unit: $(this).find('.cc-unit').val().trim() || 'unit',
        rate_key: $(this).find('.cc-rate-key').val(),
        math: $(this).find('.cc-math').val() === 'product' ? 'product' : undefined,
        inputs: parseJSON($(this).find('.cc-inputs').val(), [{placeholder:'Area'}])
      };
      if(w.key && w.label){ out.push(w); }
    });
    return out;
  }

  function toolbar(type){
    var $bar = $('<div class="cc-admin-toolbar"/>');
    $bar.append('<div class="cc-section-title">'+ type.charAt(0).toUpperCase()+type.slice(1) +'</div>');
    var $btn = $('<button type="button" class="button button-primary">Add '+ (type==='works'?'Work':'Row') +'</button>');
    $bar.append($btn);
    $btn.on('click', function(){
      var $list = $bar.next('.cc-admin-items');
      if(type==='packages'){ $list.append(renderPackageRow('',0)); }
      if(type==='rates'){ $list.append(renderRateRow('',0)); }
      if(type==='works'){ $list.append(renderWorkRow({})); }
    });
    return $bar;
  }

  function setupList($root, type){
    var $hidden = $root.next('.cc-hidden-json');
    var initial = parseJSON($hidden.val(), type==='works'?[]:{});
    if(type==='packages'){ renderPackages($root, initial); }
    if(type==='rates'){ renderRates($root, initial); }
    if(type==='works'){ renderWorks($root, initial); }

    // Save hook: serialize on submit
    $root.closest('form').on('submit', function(){
      var data;
      if(type==='packages'){ data = gatherPackages($root); }
      if(type==='rates'){ data = gatherRates($root); }
      if(type==='works'){ data = gatherWorks($root); }
      $hidden.val(JSON.stringify(data));
    });

    // Row add/remove actions
    $root.on('click', '.cc-add', function(e){ e.preventDefault(); $(this).closest('.cc-item').after($(this).closest('.cc-item').clone()); });
    $root.on('click', '.cc-del', function(e){ e.preventDefault(); $(this).closest('.cc-item').remove(); });
  }

  $(function(){
    setupList($('#cc-admin-packages'), 'packages');
    setupList($('#cc-admin-rates'), 'rates');
    setupList($('#cc-admin-works'), 'works');
  });
})(jQuery);

