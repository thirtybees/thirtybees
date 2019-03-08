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
/* global window, getE, toggle, jAlert */

$(document).ready(function () {
  bind_inputs();
  initCarrierWizard();

  $('#attachement_fileselectbutton').click(function () {
    $('#carrier_logo_input').trigger('click');
  });

  $('#attachement_filename').click(function () {
    $('#carrier_logo_input').trigger('click');
  });

  $('#carrier_logo_input').change(function () {
    var name = '';
    if (typeof $(this)[0].files !== 'undefined') {
      var files = $(this)[0].files;

      $.each(files, function (index, value) {
        name += value.name + ', ';
      });

      $('#attachement_filename').val(name.slice(0, -2));
    } else {
      // Internet Explorer 9 Compatibility
      name = $(this).val().split(/[\\/]/);
      $('#attachement_filename').val(name[name.length - 1]);
    }
  });

  $('#carrier_logo_remove').click(function () {
    $('#attachement_filename').val('');
  });

  var $isFreeOn = $('#is_free_on');
  var $shippingHandlingOn = $('#shipping_handling_on');
  var $shippingHandlingOff = $('#shipping_handling_off');
  if ($isFreeOn.prop('checked') === true) {
    $shippingHandlingOff.prop('checked', true).prop('disabled', true);
    $shippingHandlingOn.prop('disabled', true).prop('checked', false);
    hideFees();
  }

  $isFreeOn.click(function () {
    $shippingHandlingOff.prop('checked', true).prop('disabled', true);
    $shippingHandlingOn.prop('disabled', true).prop('checked', false);
    hideFees();
  });

  $('#is_free_off').click(function () {
    if ($shippingHandlingOff.prop('disabled') === true) {
      $shippingHandlingOff.prop('disabled', false).prop('checked', false);
      $shippingHandlingOn.prop('disabled', false).prop('checked', true);
    }
    showFees();
  });
});

function initCarrierWizard() {
  $('#carrier_wizard').smartWizard({
    labelNext: window.labelNext,
    labelPrevious: window.labelPrevious,
    labelFinish: window.labelFinish,
    fixHeight: 1,
    onShowStep: onShowStepCallback,
    onLeaveStep: onLeaveStepCallback,
    onFinish: onFinishCallback,
    transitionEffect: 'slideleft',
    enableAllSteps: window.enableAllSteps,
    keyNavigation: false
  });
  displayRangeType();
}

function displayRangeType() {
  var string;
  if (parseInt($('input[name="shipping_method"]:checked').val(), 10) === 1) {
    string = window.string_weight;
    $('.weight_unit').show();
    $('.price_unit').hide();
  } else {
    string = window.string_price;
    $('.price_unit').show();
    $('.weight_unit').hide();
  }
  $('.range_type').html(string);
}

function onShowStepCallback() {
  resizeWizard();
}

function onFinishCallback(obj, context) {
  var ok = validateStep(context.fromStep);
  if (ok) {
    ok = ajaxRequest(context.fromStep,
      $('#carrier_wizard .stepContainer .content form').serialize() + '&action=finish_step&ajax=1&step_number=' + context.fromStep);
  }

  if (ok) {
    window.location.href = window.carrierlist_url;
  }

  return false;
}

function onLeaveStepCallback(obj, context) {
  if (parseInt(context.toStep, 10) === window.nbr_steps) {
    displaySummary();
  }
  // Return false to stay on step and true to continue navigation.

  return validateStep(context.fromStep);
}

function displaySummary() {
  var idDefaultLang = typeof window.default_language !== 'undefined' ? window.default_language : 1;
  var idLang = idDefaultLang;
  // Try to find current employee language

  if (typeof window.languages !== 'undefined' && typeof window.iso_user !== 'undefined') {
    for (var i = 0; i < window.languages.length; i += 1) {
      if (window.languages[i].iso_code === window.iso_user) {
        idLang = window.languages[i].id_lang;
        break;
      }
    }
  }
  // used as buffer - you must not replace directly in the translation vars
  var html;

  // Carrier name
  $('#summary_name').text($('#name').val());

  // Delay and pricing
  var delayText = $('#delay_' + idLang).val();

  if (!delayText) {
    delayText = $('#delay_' + idDefaultLang).val();
  }
  html = window.summary_translation_meta_informations.replace('@s2', delayText);

  if ($('#is_free_on').prop('checked')) {
    html = html.replace('@s1', window.summary_translation_free);
  } else {
    html = html.replace('@s1', window.summary_translation_paid);
  }
  $('#summary_meta_informations').html(html);

  if ($('#is_free_on').prop('checked')) {
    $('#summary_shipping_cost, #summary_range').hide();
  } else {
    // Tax and calculation mode for the shipping cost
    html = window.summary_translation_shipping_cost.replace('@s2', $('#id_tax_rules_group option:selected').text());
    if ($('#billing_price').attr('checked')) {
      html = html.replace('@s1', window.summary_translation_price);
    } else {
      html = html.replace('@s1', window.summary_translation_weight);
    }
    $('#summary_shipping_cost').html(html);
    // Weight or price ranges
    html = window.summary_translation_range + ' ' + window.summary_translation_range_limit;

    var unit;
    if (parseInt($('input[name="shipping_method"]:checked').val(), 10) === 1) {
      unit = window.PS_WEIGHT_UNIT;
    } else {
      unit = window.currency_sign;
    }
    var rangeInf = window.summary_translation_undefined;

    var rangeSup = window.summary_translation_undefined;
    $('#zone_ranges .range_inf td input:text:first').each(function () {
      rangeInf = $(this).val();
    });
    $('#zone_ranges .range_sup td input:text:last').each(function () {
      rangeSup = $(this).val();
    });
    $('#summary_range').html(html.replace('@s1', rangeInf + ' ' + unit)
      .replace('@s2', rangeSup + ' ' + unit)
      .replace('@s3', $('#range_behavior option:selected').text())
    );

    $('#summary_shipping_cost, #summary_range').show();
  }
  // Delivery zones
  $('#summary_zones').html('');

  $('.input_zone').each(function () {
    if ($(this).attr('checked')) {
      $('#summary_zones').html($('#summary_zones').html() + '<li><strong>' + $(this).closest('tr').find('label').text() + '</strong></li>');
    }
  });
  // Group restrictions
  $('#summary_groups').html('');

  $('input[name$="groupBox[]"]').each(function () {
    if ($(this).attr('checked')) {
      $('#summary_groups').html($('#summary_groups').html() + '<li><strong>' + $(this).closest('tr').find('td:eq(2)').text() + '</strong></li>');
    }
  });
  // shop restrictions
  $('#summary_shops').html('');

  $('.input_shop').each(function () {
    if ($(this).attr('checked')) {
      $('#summary_shops').html($('#summary_shops').html() + '<li><strong>' + $(this).closest().text() + '</strong></li>');
    }
  });
}

function validateStep(step) {
  var ok = true;

  $('.wizard_error').remove();

  // The ranges step is the only one we validate here.
  var rangesZone;
  $('#step-' + step + ':visible #zone_ranges').each(function () {
    rangesZone = $(this);
  });

  if (rangesZone !== undefined && !$('#is_free_on').prop('checked')) {
    // Test individual values.
    rangesZone.find('.range_inf, .range_sup, .fees').find('input:text:enabled').each(function () {
      checkFieldIsNumeric($(this));
    });
    rangesZone.find('.has-error').each(function () {
      ok = false;
    });
    if (!ok) {
      displayError([invalid_value], step);
      return false;
    }

    // Test for at least one activated zone.
    ok = false;
    rangesZone.find('.fees input:checkbox:checked').each(function () {
      ok = true;
    });
    if (!ok) {
      displayError([select_at_least_one_zone], step);
      return false;
    }

    var nbrRanges = 0;
    rangesZone.find('.range_inf .input-group').each(function () {
      nbrRanges++;
    });

    // Test against negative and zero-sized ranges.
    for (var i = 0; i < nbrRanges; i++) {
      var rangeInf = rangesZone.find('.range_inf .input-group:eq(' + i + ') input:text');
      var rangeSup = rangesZone.find('.range_sup .input-group:eq(' + i + ') input:text');
      if (parseFloat(rangeInf.val()) < 0 ||
        parseFloat(rangeInf.val()) >= parseFloat(rangeSup.val())) {
        ok = false;
        rangeInf.closest('.input-group').addClass('has-error');
        rangeSup.closest('.input-group').addClass('has-error');
      }
    }
    if (!ok) {
      displayError([negative_range], step);
      return false;
    }

    // Test for a continuous series of ranges.
    for (var j = 0; nbrRanges > 1 && j < nbrRanges - 1; j += 1) {
      rangeSup = rangesZone.find('.range_sup .input-group:eq(' + j + ') input:text');
      rangeInf = rangesZone.find('.range_inf .input-group:eq(' + (j + 1) + ') input:text');
      if (parseFloat(rangeSup.val()) !== parseFloat(rangeInf.val())) {
        ok = false;
        rangeSup.closest('.input-group').addClass('has-error');
        rangeInf.closest('.input-group').addClass('has-error');
      }
    }
    if (!ok) {
      displayError([overlapping_range], step);
      return false;
    }
  }

  // All steps get validated by a POST request.
  ok = ajaxRequest(step,
    $('#carrier_wizard #step-' + step + ' form').serialize() +
    '&step_number=' + step + '&action=validate_step&ajax=1');

  return ok;
}

function ajaxRequest(step, data) {
  var success = false;

  $.ajax({
    type: "POST",
    url: validate_url,
    async: false,
    dataType: 'json',
    data: data,
    success: function (datas) {
      if (datas.has_error) {
        displayError(datas.errors, step);
      } else {
        success = true;
      }
    },
    error: function (XMLHttpRequest, textStatus, errorThrown) {
      jAlert("TECHNICAL ERROR: \n\nDetails:\nError thrown: " + XMLHttpRequest + "\nText status: " + textStatus);
    }
  });

  return success;
}

function displayError(errors, step_number) {
  $('.wizard_error').remove();
  str_error = '<div class="error wizard_error" style="display:none"><ul>';
  for (var error in errors) {
    $('input[name="' + error + '"]').closest('div.input-group').addClass('has-error');
    str_error += '<li>' + errors[error] + '</li>';
  }
  $('#step-' + step_number).prepend(str_error + '</ul></div>');
  $('.wizard_error').fadeIn('fast');
  resizeWizard();
}

function resizeWizard() {
  // @TODO: should be:
  //resizeInterval = setInterval(function (){$("#carrier_wizard").smartWizard('fixHeight'); clearInterval(resizeInterval)}, 100);

  // Because helpers/form/form.tpl adds inline scripts (ouch, this gives us
  // 4 times the same script node) and jQuery gives script nodes a height
  // (see https://bugs.jquery.com/ticket/10159), the above doesn't work
  // properly. Instead:
  $('#carrier_wizard').find('.step_container:visible').each(function () {
    var container = $(this);
    var height = 0;
    container.children().not('script, style').each(function () {
      height += $(this).outerHeight(true);
    });
    container.height(height + 5);
    container.parent().height(height + 20);
  });
}

function bind_inputs() {
  $('#zone_ranges .fees td input:checkbox').off('change').on('change', function () {
    var priceField = $(this).closest('tr').find('input:text');
    if ($(this).prop('checked')) {
      priceField.removeAttr('disabled');
      if (priceField.val().length === 0) {
        priceField.val(displayPriceValue(0));
      }
    } else {
      priceField.attr('disabled', 'disabled');
      priceField.closest('div.input-group').removeClass('has-error');
      priceField.val('');
    }

    return false;
  });

  $(document.body).off('change', 'tr.fees_all td input').on('change', 'tr.fees_all td input', function () {
    var index = $(this).closest('td').index();
    var val = $(this).val();
    if (val.length && $.isNumeric(val)) {
      $(this).val('');
      $('tr.fees').each(function () {
        $(this).find('td:eq(' + index + ') input:text:enabled')
               .val(displayPriceValue(val));
      });
    }

    return false;
  });

  $('input[name="shipping_method"]').off('click').on('click', function () {
    $.ajax({
      type: 'POST',
      url: window.validate_url,
      dataType: 'html',
      data: 'id_carrier=' + parseInt($('#id_carrier').val(), 10) + '&shipping_method=' + parseInt($(this).val(), 10) + '&action=changeRanges&ajax=1',
      success: function (data) {
        $('#zone_ranges').replaceWith(data);
        displayRangeType();
        bind_inputs();
      },
      error: function (XMLHttpRequest, textStatus, errorThrown) {
        jAlert('TECHNICAL ERROR: \n\nDetails:\nError thrown: ' + XMLHttpRequest + '\n' + 'Text status: ' + textStatus);
      }
    });
  });

  $('#zones_table td input[type=text]').off('change').on('change', function () {
    checkFieldIsNumeric($(this));
  });
}

function hideFees() {
  $('#zone_ranges .range_inf td input,#zone_ranges .range_sup td input,#zone_ranges .fees_all td input,#zone_ranges .fees td input').attr('disabled', 'disabled');
}

function showFees() {
  $('#zone_ranges .range_inf td input,#zone_ranges .range_sup td input,#zone_ranges .fees_all td input').removeAttr('disabled');
  $('#zone_ranges .fees td input:checkbox').each(function () {
    var checkbox = $(this);
    checkbox.removeAttr('disabled');
    if (checkbox.prop('checked')) {
      checkbox.closest('tr').find('input:text').removeAttr('disabled');
    }
  });
}

function add_new_range() {
  var rangesZone = $('#zone_ranges');
  var lastSup = rangesZone.find('.range_sup td:last input:text').val();

  rangesZone.find('.range_inf, .range_sup, .fees_all, .fees').each(function () {
    var node = $(this).find('td:last');
    node.after(node.clone());
  });

  rangesZone.find('.range_inf td:last input:text').val(lastSup);
  rangesZone.find('.range_sup td:last input:text').val('');

  rangesZone.find('.range_inf, .range_sup, .fees').find('td:last .form-control').each(function () {
    var control = $(this);
    var text = control.prop('name');
    text = text.substr(0, text.lastIndexOf('[')) + '[]';
    control.prop('name', text);
  });

  // delete_range button may not exist in the previous range.
  rangesZone.find('.delete_range td:last').after('<td><a href="#" onclick="delete_range();" class="btn btn-default">' + window.labelDelete + '</a></td>');

  bind_inputs();
  rebuildTabindex();
  resizeWizard();
  return false;
}

function delete_range() {
  if (confirm(delete_range_confirm)) {
    var index = $(this).closest('td').index();
    $('#zone_ranges .range_sup td:eq(' + index + '), #zone_ranges .range_inf td:eq(' + index + '), #zone_ranges .fees_all td:eq(' + index + '), #zone_ranges .delete_range td:eq(' + index + ')').remove();
    $('#zone_ranges .fees').each(function () {
      $(this).find('td:eq(' + index + ')').remove();
    });
    rebuildTabindex();
  }

  $('.wizard_error').remove();
  resizeWizard();

  return false;
}

function checkFieldIsNumeric(element) {
  var value = element.val();
  if (value.length && $.isNumeric(value)) {
    element.closest('div.input-group').removeClass('has-error');
  } else {
    element.closest('div.input-group').addClass('has-error');
  }
}

function rebuildTabindex() {
  var i = 1;
  $('#zones_table tr').each(function () {
    var j = i;
    $(this).find('td').each(function () {
      j = zones_nbr + j;
      if ($(this).index() >= 2 && $(this).find('div.input-group input')) {
        $(this).find('div.input-group input').attr('tabindex', j);
      }
    });
    i += 1;
  });
}

function checkAllZones(elem) {
  if ($(elem).is(':checked')) {
    $('.input_zone').attr('checked', 'checked');
    $('#zone_ranges .fees div.input-group input:text').removeAttr('disabled');
  }
  else {
    $('.input_zone').removeAttr('checked');
    $('#zone_ranges .fees div.input-group input:text').attr('disabled', 'disabled');
  }
}
