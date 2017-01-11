$(document).ready(function() {
  oosHookJsCodeMailAlert();
  $(document).on('keypress', '#oos_customer_email', function(e) {
    if (e.keyCode == 13) {
      e.preventDefault();
      addNotification();
    }
  });

  $(document).on('click', '#oos_customer_email', function(e) {
    clearText();
  });

  $(document).on('click', '#mailalert_link', function(e) {
    e.preventDefault();
    addNotification();
  });

  $(document).on('click', 'i[rel^=ajax_id_mailalert_]', function(e) {
    var ids =  $(this).attr('rel').replace('ajax_id_mailalert_', '');
    ids = ids.split('_');
    var id_product_mail_alert = parseInt(ids[0]);
    var id_product_attribute_mail_alert = parseInt(ids[1]);
    var parent = $(this).parents('li');

    if (typeof mailalerts_url_remove == 'undefined')
      return;

    $.ajax({
      url: mailalerts_url_remove,
      type: 'POST',
      data: {
        'id_product': id_product_mail_alert,
        'id_product_attribute': id_product_attribute_mail_alert
      },
      success: function(result) {
        if (result == '0') {
          parent.fadeOut('normal', function() {
            if (parent.siblings().length == 0)
              $('#mailalerts_block_account_warning').removeClass('hidden');
            parent.remove();
          });
        }
      }
    });
  });

});

function clearText() {
  if ($('#oos_customer_email').val() == mailalerts_placeholder)
    $('#oos_customer_email').val('');
}

function oosHookJsCodeMailAlert() {
  if (typeof mailalerts_url_check == 'undefined')
    return;

  $.ajax({
    type: 'POST',
    url: mailalerts_url_check,
    data: 'id_product=' + id_product + '&id_product_attribute=' + $('#idCombination').val(),
    success: function(msg) {
      if (msg == '0') {
        $('#mailalert_link').show();
        $('#oos_customer_email').show();
      } else {
        $('#mailalert_link').hide();
        $('#oos_customer_email').hide();
      }
    }
  });
}

function  addNotification() {
  if ($('#oos_customer_email').val() == mailalerts_placeholder || (typeof mailalerts_url_add == 'undefined'))
    return;

  $.ajax({
    type: 'POST',
    url: mailalerts_url_add,
    data: 'id_product=' + id_product + '&id_product_attribute=' + $('#idCombination').val() + '&customer_email=' + $('#oos_customer_email').val() + '',
    success: function(msg) {
      if (msg == '1') {
        $('#mailalert_link').hide();
        $('#oos_customer_email').hide();
        $('#oos_customer_email_result').html(mailalerts_registered);
        $('#oos_customer_email_result').css('color', 'green').show();
      } else if (msg == '2') {
        $('#oos_customer_email_result').html(mailalerts_already);
        $('#oos_customer_email_result').css('color', 'red').show();
      } else {
        $('#oos_customer_email_result').html(mailalerts_invalid);
        $('#oos_customer_email_result').css('color', 'red').show();
      }
    }
  });
}
