cj(function($) {
  // Generic function to update the selected logo
  function crm_netbanx_update_logo(filename) {
    if (filename) {
      $('select#netbanx_logo').css('background', 'url(' + CRM.netbanx.baseurl_images + '/' + filename + ') 0 1.5em no-repeat');
    }
    else {
      $('select#netbanx_logo').css('background', '');
    }
  }

  crm_netbanx_update_logo($('select#netbanx_logo').val());

  $('select#netbanx_logo option').each(function() {
    cj(this).css('background', 'url(' + CRM.netbanx.baseurl_images + '/' + cj(this).val() + ') 0 1.5em no-repeat');
  });

  $('select#netbanx_logo').change(function() {
    crm_netbanx_update_logo(cj(this).val());
  });
});

