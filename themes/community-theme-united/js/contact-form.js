$(function() {

  $(document).on('change', 'select[name=id_contact]', function() {
    $('.desc_contact').hide();
    $('#desc_contact' + parseInt($(this).val())).show();
  });

  $(document).on('change', 'select[name=id_order]', function() {
    showProductSelect($(this).attr('value'));
  });

  showProductSelect($('select[name=id_order]').attr('value'));

  $('.contact-form-box').on('submit', function() {
    $(this).addClass('loading-overlay');
  });
});

function showProductSelect(id_order) {
  $('.product_select').hide().prop('disabled', 'disabled').parent('.selector').hide();
  $('.product_select').parents('.form-group').find('label').hide();
  if ($('#' + id_order + '_order_products').length > 0) {
    $('#' + id_order + '_order_products').removeProp('disabled').show().parent('.selector').removeClass('disabled').show();
    $('.product_select').parents('.form-group').show().find('label').show();
  }
}
