$(function() {
  $(document).on('click', '.add_to_compare', function(e) {
    e.preventDefault();
    if (typeof addToCompare != 'undefined') {
      addToCompare(parseInt($(this).data('id-product')));
    }
  });

  reloadProductComparison();
  compareButtonsStatusRefresh();
  totalCompareButtons();
});

function addToCompare(productId) {
  var totalValueNow = parseInt($('.bt_compare').next('.compare_product_count').val());
  var action, totalVal;
  if ($.inArray(parseInt(productId),comparedProductsIds) === -1) {
    action = 'add';
  } else {
    action = 'remove';
  }

  $.ajax({
    url: baseUri + '?controller=products-comparison&ajax=1&action=' + action + '&id_product=' + productId,
    async: true,
    cache: false,
    success: function(data) {
      if (action === 'add' && comparedProductsIds.length < comparator_max_item) {
        comparedProductsIds.push(parseInt(productId));
        compareButtonsStatusRefresh();
        totalVal = totalValueNow + 1;
        $('.bt_compare').next('.compare_product_count').val(totalVal);
        totalValue(totalVal);
      } else if (action === 'remove') {
        comparedProductsIds.splice($.inArray(parseInt(productId), comparedProductsIds), 1);
        compareButtonsStatusRefresh();
        totalVal = totalValueNow - 1;
        $('.bt_compare').next('.compare_product_count').val(totalVal);
        totalValue(totalVal);
      } else {
        PrestaShop.showError(max_item);
      }
      totalCompareButtons();
    },
    error: function() {}
  });
}

function reloadProductComparison() {
  $(document).on('click', '#product_comparison .close', function(e) {
    e.preventDefault();
    $('#product_comparison').addClass('loading-overlay');

    var id_product = '' + parseInt($(this).data('id-product'));
    var params     = url('?');
    var ids        = params['compare_product_list'].split('|');
    var index      = ids.indexOf(id_product);

    if (index > -1) {
      ids.splice(index, 1);
      params['compare_product_list'] = ids.join('|');
    }

    $.ajax({
      url: baseUri + '?controller=products-comparison&ajax=1&action=remove&id_product=' + id_product,
      cache: false,
    });

    window.location.search = '?' + $.param(params);
  });
}

function compareButtonsStatusRefresh() {
  $('.add_to_compare').each(function() {
    if ($.inArray(parseInt($(this).data('id-product')), comparedProductsIds) !== -1) {
      $(this).addClass('checked');
    } else {
      $(this).removeClass('checked');
    }
  });
}

function totalCompareButtons() {
  var totalProductsToCompare = parseInt($('.bt_compare .total-compare-val').html());
  if (typeof totalProductsToCompare !== 'number' || totalProductsToCompare === 0) {
    $('.bt_compare').attr('disabled', true);
  } else {
    $('.bt_compare').attr('disabled', false);
  }
}

function totalValue(value) {
  $('.bt_compare').find('.total-compare-val').html(value);
}
