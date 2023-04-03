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
function validate_isName(s)
{
  return ! /www|http/i.test(s)
      && /^[^0-9!\[\]<>;?=+()@#"°{}_$%:\\\*\^]*$/.test(s);
}

function validate_isGenericName(s)
{
	var reg = /^[^<>={}]+$/;
	return reg.test(s);
}

function validate_isAddress(s)
{
	var reg = /^[^!<>?=+@{}_$%]+$/;
	return reg.test(s);
}

function validate_isPostCode(s, pattern, iso_code)
{
	if (typeof iso_code === 'undefined' || iso_code == '')
		iso_code = '[A-Z]{2}';
	if (typeof(pattern) == 'undefined' || pattern.length == 0)
		pattern = '[a-zA-Z 0-9-]+';
	else
	{
		var replacements = {
			' ': '(?:\ |)',
			'-': '(?:-|)',
			'N': '[0-9]',
			'L': '[a-zA-Z]',
			'C': iso_code
		};

		for (var new_value in replacements)
			pattern = pattern.split(new_value).join(replacements[new_value]);
	}
	var reg = new RegExp('^' + pattern + '$');
	return reg.test(s);
}

function validate_isCityName(s)
{
	var reg = /^[^!<>;?=+@#"°{}_$%]+$/;
	return reg.test(s);
}

function validate_isMessage(s)
{
	var reg = /^[^<>{}]+$/;
	return reg.test(s);
}

function validate_isPhoneNumber(s)
{
	var reg = /^[+0-9. ()-]+$/;
	return reg.test(s);
}

function validate_isDniLite(s)
{
  return /^[0-9A-Za-z-.]{1,16}$/.test(s);
}

function validate_isEmail(s)
{
	var sQtext = '[^\\x0d\\x22\\x5c\\x80-\\xff]';
	var sDtext = '[^\\x0d\\x5b-\\x5d\\x80-\\xff]';
	var sAtom = '[^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+';
	var sQuotedPair = '\\x5c[\\x00-\\x7f]';
	var sDomainLiteral = '\\x5b(' + sDtext + '|' + sQuotedPair + ')*\\x5d';
	var sQuotedString = '\\x22(' + sQtext + '|' + sQuotedPair + ')*\\x22';
	var sDomain_ref = sAtom;
	var sSubDomain = '(' + sDomain_ref + '|' + sDomainLiteral + ')';
	var sWord = '(' + sAtom + '|' + sQuotedString + ')';
	var sDomain = sSubDomain + '(\\x2e' + sSubDomain + ')*';
	var sLocalPart = sWord + '(\\x2e' + sWord + ')*';
	var sAddrSpec = sLocalPart + '\\x40' + sDomain; // complete RFC822 email address spec
	var sValidEmail = '^' + sAddrSpec + '$'; // as whole string

	var reValidEmail = new RegExp(sValidEmail);

	return reValidEmail.test(s);
}

function validate_isPasswd(s)
{
	return (s.length >= 5 && s.length < 255);
}

function validate_field(that)
{
	if ($(that).hasClass('is_required') || $(that).val().length)
	{
		if ($(that).attr('data-validate') == 'isPostCode')
		{
			var selector = '#id_country';
			if ($(that).attr('name') == 'postcode_invoice')
				selector += '_invoice';

			var id_country = $(selector + ' option:selected').val();

			if (typeof(countriesNeedZipCode[id_country]) != 'undefined' && typeof(countries[id_country]) != 'undefined')
				var result = window['validate_'+$(that).attr('data-validate')]($(that).val(), countriesNeedZipCode[id_country], countries[id_country]['iso_code']);
		}
		else if($(that).attr('data-validate'))
			var result = window['validate_' + $(that).attr('data-validate')]($(that).val());

		if (result)
			$(that).parent().removeClass('form-error').addClass('form-ok');
		else
			$(that).parent().addClass('form-error').removeClass('form-ok');
	}
}

$(document).on('focusout', 'input.validate, textarea.validate', function() {
	validate_field(this);
});
