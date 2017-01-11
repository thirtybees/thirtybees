$(document).ready(function() {

  if (!!$.prototype.fancybox)
    $('a.iframe').fancybox({
      'type': 'iframe',
      'width': 600,
      'height': 600
    });

  if (typeof cart_gift != 'undefined' && cart_gift && $('input#gift').is(':checked'))
    $('p#gift_div').show();

  $(document).on('change', 'input.delivery_option_radio', function() {
    var key = $(this).data('key');
    var id_address = parseInt($(this).data('id_address'));
    if (orderProcess == 'order' && key && id_address)
      updateExtraCarrier(key, id_address);
    else if (orderProcess == 'order-opc' && typeof updateCarrierSelectionAndGift !== 'undefined')
      updateCarrierSelectionAndGift();
  });

  $(document).on('submit', 'form[name=carrier_area]', function() {
    return acceptCGV();
  });

});

function acceptCGV() {
  if (typeof msg_order_carrier != 'undefined' && $('#cgv').length && !$('input#cgv:checked').length) {
    PrestaShop.showError(msg_order_carrier);
    return false;
  }

  return true;
}
