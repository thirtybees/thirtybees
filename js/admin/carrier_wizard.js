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

function onFinishCallback(obj, context)
{
	$('.wizard_error').remove();
	$.ajax({
		type:"POST",
		url : validate_url,
		async: false,
		dataType: 'json',
		data : $('#carrier_wizard .stepContainer .content form').serialize() + '&action=finish_step&ajax=1&step_number='+context.fromStep,
		success : function(data) {
			if (data.has_error)
			{
				displayError(data.errors, context.fromStep);
				resizeWizard();
			}
			else
				window.location.href = carrierlist_url;
		},
		error: function(XMLHttpRequest, textStatus, errorThrown) {
			jAlert("TECHNICAL ERROR: \n\nDetails:\nError thrown: " + XMLHttpRequest + "\n" + 'Text status: ' + textStatus);
		}
	});
}

function onLeaveStepCallback(obj, context)
{
	if (context.toStep == nbr_steps)
		displaySummary();

	return validateSteps(context.fromStep, context.toStep); // return false to stay on step and true to continue navigation
}

function displaySummary()
{
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
    var tmp,
        delay_text = $('#delay_' + id_lang).val();

    // Assign text in default language if empty
    if (!delay_text)
        delay_text = $('#delay_' + id_default_lang).val();

	// Carrier name
	$('#summary_name').text($('#name').val());

	// Delay and pricing
	tmp = summary_translation_meta_informations.replace('@s2', '<strong>' + delay_text + '</strong>');
	if ($('#is_free_on').attr('checked'))
		tmp = tmp.replace('@s1', summary_translation_free);
	else
		tmp = tmp.replace('@s1', summary_translation_paid);
	$('#summary_meta_informations').html(tmp);

	// Tax and calculation mode for the shipping cost
	tmp = summary_translation_shipping_cost.replace('@s2', '<strong>' + $('#id_tax_rules_group option:selected').text() + '</strong>');

		if ($('#billing_price').attr('checked'))
			tmp = tmp.replace('@s1', summary_translation_price);
		else if ($('#billing_weight').attr('checked'))
			tmp = tmp.replace('@s1', summary_translation_weight);
		else
			tmp = tmp.replace('@s1', '<strong>' + summary_translation_undefined + '</strong>');

	$('#summary_shipping_cost').text(tmp);

	// Weight or price ranges
	$('#summary_range').text(summary_translation_range+' '+summary_translation_range_limit);

	if ($('input[name="shipping_method"]:checked').val() == 1)
		unit = PS_WEIGHT_UNIT;
	else
		unit = currency_sign;

	var range_inf = summary_translation_undefined;
	var range_sup = summary_translation_undefined;

	$('tr.range_inf td input').each(function()
	{
		if (!isNaN(parseFloat($(this).val())) && (range_inf == summary_translation_undefined || parseFloat(range_inf) > parseFloat($(this).val())))
			range_inf = $(this).val();
	});

	$('tr.range_sup td input').each(function(){

		if (!isNaN(parseFloat($(this).val())) && (range_sup == summary_translation_undefined || parseFloat(range_sup) < parseFloat($(this).val())))
			range_sup = $(this).val();
	});

	$('#summary_range').html(
		$('#summary_range').html()
		.replace('@s1', '<strong>' + range_inf +' '+ unit + '</strong>')
		.replace('@s2', '<strong>' + range_sup +' '+ unit + '</strong>')
		.replace('@s3', '<strong>' + $('#range_behavior option:selected').text().toLowerCase() + '</strong>')
	);
	if ($('#is_free_on').attr('checked'))
		$('span.is_free').hide();
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

function validateSteps(fromStep, toStep)
{
	var is_ok = true;
	if ((multistore_enable && fromStep == 3) || (!multistore_enable && fromStep == 2))
	{
		if (toStep > fromStep && !$('#is_free_on').attr('checked'))
		{
			is_ok = false;
			$('.input_zone').each(function () {
				if ($(this).prop('checked'))
					is_ok = true;
			});

			if (!is_ok)
			{
				displayError([select_at_least_one_zone], fromStep);
				return;
			}
		}

		if (toStep > fromStep && !$('#is_free_on').attr('checked') && !validateRange(2))
			is_ok = false;
	}

	$('.wizard_error').remove();

	if (is_ok && isOverlapping())
		is_ok = false;

	if (is_ok)
	{
		form = $('#carrier_wizard #step-'+fromStep+' form');
		$.ajax({
			type:"POST",
			url : validate_url,
			async: false,
			dataType: 'json',
			data : form.serialize()+'&step_number='+fromStep+'&action=validate_step&ajax=1',
			success : function(datas)
			{
				if (datas.has_error)
				{
					is_ok = false;
					$('div.input-group input').focus(function () {
						$(this).closest('div.input-group').removeClass('has-error');
					});
					displayError(datas.errors, fromStep);
					resizeWizard();
				}
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				jAlert("TECHNICAL ERROR: \n\nDetails:\nError thrown: " + XMLHttpRequest + "\n" + 'Text status: ' + textStatus);
			}
		});
	}
	return is_ok;
}

function displayError(errors, step_number)
{
	$('#carrier_wizard .actionBar a.btn').removeClass('disabled');
	$('.wizard_error').remove();
	str_error = '<div class="error wizard_error" style="display:none"><ul>';
	for (var error in errors)
	{
		$('#carrier_wizard .actionBar a.btn').addClass('disabled');
		$('input[name="'+error+'"]').closest('div.input-group').addClass('has-error');
		str_error += '<li>'+errors[error]+'</li>';
	}
	$('#step-'+step_number).prepend(str_error+'</ul></div>');
	$('.wizard_error').fadeIn('fast');
	bind_inputs();
}

function resizeWizard()
{
	resizeInterval = setInterval(function (){$("#carrier_wizard").smartWizard('fixHeight'); clearInterval(resizeInterval)}, 100);
}

function bind_inputs()
{
	$('input').focus(function () {
		$(this).closest('div.input-group').removeClass('has-error');
		$('#carrier_wizard .actionBar a.btn').not('.buttonFinish').removeClass('disabled');
		$('.wizard_error').fadeOut('fast', function () { $(this).remove()});
	});

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

	$('tr.range_sup td input:text, tr.range_inf td input:text').focus(function () {
		$(this).closest('div.input-group').removeClass('has-error');
	});

	$('tr.fees_all td input:text').keypress(function (evn) {
		index = $(this).parent('td').index();
		if (evn.keyCode == 13)
			return false;
	});

	$(document.body).off('change', 'tr.fees_all td input').on('change', 'tr.fees_all td input', function() {
		index = $(this).closest('td').index();
		val = $(this).val();
		$(this).val('');
		$('tr.fees').each(function () {
			$(this).find('td:eq('+index+') input:text:enabled').val(val);
		});

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
		checkAllFieldIsNumeric();
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

function validateRange(index)
{
	$('#carrier_wizard .actionBar a.btn').removeClass('disabled');
	$('.wizard_error').remove();
	//reset error css
	$('tr.range_sup td input:text').closest('div.input-group').removeClass('has-error');
	$('tr.range_inf td input:text').closest('div.input-group').removeClass('has-error');

	var is_valid = true;
	range_sup = parseFloat($('tr.range_sup td:eq('+index+')').find('div.input-group input:text').val().trim());
	range_inf = parseFloat($('tr.range_inf td:eq('+index+')').find('div.input-group input:text').val().trim());

	if (isNaN(range_sup) || range_sup.length === 0)
	{
		$('tr.range_sup td:eq('+index+')').find('div.input-group input:text').closest('div.input-group').addClass('has-error');
		is_valid = false;
		displayError([invalid_range], $("#carrier_wizard").smartWizard('currentStep'));
	}
	else if (is_valid && (isNaN(range_inf) || range_inf.length === 0))
	{
		$('tr.range_inf td:eq('+index+')').find('div.input-group input:text').closest('div.input-group').addClass('has-error');
		is_valid = false;
		displayError([invalid_range], $("#carrier_wizard").smartWizard('currentStep'));
	}
	else if (is_valid && range_inf >= range_sup)
	{
		$('tr.range_sup td:eq('+index+')').find('div.input-group input:text').closest('div.input-group').addClass('has-error');
		$('tr.range_inf td:eq('+index+')').find('div.input-group input:text').closest('div.input-group').addClass('has-error');
		is_valid = false;
		displayError([invalid_range], $("#carrier_wizard").smartWizard('currentStep'));
	}

	return is_valid;
}

function enableGlobalFees(index) {
  $('#zone_ranges .fees_all td:eq('+index+')').find('div.input-group input').removeAttr('disabled');
}

function disabledGlobalFees(index) {
  $('#zone_ranges .fees_all td:eq('+index+')').find('div.input-group input').attr('disabled', 'disabled');
}

function add_new_range()
{
	if (!$('tr.fees_all td:last').hasClass('validated'))
	{
		alert(need_to_validate);
		return false;
	}

	last_sup_val = $('tr.range_sup td:last input').val();
	//add new rand sup input
	$('tr.range_sup td:last').after('<td class="range_data"><div class="input-group fixed-width-md"><span class="input-group-addon weight_unit" style="display: none;">'+PS_WEIGHT_UNIT+'</span><span class="input-group-addon price_unit" style="display: none;">'+currency_sign+'</span><input class="form-control" name="range_sup[]" type="text" autocomplete="off" /></div></td>');
	//add new rand inf input
	$('tr.range_inf td:last').after('<td class="border_bottom"><div class="input-group fixed-width-md"><span class="input-group-addon weight_unit" style="display: none;">'+PS_WEIGHT_UNIT+'</span><span class="input-group-addon price_unit" style="display: none;">'+currency_sign+'</span><input class="form-control" name="range_inf[]" type="text" value="'+last_sup_val+'" autocomplete="off" /></div></td>');
	$('tr.fees_all td:last').after('<td class="border_top border_bottom"><div class="input-group fixed-width-md"><span class="input-group-addon currency_sign" style="display:none" >'+currency_sign+'</span><input class="form-control" style="display:none" type="text" /></div></td>');

	$('tr.fees').each(function () {
		$(this).find('td:last').after('<td><div class="input-group fixed-width-md"><span class="input-group-addon currency_sign">'+currency_sign+'</span><input class="form-control" disabled="disabled" name="fees['+$(this).data('zoneid')+'][]" type="text" /></div></td>');
	});

  $('#zone_ranges .delete_range td:last').after('<td><a href="#" onclick="delete_range();" class="btn btn-default">'+labelDelete+'</a></td>');

	bind_inputs();
	rebuildTabindex();
	displayRangeType();
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
  return false;
}

function checkAllFieldIsNumeric()
{
	$('#carrier_wizard .actionBar a.btn').removeClass('disabled');
	$('#zones_table td input[type=text]').each(function () {
		if (!$.isNumeric($(this).val()) && $(this).val() != '')
			$(this).closest('div.input-group').addClass('has-error');
	});
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

function repositionRange(current_index, new_index)
{
	$('tr.range_sup, tr.range_inf, tr.fees_all, tr.fees, tr.delete_range ').each(function () {
		$(this).find('td:eq('+current_index+')').each(function () {
			$(this).closest('tr').find('td:eq('+new_index+')').after(this.outerHTML);
			$(this).remove();
		});
	});
}

function checkRangeContinuity(reordering)
{
	reordering = typeof reordering !== 'undefined' ? reordering : false;
	res = true;

	$('tr.range_sup td').not('.range_type, .range_sign').each(function ()
	{
		index = $(this).index();
		if (index > 2)
		{
			range_sup = parseFloat($('tr.range_sup td:eq('+index+')').find('div.input-group input:text').val().trim());
			range_inf = parseFloat($('tr.range_inf td:eq('+index+')').find('div.input-group input:text').val().trim());
			prev_index = index-1;
			prev_range_sup = parseFloat($('tr.range_sup td:eq('+prev_index+')').find('div.input-group input:text').val().trim());
			prev_range_inf = parseFloat($('tr.range_inf td:eq('+prev_index+')').find('div.input-group input:text').val().trim());

			if (range_inf < prev_range_inf || range_sup < prev_range_sup)
			{
				res = false;
				if (reordering)
				{
					new_position = getCorrectRangePosistion(range_inf, range_sup);
					if (new_position)
						repositionRange(index, new_position);
				}
			}
		}
	});
	if (res)
		$('.ranges_not_follow').fadeOut();
	else
		$('.ranges_not_follow').fadeIn();
	resizeWizard();
}

function getCorrectRangePosistion(current_inf, current_sup)
{
	new_position = false;
	$('tr.range_sup td').not('.range_type, .range_sign').each(function ()
	{
		index = $(this).index();
		range_sup = parseFloat($('tr.range_sup td:eq('+index+')').find('div.input-group input:text').val().trim());
		next_range_inf = 0
		if ($('tr.range_inf td:eq('+index+1+')').length)
			next_range_inf = parseFloat($('tr.range_inf td:eq('+index+1+')').find('div.input-group input:text').val().trim());
		if (current_inf >= range_sup && current_sup < next_range_inf)
			new_position = index;
	});
	return new_position;
}

function isOverlapping()
{
	var is_valid = false;
	$('#carrier_wizard .actionBar a.btn').removeClass('disabled');
	$('tr.range_sup td').not('.range_type, .range_sign').each( function ()
	{
		index = $(this).index();
		current_inf = parseFloat($('.range_inf td:eq('+index+') input').val());
		current_sup = parseFloat($('.range_sup td:eq('+index+') input').val());

		$('tr.range_sup td').not('.range_type, .range_sign').each( function ()
		{
			testing_index = $(this).index();

			if (testing_index != index) //do not test himself
			{
				testing_inf = parseFloat($('.range_inf td:eq('+testing_index+') input').val());
				testing_sup = parseFloat($('.range_sup td:eq('+testing_index+') input').val());

				if ((current_inf >= testing_inf && current_inf < testing_sup) || (current_sup > testing_inf && current_sup < testing_sup))
				{
					$('tr.range_sup td:eq('+testing_index+') div.input-group, tr.range_inf td:eq('+testing_index+') div.input-group').addClass('has-error');
					displayError([overlapping_range], $("#carrier_wizard").smartWizard('currentStep'));
					is_valid = true;
				}
			}
		});
	});
	return is_valid;
}

function checkAllZones(elt)
{
	if($(elt).is(':checked'))
	{
		$('.input_zone').attr('checked', 'checked');
    $('#zone_ranges .fees_all div.input-group input:text, \
       #zone_ranges .fees div.input-group input:text').removeAttr('disabled');
	}
	else
	{
		$('.input_zone').removeAttr('checked');
    $('#zone_ranges .fees_all div.input-group input:text, \
       #zone_ranges .fees div.input-group input:text').attr('disabled', 'disabled');
	}
}
