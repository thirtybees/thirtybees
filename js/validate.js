// noinspection JSUnusedGlobalSymbols

/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2024 thirty bees
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
 *  @copyright 2017-2024 thirty bees
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

function validate_isPostCode(s, $element)
{
	const validatePostCodePatern = (s, pattern, iso_code) => {
		if (typeof iso_code === 'undefined' || iso_code == '') {
			iso_code = '[A-Z]{2}';
		}

		if (typeof pattern === 'undefined' || pattern.length == 0) {
			pattern = '[a-zA-Z 0-9-]+';
		} else {
			const replacements = {
				' ': '(?:\ |)',
				'-': '(?:-|)',
				'N': '[0-9]',
				'L': '[a-zA-Z]',
				'C': iso_code
			};

			for (var new_value in replacements) {
				pattern = pattern.split(new_value).join(replacements[new_value]);
			}
		}
		const reg = new RegExp('^' + pattern + '$');
		return reg.test(s);
	}

	const getPostCodePattern = (countryId) => {
		if ((typeof window['countriesNeedZipCode'] !== 'undefined') &&
			(typeof window['countriesNeedZipCode'][countryId] !== 'undefined')
		) {
			return window['countriesNeedZipCode'][countryId];
		}
		return '';
	}

	const getIsoCode = (countryId) => {
		if ((typeof window['countries'] !== 'undefined') &&
			(typeof window['countries'][countryId] !== 'undefined') &&
			(typeof window['countries'][countryId]['iso_code'] !== 'undefined')
		) {
			return window['countries'][countryId]['iso_code'];
		}
		return '';
	}

	if ($element instanceof jQuery) {
		let selector = '#id_country';
		if ($element.attr('name') == 'postcode_invoice') {
			selector += '_invoice';
		}
		const countryId = $(selector + ' option:selected').val();
		return validatePostCodePatern(s, getPostCodePattern(countryId), getIsoCode(countryId));
	} else {
		return validatePostCodePatern.apply(this, arguments);
	}

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
	var sSubDomain = '(' + sAtom + '|' + sDomainLiteral + ')';
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
	const $field = $(that);
	const $form = $field.parent();
	const value = $field.val();

	if ($field.hasClass('is_required') || value.length) {

		const validatorName = $field.attr('data-validate');
		if (validatorName) {
			const validatorFunc = window['validate_' + validatorName];
			if (typeof validatorFunc === 'function') {
				if (validatorFunc(value, $field)) {
					$form.removeClass('form-error').addClass('form-ok');
				} else {
					$form.addClass('form-error').removeClass('form-ok');
				}
			} else {
				console.warn("Validator function 'validate_"+validatorName+"' does not exist");
			}
		}
	}
}

$(document).on('focusout', 'input.validate, textarea.validate', function() {
	validate_field(this);
});
