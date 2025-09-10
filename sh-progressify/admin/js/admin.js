(function($){
  function setActive(section){
    $('.shp-nav li').removeClass('is-active');
    $('.shp-nav li[data-section="'+section+'"]').addClass('is-active');
    $('.shp-section').addClass('is-hidden');
    $('#section-'+section).removeClass('is-hidden');
    var titleMap = {
      dashboard: 'Dashboard',
      manifest: 'Web App Manifest',
      installation: 'Installation',
      offline: 'Offline Usage',
      ui: 'UI Components',
      capabilities: 'App Capabilities',
      push: 'Push Notifications',
      publish: 'Publish to App Stores',
      help: 'Help Centre',
      whatsnew: "What's New"
    };
    $('#shp-section-title').text(titleMap[section] || 'Progressify');
  }

  $(document).on('click', '.shp-nav li', function(){
    var section = $(this).data('section');
    setActive(section);
  });

  // Initial state
  setActive('dashboard');
})(jQuery);

