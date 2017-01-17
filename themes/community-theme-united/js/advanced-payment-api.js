$(document).ready(function() {
  var handler = new PaymentOptionHandler();

  if (!!$.prototype.fancybox)
    $('a.iframe').fancybox({
      'type': 'iframe',
      'width': 600,
      'height': 600
    });

  $('p.payment_module').on('click', function(event) {
    handler.selectOption($(this));
    return;
  });

  $('#confirmOrder').on('click', function(event) {
    /* Avoid any further action */
    event.preventDefault();
    event.stopPropagation();

    if (handler.checkTOS() === false) {
      PrestaShop.showError(aeuc_tos_err_str);
      return;
    }
    if (aeuc_has_virtual_products === true && handler.checkVirtualProductRevocation() === false) {
      PrestaShop.showError(aeuc_virt_prod_err_str);
      return;
    }
    if (handler.selected_option === null) {
      PrestaShop.showError(aeuc_no_pay_err_str);
      return;
    }
    if (handler.submitForm() === false) {
      PrestaShop.showError(aeuc_submit_err_str);
      return;
    }
    return;
  });

});

var PaymentOptionHandler = function() {

  this.selected_option = null;

  this.selectOption = function(elem) {
    if (typeof elem === 'undefined' || elem.hasClass('payment_selected')) {
      return;
    }
    if (this.selected_option !== null) {
      this.unselectOption();
    }
    this.selected_option = elem;
    this.selected_option.addClass('payment_selected');
    this.selected_option.children('a:first').children('.payment_option_selected:first').fadeIn();
  };

  this.unselectOption = function() {
    this.selected_option.children('a:first').children('.payment_option_selected:first').fadeOut();
    this.selected_option.removeClass('payment_selected');
  };

  /* Return array with all payment option information required */
  this.submitForm = function() {
    if (typeof this.selected_option !== 'undefined' && this.selected_option !== null && this.selected_option.hasClass('payment_selected')) {
      var form_to_submit = this.selected_option.next('.payment_option_form').children('form:first');
      if (typeof form_to_submit !== 'undefined') {
        form_to_submit.submit();
        return true;
      }
    }
    return false;
  };

  this.checkTOS = function() {

    if ($('#cgv').prop('checked')) {
      return true;
    }

    return false;
  };

  this.checkVirtualProductRevocation = function() {
    if ($('#revocation_vp_terms_agreed').prop('checked')) {
      return true;
    }

    return false;
  };
};
