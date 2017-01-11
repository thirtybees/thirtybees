$(function() {
  $('#block-history').find('.footab').footable();
});

//show the order-details with ajax
function showOrder(mode, var_content, file) {
  var $orderList = $('#order-list');
  var $blockOrderDetail = $('#block-order-detail');

  $blockOrderDetail.addClass('loading-overlay');
  $orderList.addClass('loading-overlay');
  $.get(
    file,
    ((mode === 1) ? {'id_order': var_content, 'ajax': true} : {'id_order_return': var_content, 'ajax': true}),
    function(data) {
      $('#block-order-detail').fadeOut(function() {
        $blockOrderDetail.html(data).removeClass('loading-overlay');
        $orderList.removeClass('loading-overlay');

        bindOrderDetailForm();

        $blockOrderDetail.fadeIn(function() {
          $.scrollTo($blockOrderDetail, 1000, {offset: -(50 + 10)});
        });
      });
    }
  );
}

function updateOrderLineDisplay(domCheckbox) {
  var $tr = $(domCheckbox).closest('tr');
  var lineQuantitySpan    = $tr.find('.order_qte_span');
  var lineQuantityInput   = $tr.find('.order_qte_input');
  var lineQuantityButtons = $tr.find('.return_quantity_up, .return_quantity_down');

  if ($(domCheckbox).is(':checked')) {
    lineQuantitySpan.hide();
    lineQuantityInput.show();
    lineQuantityButtons.show();
  } else {
    lineQuantityInput.hide();
    lineQuantityButtons.hide();
    lineQuantityInput.val(lineQuantitySpan.text());
    lineQuantitySpan.show();
  }
}

function bindOrderDetailForm() {

  var $orderDetail = $('#order-detail-content');

  /* if return is allowed*/
  if ($orderDetail.find('.order_cb').length > 0) {

    //return slip : check or uncheck every checkboxes
    $orderDetail.find('th input[type=checkbox]').on('click', function() {
      $orderDetail.find('td input[type=checkbox]').each(function() {
        $(this).prop('checked', $orderDetail.find('th input[type=checkbox]').is(':checked'));
        updateOrderLineDisplay(this);
      });
    });

    //return slip : enable or disable 'global' quantity editing
    $orderDetail.find('td input[type=checkbox]').on('click', function() {
      updateOrderLineDisplay(this);
    });

    //return slip : limit quantities
    $orderDetail.find('td .order_qte_input').on('keyup', function() {
      var maxQuantity = parseInt($(this).parent().find('.order_qte_span').text());
      var quantity = parseInt($(this).val());
      if (isNaN($(this).val()) && $(this).val() !== '') {
        $(this).val(maxQuantity);
      } else {
        if (quantity > maxQuantity)
          $(this).val(maxQuantity);
        else if (quantity < 1)
          $(this).val(1);
      }
    });

    // The button to increment the product return value
    $(document).on('click', '.return_quantity_down', function(e) {
      e.preventDefault();
      var $input = $(this).parent().parent().find('input');
      var count = parseInt($input.val()) - 1;
      count = count < 1 ? 1 : count;
      $input.val(count);
      $input.trigger('change');
    });

    // The button to decrement the product return value
    $(document).on('click', '.return_quantity_up', function(e) {
      e.preventDefault();
      var maxQuantity = parseInt($(this).parent().parent().find('.order_qte_span').text());
      var $input = $(this).parent().parent().find('input');
      var count = parseInt($input.val()) + 1;
      count = count > maxQuantity ? maxQuantity : count;
      $input.val(count);
      $input.trigger('change');
    });
  }

  $('#sendOrderMessage').on('submit', function(e) {
    e.preventDefault();

    var $form   = $('#sendOrderMessage');
    var $submit = $form.find('[type="submit"]');
    var query   = $form.serialize() + '&ajax=true';

    $form.addClass('loading-overlay');
    $submit.prop('disabled', 'disabled');
    $.ajax({
      type: 'POST',
      headers: {'cache-control': 'no-cache'},
      url: $form.attr('action') + '?rand=' + new Date().getTime(),
      data: query,
      success: function(msg) {
        $('#block-order-detail').fadeOut(function() {
          $(this).html(msg);
          bindOrderDetailForm();
          $(this).fadeIn();
        });
      },
      complete: function() {
        $form.removeClass('loading-overlay');
        $submit.prop('disabled', false);
      }
    });
  });
}
