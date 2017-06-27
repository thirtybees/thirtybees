/**
 * 2007-2016 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 Thirty Bees
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
 *  @author    Thirty Bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017 Thirty Bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/* global jQuery, $, window, showSuccessMessage, showErrorMessage, moment */

$(document).ready(function () {
  var hints = $('.translatable span.hint');
  if (window.youEditFieldFor) {
    hints.html(hints.html() + '<br /><span class="red">' + window.youEditFieldFor + '</span>');
  }

  var html = '';
  var nb_notifs = 0;
  var wrapper_id = '';
  var type = [];

  $('.notifs').click(function () {
    var idWrapper = $(this).parent().attr('id');

    $.post(
      'ajax.php',
      {
        updateElementEmployee: '1',
        updateElementEmployeeType: $(this).parent().attr('data-type')
      },
      function (data) {
        if (data) {
          $('#' + idWrapper + '_value').html(0);
          $('#' + idWrapper + '_number_wrapper').hide();
        }
      }
    );
  });
  // call it once immediately, then use setTimeout if refresh is activated
  getPush(autorefresh_notifications);

});

function getPush(refresh) {
  $.ajax({
    type: 'POST',
    headers: { 'cache-control': 'no-cache' },
    url: 'ajax.php?rand=' + new Date().getTime(),
    async: true,
    cache: false,
    dataType: 'json',
    data: {
      getNotifications: '1'
    },
    success: function (json) {
      if (json) {
        // Set moment language
        moment.lang(full_language_code);

        // Add orders notifications to the list
        html = '';
        $.each(json.order.results, function (property, value) {
          html += "<a href='index.php?tab=AdminOrders&token=" + token_admin_orders + "&vieworder&id_order=" + parseInt(value.id_order) + "'>";
          html += "<p>" + order_number_msg + "&nbsp;<strong>#" + parseInt(value.id_order) + "</strong></p>";
          html += "<p class='pull-right'>" + total_msg + "&nbsp;<span class='total badge badge-success'>" + value.total_paid + "</span></p>";
          html += "<p>" + from_msg + "&nbsp;<strong>" + value.customer_name + "</strong></p>";
          html += "<small class='text-muted'><i class='icon-time'></i> " + moment(value.update_date).fromNow() + " </small>";
          html += "</a>";
        });
        if (parseInt(json.order.total, 10) > 0) {
          $('#list_orders_notif').empty().append(html);
          $('#orders_notif_value').text(json.order.total);
          $('#orders_notif_number_wrapper').removeClass('hide');
        }
        else {
          $('#orders_notif_number_wrapper').addClass('hide');
        }
        // Add customers notifications to the list
        html = '';
        $.each(json.customer.results, function (property, value) {
          html += "<a href='index.php?tab=AdminCustomers&token=" + token_admin_customers + "&viewcustomer&id_customer=" + parseInt(value.id_customer) + "'>";
          html += "<p>" + customer_name_msg + "&nbsp;<strong>#" + value.customer_name + "</strong></p>";
          html += "<small class='text-muted'><i class='icon-time'></i> " + moment(value.update_date).fromNow() + " </small>";
          html += "</a>";
        });
        if (parseInt(json.customer.total) > 0) {
          $("#list_customers_notif").empty().append(html);
          $("#customers_notif_value").text(json.customer.total);
          $("#customers_notif_number_wrapper").removeClass('hide');
        }
        else {
          $("#customers_notif_number_wrapper").addClass('hide');
        }
        // Add messages notifications to the list
        html = "";
        $.each(json.customer_message.results, function (property, value) {
          html += "<a href='index.php?tab=AdminCustomerThreads&token=" + token_admin_customer_threads + "&viewcustomer_thread&id_customer_thread=" + parseInt(value.id_customer_thread) + "'>";
          html += "<p>" + from_msg + "&nbsp;<strong>" + value.customer_name + "</strong></p>";
          html += "<small class='text-muted'><i class='icon-time'></i> " + moment(value.update_date).fromNow() + " </small>";
          html += "</a>";
        });
        if (parseInt(json.customer_message.total) > 0) {
          $("#list_customer_messages_notif").empty().append(html);
          $("#customer_messages_notif_value").text(json.customer_message.total);
          $("#customer_messages_notif_number_wrapper").removeClass('hide');
        } else {
          $("#customer_messages_notif_number_wrapper").addClass('hide');
        }
      }
      if (refresh) {
        setTimeout("getPush(1)", 120000);
      }
    }
  });
}
