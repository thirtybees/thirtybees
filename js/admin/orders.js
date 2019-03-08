/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2018 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.thirtybees.com for more information.
 *
 *  @author    thirty bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017-2018 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/* global jQuery, $, window, showSuccessMessage, showErrorMessage, ps_round */

var current_product;
var ajaxQueries = [];

function stopAjaxQuery() {
  if (typeof ajaxQueries === 'undefined') {
    ajaxQueries = [];
  }
  for (var i = 0; i < ajaxQueries.length; i++) {
    ajaxQueries[i].abort();
  }
  ajaxQueries = [];
}

function updateInvoice(invoices) {
  // Update select on product edition line
  $('.edit_product_invoice').each(function () {
    var selected = $(this).children('option:selected').val();

    $(this).children('option').remove();
    $.each(invoices, function (i) {
      // Create new option
      var option = $('<option>' + invoices[i].name + '</option>').attr('value', invoices[i].id);
      if (invoices[i].id === selected) {
        option.attr('selected', true);
      }

      $(this).append(option);
    });
  });

  // Update select on product addition line
  $('#add_product_product_invoice').each(function () {
    var parent = $(this).children('optgroup.existing');
    parent.children('option').remove();
    $.each(invoices, function (i) {
      // Create new option
      var option = $('<option>' + invoices[i].name + '</option>').attr('value', invoices[i].id);

      parent.append(option);
    });
    parent.children('option:first').attr('selected', true);
  });

  // Update select on product addition line
  $('#payment_invoice').each(function () {
    $(this).children('option').remove();
    $.each(invoices, function (i) {
      // Create new option
      var option = $('<option>' + invoices[i].name + '</option>').attr('value', invoices[i].id);

      $(this).append(option);
    });
  });
}

function updateDocuments(documentsHtml) {
  $('#documents_table').attr('id', 'documents_table_old');
  $('#documents_table_old').after(documentsHtml);
  $('#documents_table_old').remove();
}

function updateShipping(shippingHtml) {
  $('#shipping_table').attr('id', 'shipping_table_old');
  $('#shipping_table_old').after(shippingHtml);
  $('#shipping_table_old').remove();
}

function updateDiscountForm(discountFormHtml) {
  $('#voucher_form').html(discountFormHtml);
}

function populateWarehouseList(warehouseList) {
  $('#add_product_product_warehouse_area').hide();
  if (warehouseList.length > 1) {
    $('#add_product_product_warehouse_area').show();
  }
  var orderWarehouseList = $('#warehouse_list').val().split(',');
  $('#add_product_warehouse').html('');
  var warehouseSelected = false;
  $.each(warehouseList, function () {
    if (!warehouseSelected && $.inArray(this.id_warehouse, orderWarehouseList)) {
      warehouseSelected = this.id_warehouse;
    }

    $('#add_product_warehouse').append($('<option value="' + this.id_warehouse + '">' + this.name + '</option>'));
  });
  if (warehouseSelected) {
    $('#add_product_warehouse').val(warehouseSelected);
  }
}

function addProductRefreshTotal() {
  var quantity = parseInt($('#add_product_product_quantity').val(), 10);
  if (quantity < 1 || isNaN(quantity)) {
    quantity = 1;
  }
  var price;
  if (window.use_taxes) {
    price = parseFloat($('#add_product_product_price_tax_incl').val());
  } else {
    price = parseFloat($('#add_product_product_price_tax_excl').val());
  }

  if (price < 0 || isNaN(price)) {
    price = 0;
  }
  var total = makeTotalProductCaculation(quantity, price);
  $('#add_product_product_total').html(displayPrice(
    total,
    window.currency_format,
    window.currency_sign,
    window.currency_blank
  ));
}

function editProductRefreshTotal(element) {
  element = element.parent().parent().parent();
  var element_list = [];

  // Customized product
  if (element.hasClass('customized')) {
    var element_list = $('.customized-' + element.find('.edit_product_id_order_detail').val());
    element = $(element_list[0]);
  }

  var quantity = parseInt(element.find('td .edit_product_quantity').val());
  if (quantity < 1 || isNaN(quantity)) {
    quantity = 1;
  }

  var price;
  if (window.use_taxes) {
    price = parseFloat(element.find('td .edit_product_price_tax_incl').val());
  } else {
    price = parseFloat(element.find('td .edit_product_price_tax_excl').val())
  }

  if (price < 0 || isNaN(price)) {
    price = 0;
  }

  // Customized product
  if (element_list.length) {
    var qty = 0;
    $.each(element_list, function (i, elm) {
      if ($(elm).find('.edit_product_quantity').length) {
        qty += parseInt($(elm).find('.edit_product_quantity').val());
        subtotal = makeTotalProductCaculation($(elm).find('.edit_product_quantity').val(), price);
        $(elm).find('.total_product').html(displayPrice(
          subtotal, currency_format, currency_sign, currency_blank
        ));
      }
    });

    var total = makeTotalProductCaculation(qty, price);
    element.find('td.total_product').html(displayPrice(
      total, currency_format, currency_sign, currency_blank
    ));
    element.find('td.productQuantity').html(qty);
  }
  else {
    var total = makeTotalProductCaculation(quantity, price);
    element.find('td.total_product').html(displayPrice(
      total, currency_format, currency_sign, currency_blank
    ));
  }

}

function makeTotalProductCaculation(quantity, price) {
  return parseFloat((quantity * price).toFixed(priceDatabasePrecision));
}

function addViewOrderDetailRow(view) {
  var html = $(view);
  html.find('td').hide();
  $('tr#new_invoice').hide();
  $('tr#new_product').hide();

  // Initialize fields
  closeAddProduct();

  $('tr#new_product').before(html);
  html.find('td').each(function () {
    if (!$(this).is('.product_invoice')) {
      $(this).fadeIn('slow');
    }
  });
}

function refreshProductLineView(element, view) {
  var newProductLine = $(view);
  newProductLine.find('td').hide();

  var elementList = [];
  if (element.parent().parent().find('.edit_product_id_order_detail').length) {
    elementList = $('.customized-' + element.parent().parent().find('.edit_product_id_order_detail').val());
  }
  if (!elementList.length) {
    elementList = $(element.parent().parent());
  }

  var currentProductLine = element.parent().parent();
  currentProductLine.replaceWith(newProductLine);
  elementList.remove();

  newProductLine.find('td').each(function () {
    if (!$(this).is('.product_invoice')) {
      $(this).fadeIn('slow');
    }
  });
}

function updateAmounts(order) {
  $('#total_products td.amount').fadeOut('slow', function () {
    $(this).html(displayPrice(
      order.total_products_wt,
      window.currency_format,
      window.currency_sign,
      window.currency_blank
    ));
    $(this).fadeIn('slow');
  });
  $('#total_discounts td.amount').fadeOut('slow', function () {
    $(this).html(displayPrice(
      order.total_discounts_tax_incl,
      window.currency_format,
      window.currency_sign,
      window.currency_blank
    ));
    $(this).fadeIn('slow');
  });
  if (order.total_discounts_tax_incl > 0) {
    $('#total_discounts').slideDown('slow');
  }
  $('#total_wrapping td.amount').fadeOut('slow', function () {
    $(this).html(displayPrice(
      order.total_wrapping_tax_incl,
      window.currency_format,
      window.currency_sign,
      window.currency_blank
    ));
    $(this).fadeIn('slow');
  });
  if (order.total_wrapping_tax_incl > 0) {
    $('#total_wrapping').slideDown('slow');
  }
  $('#total_shipping td.amount').fadeOut('slow', function () {
    $(this).html(displayPrice(
      order.total_shipping_tax_incl,
      window.currency_format,
      window.currency_sign,
      window.currency_blank
    ));
    $(this).fadeIn('slow');
  });
  $('#total_order td.amount').fadeOut('slow', function () {
    $(this).html(displayPrice(
      order.total_paid_tax_incl,
      window.currency_format,
      window.currency_sign,
      window.currency_blank
    ));
    $(this).fadeIn('slow');
  });
  $('.total_paid').fadeOut('slow', function () {
    $(this).html(displayPrice(
      order.total_paid_tax_incl,
      window.currency_format,
      window.currency_sign,
      window.currency_blank
    ));
    $(this).fadeIn('slow');
  });
  $('.alert').slideDown('slow');
  $('#product_number').fadeOut('slow', function () {
    var oldQuantity = parseInt($(this).html(), 10);
    $(this).html(oldQuantity + 1);
    $(this).fadeIn('slow');
  });
  $('#shipping_table .weight').fadeOut('slow', function () {
    $(this).html(order.weight);
    $(this).fadeIn('slow');
  });
}

function closeAddProduct() {
  $('tr#new_invoice').hide();
  $('tr#new_product').hide();

  // Initialize fields
  $('tr#new_product select, tr#new_product input').each(function () {
    if (!$(this).is('.button')) {
      $(this).val('');
    }
  });
  $('tr#new_invoice select, tr#new_invoice input').val('');
  $('#add_product_product_quantity').val('1');
  $('#add_product_product_attribute_id option').remove();
  $('#add_product_product_attribute_area').hide();
  if (window.stock_management) {
    $('#add_product_product_stock').html('0');
  }
  window.current_product = null;
}


/**
 * This method allow to initialize all events
 */
function init() {
  $('#txt_msg').on('keyup', function () {
    var length = $('#txt_msg').val().length;
    if (length > 600) {
      length = '600+';
    }
    $('#nbchars').html(length + '/600');
  });

  $('#newMessage').unbind('click').click(function (e) {
    $(this).hide();
    $('#message').show();
    e.preventDefault();
  });

  $('#cancelMessage').unbind('click').click(function (e) {
    $('#newMessage').show();
    $('#message').hide();
    e.preventDefault();
  });

  $('#add_product').unbind('click').click(function (e) {
    $('.cancel_product_change_link:visible').trigger('click');
    $('.add_product_fields').show();
    $('.edit_product_fields, .standard_refund_fields, .partial_refund_fields, .order_action').hide();
    $('tr#new_product').slideDown('fast', function () {
      $('tr#new_product td').fadeIn('fast', function () {
        $('#add_product_product_name').focus();
        window.scroll_if_anchor('#new_product');
      });
    });
    e.preventDefault();
  });

  $('#cancelAddProduct').unbind('click').click(function () {
    $('.order_action').show();
    $('tr#new_product td').fadeOut('fast');
  });

  $('#add_product_product_name').autocomplete(window.admin_order_tab_link,
    {
      minChars: 3,
      max: 10,
      width: 500,
      selectFirst: false,
      scroll: false,
      dataType: "json",
      highlightItem: true,
      formatItem: function (data, i, max, value) {
        return value;
      },
      parse: function (data) {
        var products = []
        if (typeof data.products !== 'undefined') {
          for (var i = 0; i < data.products.length; i += 1) {
            products[i] = { data: data.products[i], value: data.products[i].name };
          }
        }
        return products;
      },
      extraParams: {
        ajax: true,
        token: window.token,
        action: 'searchProducts',
        id_lang: window.id_lang,
        id_currency: window.id_currency,
        id_address: window.id_address,
        id_customer: window.id_customer,
        product_search: function () {
          return $('#add_product_product_name').val();
        }
      }
    }
  )
    .result(function (event, data) {
      if (!data) {
        $('tr#new_product input, tr#new_product select').each(function () {
          if ($(this).attr('id') !== 'add_product_product_name') {
            $('tr#new_product input, tr#new_product select, tr#new_product button').attr('disabled', true);
          }
        });
      } else {
        $('tr#new_product input, tr#new_product select, tr#new_product button').removeAttr('disabled');
        // Keep product variable
        window.current_product = data;
        $('#add_product_product_id').val(data.id_product);
        $('#add_product_product_name').val(data.name);
        $('#add_product_product_price_tax_incl').val(displayPriceValue(
          data.price_tax_incl
        ));
        $('#add_product_product_price_tax_excl').val(displayPriceValue(
          data.price_tax_excl
        ));
        addProductRefreshTotal();
        if (window.stock_management) {
          $('#add_product_product_stock').html(data.stock[0]);
        }

        if (current_product.combinations.length !== 0) {
          // Reset combinations list
          $('select#add_product_product_attribute_id').html('');
          var defaultAttribute = 0;
          $.each(current_product.combinations, function () {
            $('select#add_product_product_attribute_id').append('<option value="' + this.id_product_attribute + '"' + (this.default_on === 1 ? ' selected="selected"' : '') + '>' + this.attributes + '</option>');
            if (this.default_on === 1) {
              if (window.stock_management) {
                $('#add_product_product_stock').html(this.qty_in_stock);
              }
              defaultAttribute = this.id_product_attribute;
            }
          });
          // Show select list
          $('#add_product_product_attribute_area').show();

          populateWarehouseList(current_product.warehouse_list[defaultAttribute]);
        } else {
          // Reset combinations list
          $('select#add_product_product_attribute_id').html('');
          // Hide select list
          $('#add_product_product_attribute_area').hide();

          populateWarehouseList(current_product.warehouse_list[0]);
        }
      }
    });

  $('select#add_product_product_attribute_id').unbind('change');
  $('select#add_product_product_attribute_id').change(function () {
    $('#add_product_product_price_tax_incl').val(displayPriceValue(
      current_product.combinations[$(this).val()].price_tax_incl
    ));
    $('#add_product_product_price_tax_excl').val(displayPriceValue(
      current_product.combinations[$(this).val()].price_tax_excl
    ));

    populateWarehouseList(current_product.warehouse_list[$(this).val()]);

    addProductRefreshTotal();
    if (window.stock_management) {
      $('#add_product_product_stock').html(current_product.combinations[$(this).val()].qty_in_stock);
    }
  });

  $('input#add_product_product_quantity').unbind('keyup').keyup(function () {
    if (window.stock_management) {
      var quantity = parseInt($(this).val(), 10);
      if (quantity < 1 || isNaN(quantity)) {
        quantity = 1;
      }
      var stockAvailable = parseInt($('#add_product_product_stock').html(), 10);
      // stock status update
      if (quantity > stockAvailable) {
        $('#add_product_product_stock').css('font-weight', 'bold').css('color', 'red').css('font-size', '1.2em');
      } else {
        $('#add_product_product_stock').css('font-weight', 'normal').css('color', 'black').css('font-size', '1em');
      }
    }
    // total update
    addProductRefreshTotal();
  });

  $('#submitAddProduct').unbind('click').click(function (e) {
    e.preventDefault();
    stopAjaxQuery();
    var go = true;

    if (!$('input#add_product_product_id').val()) {
      jAlert(window.txt_add_product_no_product);
      go = false;
    }

    if (!$('input#add_product_product_quantity').val()) {
      jAlert(window.txt_add_product_no_product_quantity);
      go = false;
    }

    if (!$('input#add_product_product_price_tax_excl').val()) {
      jAlert(window.txt_add_product_no_product_price);
      go = false;
    }

    if (go) {
      if (parseInt($('input#add_product_product_quantity').val(), 10) > parseInt($('#add_product_product_stock').html(), 10)) {
        go = confirm(window.txt_add_product_stock_issue);
      }

      if (go && $('select#add_product_product_invoice').val() == 0) {
        go = confirm(window.txt_add_product_new_invoice);
      }

      if (go) {
        var query = 'ajax=1&token=' + window.token + '&action=addProductOnOrder&id_order=' + window.id_order + '&';

        query += $('#add_product_warehouse').serialize() + '&';
        query += $('tr#new_product select, tr#new_product input').serialize();
        if ($('select#add_product_product_invoice').val() == 0) {
          query += '&' + $('tr#new_invoice select, tr#new_invoice input').serialize();
        }

        var ajaxQuery = $.ajax({
          type: 'POST',
          url: window.admin_order_tab_link,
          cache: false,
          dataType: 'json',
          data: query,
          success: function (data) {
            if (data.result) {
              if (data.refresh) {
                location.reload();
                return;
              }
              go = false;
              addViewOrderDetailRow(data.view);
              updateAmounts(data.order);
              updateInvoice(data.invoices);
              updateDocuments(data.documents_html);
              updateShipping(data.shipping_html);
              updateDiscountForm(data.discount_form_html);

              // Initialize all events
              init();

              $('.standard_refund_fields').hide();
              $('.partial_refund_fields').hide();
              $('.order_action').show();
            } else {
              jAlert(data.error);
            }
          },
          error: function (XMLHttpRequest, textStatus, errorThrown) {
            jAlert("Impossible to add the product to the cart.\n\ntextStatus: '" + textStatus + "'\nerrorThrown: '" + errorThrown + "'\nresponseText:\n" + XMLHttpRequest.responseText);
          }
        });
        ajaxQueries.push(ajaxQuery);
      }
    }
  });

  $('.edit_shipping_number_link').unbind('click').click(function (e) {
    $(this).parent().parent().find('.shipping_number_show').hide();
    $(this).parent().find('.shipping_number_edit').show();

    $(this).parent().find('.edit_shipping_number_link').hide();
    $(this).parent().find('.cancel_shipping_number_link').show();
    e.preventDefault();
  });

  $('.cancel_shipping_number_link').unbind('click').click(function (e) {
    $(this).parent().parent().find('.shipping_number_show').show();
    $(this).parent().find('.shipping_number_edit').hide();

    $(this).parent().find('.edit_shipping_number_link').show();
    $(this).parent().find('.cancel_shipping_number_link').hide();
    e.preventDefault();
  });

  $('#add_product_product_invoice').unbind('change').change(function () {
    if (!$(this).val()) {
      $('#new_invoice').slideDown('slow');
    } else {
      $('#new_invoice').slideUp('slow');
    }
  });

  $('#add_product_product_price_tax_excl').unbind('keyup').keyup(function () {
    var priceTaxExcl = parseFloat($(this).val());
    if (priceTaxExcl < 0 || isNaN(priceTaxExcl)) {
      priceTaxExcl = 0;
    }

    var taxRate = (current_product.tax_rate / 100) + 1;
    $('#add_product_product_price_tax_incl').val(displayPriceValue(
      priceTaxExcl * taxRate
    ));

    // Update total product
    addProductRefreshTotal();
  });

  $('#add_product_product_price_tax_incl').unbind('keyup').keyup(function () {
    var priceTaxIncl = parseFloat($(this).val());
    if (priceTaxIncl < 0 || isNaN(priceTaxIncl)) {
      priceTaxIncl = 0;
    }

    var taxRate = (current_product.tax_rate / 100) + 1;
    $('#add_product_product_price_tax_excl').val(displayPriceValue(
      priceTaxIncl / taxRate
    ));

    // Update total product
    addProductRefreshTotal();
  });

  $('.edit_product_change_link').unbind('click').click(function (e) {
    $('.add_product_fields, .standard_refund_fields, .order_action').hide();
    $('.edit_product_fields').show();
    $('.row-editing-warning').hide();
    $('.cancel_product_change_link:visible').trigger('click');
    closeAddProduct();
    var element = $(this);
    $.ajax({
      type: 'POST',
      url: admin_order_tab_link,
      cache: false,
      dataType: 'json',
      data: {
        ajax: 1,
        token: token,
        action: 'loadProductInformation',
        id_order_detail: element.closest('tr.product-line-row').find('input.edit_product_id_order_detail').val(),
        id_address: id_address,
        id_order: id_order
      },
      success: function (data) {
        if (data.result) {
          current_product = data;

          var element_list = $('.customized-' + element.parents('.product-line-row').find('.edit_product_id_order_detail').val());
          if (!element_list.length) {
            element_list = element.parents('.product-line-row');
            element_list.find('td .product_quantity_show').hide();
            element_list.find('td .product_quantity_edit').show();
          }
          else {
            element_list.find('td .product_quantity_show').hide();
            element_list.find('td .product_quantity_edit').show();
          }
          element_list.find('td .product_price_show').hide();
          element_list.find('td .product_price_edit').show();
          element_list.find('td.cancelCheck').hide();
          element_list.find('td.cancelQuantity').hide();
          element_list.find('td.product_invoice').show();
          $('td.product_action').attr('colspan', 3);
          $('th.edit_product_fields').show();
          $('th.edit_product_fields').attr('colspan', 2);
          element_list.find('td.product_action').attr('colspan', 1);
          element.parent().children('.edit_product_change_link').parent().hide();
          element.parent().parent().find('button.submitProductChange').show();
          element.parent().parent().find('.cancel_product_change_link').show();

          if (+data.reduction_percent != +0) {
            element_list.find('.row-editing-warning').show();
          }

          $('.standard_refund_fields').hide();
          $('.partial_refund_fields').hide();
        }
        else {
          jAlert(data.error);
        }
      }
    });
    e.preventDefault();
  });

  $('.cancel_product_change_link').unbind('click').click(function (e) {
    window.current_product = null;
    $('.edit_product_fields').hide();
    $('.row-editing-warning').hide();
    var elementList = $('.customized-' + $(this).parent().parent().find('.edit_product_id_order_detail').val());
    if (!elementList.length) {
      elementList = $($(this).parent().parent());
    }
    elementList.find('td .product_price_show').show();
    elementList.find('td .product_quantity_show').show();
    elementList.find('td .product_price_edit').hide();
    elementList.find('td .product_quantity_edit').hide();
    elementList.find('td.product_invoice').hide();
    elementList.find('td.cancelCheck').show();
    elementList.find('td.cancelQuantity').show();
    elementList.find('.edit_product_change_link').parent().show();
    elementList.find('button.submitProductChange').hide();
    elementList.find('.cancel_product_change_link').hide();
    $('.order_action').show();
    $('.standard_refund_fields').hide();
    e.preventDefault();
  });

  $('button.submitProductChange').unbind('click').click(function (e) {
    e.preventDefault();

    if ($(this).closest('tr.product-line-row').find('td .edit_product_quantity').val() <= 0) {
      jAlert(window.txt_add_product_no_product_quantity);
      return false;
    }
    if ($(this).closest('tr.product-line-row').find('td .edit_product_price').val() <= 0) {
      jAlert(window.txt_add_product_no_product_price);
      return false;
    }
    if (confirm(window.txt_confirm)) {
      var element = $(this);
      var elementList = $('.customized-' + $(this).parent().parent().find('.edit_product_id_order_detail').val());
      var query = 'ajax=1&token=' + token + '&action=editProductOnOrder&id_order=' + id_order + '&';
      if (elementList.length) {
        query += elementList.parent().parent().find('input:visible, select:visible, .edit_product_id_order_detail').serialize();
      } else {
        query += element.parent().parent().find('input:visible, select:visible, .edit_product_id_order_detail').serialize();
      }

      $.ajax({
        type: 'POST',
        url: window.admin_order_tab_link,
        cache: false,
        dataType: 'json',
        data: query,
        success: function (data) {
          if (data.result) {
            refreshProductLineView(element, data.view);
            updateAmounts(data.order);
            updateInvoice(data.invoices);
            updateDocuments(data.documents_html);
            updateDiscountForm(data.discount_form_html);

            // Initialize all events
            init();

            $('.standard_refund_fields').hide();
            $('.partial_refund_fields').hide();
            $('.add_product_fields').hide();
            $('.row-editing-warning').hide();
            $('td.product_action').attr('colspan', 3);
          } else {
            jAlert(data.error);
          }
        }
      });
    }

    return false;
  });

  $('.edit_product_price_tax_excl').unbind('keyup').keyup(function () {
    var priceTaxExcl = parseFloat($(this).val());
    if (priceTaxExcl < 0 || isNaN(priceTaxExcl)) {
      priceTaxExcl = 0;
    }
    var taxRate = (current_product.tax_rate / 100) + 1;
    $('.edit_product_price_tax_incl:visible').val(displayPriceValue(
      priceTaxExcl * taxRate
    ));
    // Update total product
    editProductRefreshTotal($(this));
  });

  $('.edit_product_price_tax_incl')
    .unbind('keyup')
    .keyup(function () {
    var priceTaxIncl = parseFloat($(this).val());
    if (priceTaxIncl < 0 || isNaN(priceTaxIncl)) {
      priceTaxIncl = 0;
    }

    var taxRate = (current_product.tax_rate / 100) + 1;
    $('.edit_product_price_tax_excl:visible').val(displayPriceValue(
      priceTaxIncl / taxRate
    ));
    // Update total product
    editProductRefreshTotal($(this));
  });

  $('.edit_product_quantity').unbind('keyup');
  $('.edit_product_quantity').keyup(function () {
    var quantity = parseInt($(this).val(), 10);
    if (quantity < 1 || isNaN(quantity)) {
      quantity = 1;
    }
    // var stock_available = parseInt($(this).parent().parent().parent().find('td.product_stock').html());
    // total update
    editProductRefreshTotal($(this));
  });

  $('.delete_product_line').unbind('click').click(function (e) {
    if (!confirm(window.txt_confirm)) {
      return false;
    }
    var trProduct = $(this).closest('.product-line-row');
    var idOrderDetail = $(this).closest('.product-line-row').find('td .edit_product_id_order_detail').val();
    var query = 'ajax=1&action=deleteProductLine&token=' + token + '&id_order_detail=' + idOrderDetail + '&id_order=' + window.id_order;

    $.ajax({
      type: 'POST',
      url: window.admin_order_tab_link,
      cache: false,
      dataType: 'json',
      data: query,
      success: function (data) {
        if (data.result) {
          trProduct.fadeOut('slow', function () {
            $(this).remove();
          });
          updateAmounts(data.order);
          updateInvoice(data.invoices);
          updateDocuments(data.documents_html);
          updateDiscountForm(data.discount_form_html);
        } else {
          jAlert(data.error);
        }
      }
    });
    e.preventDefault();
  });


  $('.js-set-payment').unbind('click').click(function (e) {
    var amount = $(this).attr('data-amount');
    $('input[name=payment_amount]').val(displayPriceValue(amount));
    var idInvoice = $(this).attr('data-id-invoice');
    $('select[name=payment_invoice] option[value=' + idInvoice + ']').attr('selected', true);
    e.preventDefault();
  });

  $('#add_voucher').unbind('click').click(function (e) {
    $('.order_action').hide();
    $('.panel-vouchers,#voucher_form').show();
    e.preventDefault();
  });

  $('#cancel_add_voucher').unbind('click').click(function (e) {
    $('#voucher_form').hide();
    if (!window.has_voucher) {
      $('.panel-vouchers').hide();
    }
    $('.order_action').show();
    e.preventDefault();
  });

  $('#discount_type').unbind('change').change(function () {
    if (parseInt($(this).val(), 10) === 1) {
      // Percent type
      $('#discount_value_field').show();
      $('#discount_currency_sign').hide();
      $('#discount_value_help').hide();
      $('#discount_percent_symbol').show();
    } else if (parseInt($(this).val(), 10) === 2) {
      // Amount type
      $('#discount_value_field').show();
      $('#discount_percent_symbol').hide();
      $('#discount_value_help').show();
      $('#discount_currency_sign').show();
    } else if (parseInt($(this).val(), 10) === 3) {
      // Free shipping
      $('#discount_value_field').hide();
    }
  });

  $('#discount_all_invoices').unbind('change').change(function () {
    if ($(this).is(':checked')) {
      $('select[name=discount_invoice]').attr('disabled', true);
    } else {
      $('select[name=discount_invoice]').attr('disabled', false);
    }
  });

  $('.open_payment_information').unbind('click').click(function (e) {
    if ($(this).parent().parent().next('tr').is(':visible')) {
      $(this).parent().parent().next('tr').hide();
    } else {
      $(this).parent().parent().next('tr').show();
    }
    e.preventDefault();
  });
}


/* Refund system script */
var flagRefund = '';

$(document).ready(function () {
  $('#desc-order-standard_refund').click(function () {
    $('.cancel_product_change_link:visible').trigger('click');
    closeAddProduct();
    if (flagRefund === 'standard') {
      flagRefund = '';
      $('.partial_refund_fields').hide();
      $('.standard_refund_fields').hide();
    } else {
      flagRefund = 'standard';
      $('.partial_refund_fields').hide();
      $('.standard_refund_fields').fadeIn();
    }
    if (window.order_discount_price) {
      actualizeTotalRefundVoucher();
    }
  });

  $('#desc-order-partial_refund').click(function () {
    $('.cancel_product_change_link:visible').trigger('click');
    closeAddProduct();
    if (flagRefund === 'partial') {
      flagRefund = '';
      $('.partial_refund_fields').hide();
      $('.standard_refund_fields').hide();
    } else {
      flagRefund = 'partial';
      $('.standard_refund_fields, .product_action, .order_action').hide();
      $('.product_action').hide();
      $('.partial_refund_fields').fadeIn();
    }

    if (window.order_discount_price) {
      actualizeRefundVoucher();
    }
  });
});

function checkPartialRefundProductQuantity(it) {
  var entered = parseInt($(it).val());
  var max = parseInt($(it).next().text().match(/\d+/)[0], 10);
  if (entered > max) {
    $(it).val(max);
  }
  if (window.order_discount_price) {
    actualizeRefundVoucher();
  }
}

function checkPartialRefundProductAmount(it) {
  // TODO: find a way to restore the limit
  if (window.order_discount_price) {
    actualizeRefundVoucher();
  }
}

function actualizeRefundVoucher() {
  var total = 0.0;
  $('.edit_product_price_tax_incl.edit_product_price').each(function () {
    window.quantity_refund_product = parseFloat($(this).closest('td').parent().find('td.partial_refund_fields.current-edit').find('input[onchange="checkPartialRefundProductQuantity(this)"]').val());
    if (window.quantity_refund_product > 0) {
      window.current_amount = parseFloat($(this).closest('td').parent().find('td.partial_refund_fields.current-edit').find('input[onchange="checkPartialRefundProductAmount(this)"]').val()) ?
        parseFloat($(this).closest('td').parent().find('td.partial_refund_fields.current-edit').find('input[onchange="checkPartialRefundProductAmount(this)"]').val())
        : parseFloat($(this).val());
      total += window.current_amount * window.quantity_refund_product;
    }
  });
  $('#total_refund_1').remove();
  $('#lab_refund_1').append('<span id="total_refund_1">' + displayPrice(total, window.currency_format, window.currency_sign, window.currency_blank) + '</span>');
  $('#lab_refund_1').append('<input type="hidden" name="order_discount_price" value=' + window.order_discount_price + '/>');
  $('#total_refund_2').remove();
  if (parseFloat(total - window.order_discount_price) > 0.0) {
    document.getElementById('refund_2').disabled = false;
    $('#lab_refund_2').append('<span id="total_refund_2">' + displayPrice((total - window.order_discount_price), window.currency_format, window.currency_sign, window.currency_blank) + '</span>');
  } else {
    if (document.getElementById('refund_2').checked === true) {
      document.getElementById('refund_1').checked = true;
    }
    document.getElementById('refund_2').disabled = true;
    $('#lab_refund_2').append('<span id="total_refund_2">' + errorRefund + '</span>');
  }
}

function actualizeTotalRefundVoucher() {
  var total = 0.0;
  $('.edit_product_price_tax_incl.edit_product_price').each(function () {
    window.quantity_refund_product = parseFloat($(this).closest('td').parent().find('td.cancelQuantity').children().val());
    if (typeof window.quantity_refund_product !== 'undefined' && window.quantity_refund_product > 0) {
      total += $(this).val() * window.quantity_refund_product;
    }
  });
  $('#total_refund_1').remove();
  $('#lab_refund_total_1').append('<span id="total_refund_1">' + displayPrice(total, window.currency_format, window.currency_sign, window.currency_blank) + '</span>');
  $('#lab_refund_total_1').append('<input type="hidden" name="order_discount_price" value=' + window.order_discount_price + '/>');
  $('#total_refund_2').remove();
  if (parseFloat(total - window.order_discount_price) > 0.0) {
    document.getElementById('refund_total_2').disabled = false;
    $('#lab_refund_total_2').append('<span id="total_refund_2">' + displayPrice((total - window.order_discount_price), window.currency_format, window.currency_sign, window.currency_blank) + '</span>');
  }
  else {
    if (document.getElementById('refund_total_2').checked === true) {
      document.getElementById('refund_total_1').checked = true;
    }
    document.getElementById('refund_total_2').disabled = true;
    $('#lab_refund_total_2').append('<span id="total_refund_2">' + window.errorRefund + '</span>');
  }
}

function setCancelQuantity(itself, idOrderDetail, quantity) {
  $('#cancelQuantity_' + idOrderDetail).val($(itself).prop('checked') ? quantity : '');
  if (window.order_discount_price) {
    actualizeTotalRefundVoucher();
  }
}

function checkTotalRefundProductQuantity(it) {
  $(it).parent().parent().find('td.cancelCheck input[type=checkbox]').attr('checked', true);
  if (parseInt($(it).val(), 10) > parseInt($(it).closest('td').find('.partialRefundProductQuantity').val(), 10)) {
    $(it).val($(it).closest('td').find('.partialRefundProductQuantity').val());
  }
  if (window.order_discount_price) {
    actualizeTotalRefundVoucher();
  }
}

$(document).ready(function () {
  // Init all events
  init();

  $('img.js-disabled-action').css({ opacity: 0.5 });
});
