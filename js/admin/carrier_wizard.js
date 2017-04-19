/*
* 2007-2016 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

$(document).ready(function() {
	bind_inputs();
	initCarrierWizard();

	$('#attachement_fileselectbutton').click(function(e) {
		$('#carrier_logo_input').trigger('click');
	});

	$('#attachement_filename').click(function(e) {
		$('#carrier_logo_input').trigger('click');
	});

	$('#carrier_logo_input').change(function(e) {
		var name  = '';
		if ($(this)[0].files !== undefined)
		{
			var files = $(this)[0].files;

			$.each(files, function(index, value) {
				name += value.name+', ';
			});

			$('#attachement_filename').val(name.slice(0, -2));
		}
		else // Internet Explorer 9 Compatibility
		{
			name = $(this).val().split(/[\\/]/);
			$('#attachement_filename').val(name[name.length-1]);
		}
	});

	$('#carrier_logo_remove').click(function(e) {
		$('#attachement_filename').val('');
	});

	if ($('#is_free_on').prop('checked') === true)
	{
		$('#shipping_handling_off').prop('checked', true).prop('disabled', true);
		$('#shipping_handling_on').prop('disabled', true).prop('checked', false);
    hideFees();
	}

	$('#is_free_on').click(function(e) {
		$('#shipping_handling_off').prop('checked', true).prop('disabled', true);
		$('#shipping_handling_on').prop('disabled', true).prop('checked', false);
    hideFees();
	});

	$('#is_free_off').click(function(e) {
		if ($('#shipping_handling_off').prop('disabled') === true)
		{
			$('#shipping_handling_off').prop('disabled', false).prop('checked', false);
			$('#shipping_handling_on').prop('disabled', false).prop('checked', true);
		}
    showFees();
	});
});

function initCarrierWizard()
{
	$("#carrier_wizard").smartWizard({
		'labelNext' : labelNext,
		'labelPrevious' : labelPrevious,
		'labelFinish' : labelFinish,
		'fixHeight' : 1,
		'onShowStep' : onShowStepCallback,
		'onLeaveStep' : onLeaveStepCallback,
		'onFinish' : onFinishCallback,
		'transitionEffect' : 'slideleft',
		'enableAllSteps' : enableAllSteps,
		'keyNavigation' : false
	});
	displayRangeType();
}

function displayRangeType()
{
	if ($('input[name="shipping_method"]:checked').val() == 1)
	{
		string = string_weight;
		$('.weight_unit').show();
		$('.price_unit').hide();
	}
	else
	{
		string = string_price;
		$('.price_unit').show();
		$('.weight_unit').hide();
	}
	$('.range_type').html(string);
}

function onShowStepCallback() {
  resizeWizard();
}

function onFinishCallback(obj, context) {
  let ok = false;

  ok = validateStep(context.fromStep);

  if (ok) {
    ok = ajaxRequest(context.fromStep,
                     $('#carrier_wizard .stepContainer .content form').serialize()+
                     '&action=finish_step&ajax=1&step_number='+context.fromStep);
  }

  if (ok) {
    window.location.href = carrierlist_url;
  }

  return false;
}

function onLeaveStepCallback(obj, context)
{
	if (context.toStep == nbr_steps)
		displaySummary();

  // Return false to stay on step and true to continue navigation.
  return validateStep(context.fromStep);
}

function displaySummary() {
    var id_default_lang = typeof default_language !== 'undefined' ? default_language : 1,
        id_lang = id_default_lang;

    // Try to find current employee language
    if (typeof languages !== 'undefined' && typeof iso_user !== 'undefined')
        for (var i=0; i<languages.length; i++)
            if (languages[i]['iso_code'] == iso_user)
            {
                id_lang = languages[i]['id_lang'];
                break;
            }

  // used as buffer - you must not replace directly in the translation vars
  let html;

	// Carrier name
	$('#summary_name').text($('#name').val());

	// Delay and pricing
  let delayText = $('#delay_' + id_lang).val();
  if (!delayText)
    delayText = $('#delay_' + id_default_lang).val();
  html = summary_translation_meta_informations.replace('@s2', delayText);

  if ($('#is_free_on').prop('checked')) {
    html = html.replace('@s1', summary_translation_free);
  } else {
    html = html.replace('@s1', summary_translation_paid);
  }
	$('#summary_meta_informations').html(html);

  if ($('#is_free_on').prop('checked')) {
    $('#summary_shipping_cost, #summary_range').hide();
  } else {
    // Tax and calculation mode for the shipping cost
    html = summary_translation_shipping_cost
             .replace('@s2', $('#id_tax_rules_group option:selected').text());
    if ($('#billing_price').attr('checked')) {
      html = html.replace('@s1', summary_translation_price);
    } else {
      html = html.replace('@s1', summary_translation_weight);
    }
    $('#summary_shipping_cost').html(html);

    // Weight or price ranges
    html = summary_translation_range+' '+summary_translation_range_limit;

    if ($('input[name="shipping_method"]:checked').val() == 1) {
      unit = PS_WEIGHT_UNIT;
    } else {
      unit = currency_sign;
    }

    let range_inf = summary_translation_undefined;
    let range_sup = summary_translation_undefined;
    $('#zone_ranges .range_inf td input:text:first').each(function() {
      range_inf = $(this).val();
    });
    $('#zone_ranges .range_sup td input:text:last').each(function(){
      range_sup = $(this).val();
    });

    $('#summary_range').html(html.replace('@s1', range_inf+' '+unit)
                                 .replace('@s2', range_sup+' '+unit)
                                 .replace('@s3', $('#range_behavior option:selected').text())
    );
    $('#summary_shipping_cost, #summary_range').show();
  }

	// Delivery zones
	$('#summary_zones').html('');
	$('.input_zone').each(function(){
		if ($(this).attr('checked'))
			$('#summary_zones').html($('#summary_zones').html() + '<li><strong>' + $(this).closest('tr').find('label').text() + '</strong></li>');
	});

	// Group restrictions
	$('#summary_groups').html('');
	$('input[name$="groupBox[]"]').each(function(){
		if ($(this).attr('checked'))
			$('#summary_groups').html($('#summary_groups').html() + '<li><strong>' + $(this).closest('tr').find('td:eq(2)').text() + '</strong></li>');
	});

	// shop restrictions
	$('#summary_shops').html('');
	$('.input_shop').each(function(){
		if ($(this).attr('checked'))
			$('#summary_shops').html($('#summary_shops').html() + '<li><strong>' + $(this).closest().text() + '</strong></li>');
	});
}

function validateStep(step) {
  let ok = true;

  $('.wizard_error').remove();

  // The ranges step is the only one we validate here.
  let rangesZone = undefined;
  $('#step-'+step+':visible #zone_ranges').each(function() {
    rangesZone = $(this);
  });

  if (rangesZone !== undefined && !$('#is_free_on').prop('checked')) {
    // Test individual values.
    rangesZone.find('.range_inf, .range_sup, .fees').
               find('input:text:enabled').each(function () {
      checkFieldIsNumeric($(this));
    });
    rangesZone.find('.has-error').each(function() {
      ok = false;
    });
    if (!ok) {
      displayError([invalid_value], step);
      return false;
    }

    // Test for at least one activated zone.
    ok = false;
    rangesZone.find('.fees input:checkbox:checked').each(function() {
      ok = true;
    });
    if (!ok) {
      displayError([select_at_least_one_zone], step);
      return false;
    }

    let nbrRanges = 0;
    rangesZone.find('.range_inf .input-group').each(function() {
      nbrRanges++;
    });

    // Test against negative and zero-sized ranges.
    for (let i = 0; i < nbrRanges; i++) {
      let rangeInf = rangesZone.find('.range_inf .input-group:eq('+i+') input:text');
      let rangeSup = rangesZone.find('.range_sup .input-group:eq('+i+') input:text');
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
    for (let i = 0; nbrRanges > 1 && i < nbrRanges - 1; i++) {
      let rangeSup = rangesZone.find('.range_sup .input-group:eq('+i+') input:text');
      let rangeInf = rangesZone.find('.range_inf .input-group:eq('+(i + 1)+') input:text');
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
                   $('#carrier_wizard #step-'+step+' form').serialize()+
                   '&step_number='+step+'&action=validate_step&ajax=1');

  return ok;
}

function ajaxRequest(step, data) {
  let success = false;

  $.ajax({
    type:"POST",
    url: validate_url,
    async: false,
    dataType: 'json',
    data: data,
    success: function(datas) {
      if (datas.has_error) {
        displayError(datas.errors, step);
      } else {
        success = true;
      }
    },
    error: function(XMLHttpRequest, textStatus, errorThrown) {
      jAlert("TECHNICAL ERROR: \n\nDetails:\nError thrown: "+XMLHttpRequest+"\nText status: "+textStatus);
    }
  });

  return success;
}

function displayError(errors, step_number) {
	$('.wizard_error').remove();
	str_error = '<div class="error wizard_error" style="display:none"><ul>';
	for (var error in errors)
	{
		$('input[name="'+error+'"]').closest('div.input-group').addClass('has-error');
		str_error += '<li>'+errors[error]+'</li>';
	}
	$('#step-'+step_number).prepend(str_error+'</ul></div>');
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
  $('#carrier_wizard').find('.step_container:visible').each(function() {
    let container = $(this);
    let height = 0;
    container.children().not('script, style').each(function() {
      height += $(this).outerHeight(true);
    });
    container.height(height + 5);
    container.parent().height(height + 20);
  });
}

function bind_inputs() {
  $('#zone_ranges .fees td input:checkbox').off('change').on('change', function () {
    let priceField = $(this).closest('tr').find('input:text');
    if ($(this).prop('checked')) {
      priceField.removeAttr('disabled');
      if (priceField.val().length === 0) {
        priceField.val((0).toFixed(6));
      }
    } else {
      priceField.attr('disabled', 'disabled');
      if (priceField.val() == 0) {
        priceField.val('');
      }
    }

		return false;
	});

	$(document.body).off('change', 'tr.fees_all td input').on('change', 'tr.fees_all td input', function() {
		index = $(this).closest('td').index();
		val = $(this).val();
    if (val.length && $.isNumeric(val)) {
      $(this).val('');
      $('tr.fees').each(function () {
        $(this).find('td:eq('+index+') input:text:enabled').val(val);
      });
    }

		return false;
	});

	$('input[name="shipping_method"]').off('click').on('click', function() {
		$.ajax({
			type:"POST",
			url : validate_url,
			async: false,
			dataType: 'html',
			data : 'id_carrier='+parseInt($('#id_carrier').val())+'&shipping_method='+parseInt($(this).val())+'&action=changeRanges&ajax=1',
			success : function(data) {
				$('#zone_ranges').replaceWith(data);
				displayRangeType();
				bind_inputs();
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				jAlert("TECHNICAL ERROR: \n\nDetails:\nError thrown: " + XMLHttpRequest + "\n" + 'Text status: ' + textStatus);
			}
		});
	});

	$('#zones_table td input[type=text]').off('change').on('change', function () {
    checkFieldIsNumeric($(this));
	});
}

function hideFees() {
  $('#zone_ranges .range_inf td input, \
     #zone_ranges .range_sup td input, \
     #zone_ranges .fees_all td input, \
     #zone_ranges .fees td input').attr('disabled', 'disabled');
}

function showFees() {
  $('#zone_ranges .range_inf td input, \
     #zone_ranges .range_sup td input, \
     #zone_ranges .fees_all td input').removeAttr('disabled');
  $('#zone_ranges .fees td input:checkbox').each(function () {
    let checkbox = $(this);
    checkbox.removeAttr('disabled');
    if (checkbox.prop('checked')) {
      checkbox.closest('tr').find('input:text').removeAttr('disabled');
    }
  });
}

function add_new_range() {
  let rangesZone = $('#zone_ranges');
  let lastSup = rangesZone.find('.range_sup td:last input:text').val();

  rangesZone.find('.range_inf, .range_sup, .fees_all, .fees').each(function() {
    let node = $(this).find('td:last');
    node.after(node.clone());
  });

  rangesZone.find('.range_inf td:last input:text').val(lastSup);
  rangesZone.find('.range_sup td:last input:text').val('');

  rangesZone.find('.range_inf, .range_sup, .fees')
            .find('td:last .form-control').each(function() {
    let control = $(this);
    let text = control.prop('name');
    text = text.substr(0, text.lastIndexOf('['))+'[]';
    control.prop('name', text);
  });

  // delete_range button may not exist in the previous range.
  rangesZone.find('.delete_range td:last').after('<td><a href="#" onclick="delete_range();" class="btn btn-default">'+labelDelete+'</a></td>');

	bind_inputs();
	rebuildTabindex();
	resizeWizard();
	return false;
}

function delete_range() {
  if (confirm(delete_range_confirm)) {
    let index = $(this).closest('td').index();
    $('#zone_ranges .range_sup td:eq('+index+'), \
       #zone_ranges .range_inf td:eq('+index+'), \
       #zone_ranges .fees_all td:eq('+index+'), \
       #zone_ranges .delete_range td:eq('+index+')').remove();
    $('#zone_ranges .fees').each(function () {
      $(this).find('td:eq('+index+')').remove();
    });
    rebuildTabindex();
  }

  $('.wizard_error').remove();
  resizeWizard();

  return false;
}

function checkFieldIsNumeric(element) {
  let value = element.val();
  if (value.length && $.isNumeric(value)) {
    element.closest('div.input-group').removeClass('has-error');
  } else {
    element.closest('div.input-group').addClass('has-error');
  }
}

function rebuildTabindex()
{
	i = 1;
	$('#zones_table tr').each(function ()
	{
		j = i;
		$(this).find('td').each(function ()
		{
			j = zones_nbr + j;
			if ($(this).index() >= 2 && $(this).find('div.input-group input'))
				$(this).find('div.input-group input').attr('tabindex', j);
		});
		i++;
	});
}

function checkAllZones(elt)
{
	if($(elt).is(':checked'))
	{
		$('.input_zone').attr('checked', 'checked');
    $('#zone_ranges .fees div.input-group input:text').removeAttr('disabled');
	}
	else
	{
		$('.input_zone').removeAttr('checked');
    $('#zone_ranges .fees div.input-group input:text').attr('disabled', 'disabled');
	}
}
