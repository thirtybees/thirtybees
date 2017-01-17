$(document).ready(function() {
  vat_number();
  vat_number_ajax();

  $(document).on('input', '#company, #company_invoice', function() {
    vat_number();
  });
});

function vat_number() {
  if ($('#company').length && ($('#company').val() != ''))
    $('#vat_number, #vat_number_block').show();
  else
    $('#vat_number, #vat_number_block').hide();

  if ($('#company_invoice').length && ($('#company_invoice').val() != ''))
    $('#vat_number_block_invoice').show();
  else
    $('#vat_number_block_invoice').hide();
}

function vat_number_ajax() {
  $(document).on('change', '#id_country', function() {
    if ($('#company').length && !$('#company').val())
      return;
    if (typeof vatnumber_ajax_call !== 'undefined' && vatnumber_ajax_call)
      $.ajax({
        type: 'POST',
        headers: {'cache-control': 'no-cache'},
        url: baseDir + 'modules/vatnumber/ajax.php?id_country=' + parseInt($(this).val()) + '&rand=' + new Date().getTime(),
        success: function(isApplicable) {
          if (isApplicable == '1') {
            $('#vat_area').show();
            $('#vat_number').show();
          } else
            $('#vat_area').hide();
        }
      });
  });

  $(document).on('change', '#id_country_invoice', function() {
    if ($('#company_invoice').length && !$('#company_invoice').val())
      return;
    if (typeof vatnumber_ajax_call !== 'undefined' && vatnumber_ajax_call)
      $.ajax({
        type: 'POST',
        headers: {'cache-control': 'no-cache'},
        url: baseDir + 'modules/vatnumber/ajax.php?id_country=' + parseInt($(this).val()) + '&rand=' + new Date().getTime(),
        success: function(isApplicable) {
          if (isApplicable == '1') {
            $('#vat_area_invoice').show();
            $('#vat_number_invoice').show();
          } else
            $('#vat_area_invoice').hide();
        }
      });
  });
}
