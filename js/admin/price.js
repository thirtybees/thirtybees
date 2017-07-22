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

/* global jQuery, $, window, showSuccessMessage, showErrorMessage, ps_round, priceDisplayPrecision */

function getTax() {
  if (typeof window.noTax !== 'undefined' && window.noTax || window.taxesArray === 'undefined') {
    return 0;
  }

  var selectedTax = document.getElementById('id_tax_rules_group');
  var taxId = selectedTax.options[selectedTax.selectedIndex].value;
  return window.taxesArray[taxId].rates[0];
}

function getTaxes() {
  if (typeof window.noTax !== 'undefined' && window.noTax || window.taxesArray === 'undefined') {
    return 0;
  }

  var selectedTax = document.getElementById('id_tax_rules_group');
  var taxId = selectedTax.options[selectedTax.selectedIndex].value;

  return window.taxesArray[taxId];
}

function addTaxes(price) {
  var taxes = getTaxes();
  var priceWithTaxes = price;
  if (taxes.computation_method === 0) {
    $.each(taxes.rates, function (i) {
      priceWithTaxes *= (1 + (taxes.rates[i] / 100));

      return false;
    });
  } else if (taxes.computation_method === 1) {
    var rate = 0;
    $.each(taxes.rates, function (i) {
      rate += taxes.rates[i];
    });

    priceWithTaxes *= (1 + (rate / 100));
  } else if (taxes.computation_method === 2) {
    $.each(taxes.rates, function (i) {
      priceWithTaxes *= (1 + (taxes.rates[i] / 100));
    });
  }

  return priceWithTaxes;
}

function removeTaxes(price) {
  var taxes = getTaxes();
  var priceWithoutTaxes = price;
  if (taxes.computation_method === 0) {
    $.each(taxes.rates, function (i) {
      priceWithoutTaxes /= (1 + (taxes.rates[i] / 100));

      return false;
    });
  }
  else if (taxes.computation_method === 1) {
    var rate = 0;
    $.each(taxes.rates, function (i) {
      rate += taxes.rates[i];
    });

    priceWithoutTaxes /= (1 + (rate / 100));
  } else if (taxes.computation_method === 2) {
    $.each(taxes.rates, function (i) {
      priceWithoutTaxes /= (1 + (taxes.rates[i] / 100));
    });
  }

  return priceWithoutTaxes;
}

function getEcotaxTaxIncluded() {
  return ps_round(window.ecotax_tax_excl * (1 + ecotaxTaxRate), 2);
}

function getEcotaxTaxExcluded() {
  return window.ecotax_tax_excl;
}

function formatPrice(price) {
  var fixedToSix = (Math.round(price * 1000000) / 1000000);
  return (Math.round(fixedToSix) === fixedToSix + 0.000001 ? fixedToSix + 0.000001 : fixedToSix);
}

function calcPrice() {
  var priceType = $('#priceType').val();
  if (priceType === 'TE') {
    calcPriceTI();
  } else {
    calcPriceTE();
  }
}

function calcPriceTI() {
  var priceTE = parseFloat(document.getElementById('priceTEReal').value.replace(/,/g, '.'));
  var newPrice = addTaxes(priceTE);

  document.getElementById('priceTI').value = (isNaN(newPrice) || newPrice < 0) ? '' :
    ps_round(newPrice, priceDisplayPrecision);
  document.getElementById('finalPrice').innerHTML = (isNaN(newPrice) || newPrice < 0) ? '' :
    ps_round(newPrice, priceDisplayPrecision).toFixed(priceDisplayPrecision);
  document.getElementById('finalPriceWithoutTax').innerHTML = (isNaN(priceTE) || priceTE < 0) ? '' :
    (ps_round(priceTE, 6)).toFixed(6);
  calcReduction();

  if (isNaN(parseFloat($('#priceTI').val()))) {
    $('#priceTI').val('');
    $('#finalPrice').html('');
  }
  else {
    $('#priceTI').val((parseFloat($('#priceTI').val()) + getEcotaxTaxIncluded()).toFixed(priceDisplayPrecision));
    $('#finalPrice').html(parseFloat($('#priceTI').val()).toFixed(priceDisplayPrecision));
  }
}

function calcPriceTE() {
  ecotax_tax_excl = $('#ecotax').val() / (1 + ecotaxTaxRate);
  var priceTI = parseFloat(document.getElementById('priceTI').value.replace(/,/g, '.'));
  var newPrice = removeTaxes(ps_round(priceTI - getEcotaxTaxIncluded(), priceDisplayPrecision));
  document.getElementById('priceTE').value = (isNaN(newPrice) || newPrice < 0) ? '' :
    ps_round(newPrice, 6).toFixed(6);
  document.getElementById('priceTEReal').value = (isNaN(newPrice) || newPrice < 0) ? 0 : ps_round(newPrice, 9);
  document.getElementById('finalPrice').innerHTML = (isNaN(newPrice) || newPrice < 0) ? '' :
    ps_round(priceTI, priceDisplayPrecision).toFixed(priceDisplayPrecision);
  document.getElementById('finalPriceWithoutTax').innerHTML = (isNaN(newPrice) || newPrice < 0) ? '' :
    (ps_round(newPrice, 6)).toFixed(6);
  calcReduction();
}

function calcImpactPriceTI() {
  var priceTE = parseFloat(document.getElementById('attribute_priceTEReal').value.replace(/,/g, '.'));
  var newPrice = addTaxes(priceTE);
  $('#attribute_priceTI').val((isNaN(newPrice) || newPrice < 0) ? '' : ps_round(newPrice, priceDisplayPrecision).toFixed(priceDisplayPrecision));
  var total = ps_round((parseFloat($('#attribute_priceTI').val()) * parseInt($('#attribute_price_impact').val()) + parseFloat($('#finalPrice').html())), priceDisplayPrecision);
  if (isNaN(total) || total < 0) {
    $('#attribute_new_total_price').html('0.00');
  } else {
    $('#attribute_new_total_price').html(total);
  }
}

function calcImpactPriceTE() {
  var priceTI = parseFloat(document.getElementById('attribute_priceTI').value.replace(/,/g, '.'));
  priceTI = (isNaN(priceTI)) ? 0 : ps_round(priceTI);
  var newPrice = removeTaxes(ps_round(priceTI, priceDisplayPrecision));
  $('#attribute_price').val((isNaN(newPrice) || newPrice < 0) ? '' : ps_round(newPrice, 6).toFixed(6));
  $('#attribute_priceTEReal').val((isNaN(newPrice) || newPrice < 0) ? 0 : ps_round(newPrice, 9));
  var total = ps_round((parseFloat($('#attribute_priceTI').val()) * parseInt($('#attribute_price_impact').val()) + parseFloat($('#finalPrice').html())), priceDisplayPrecision);
  if (isNaN(total) || total < 0) {
    $('#attribute_new_total_price').html('0.00');
  } else {
    $('#attribute_new_total_price').html(total);
  }
}

function calcReduction() {
  if (parseFloat($('#reduction_price').val()) > 0) {
    reductionPrice();
  } else if (parseFloat($('#reduction_percent').val()) > 0) {
    reductionPercent();
  }
}

function reductionPrice() {
  var price = document.getElementById('priceTI');
  var priceWhithoutTaxes = document.getElementById('priceTE');
  var newprice = document.getElementById('finalPrice');
  var newpriceWithoutTax = document.getElementById('finalPriceWithoutTax');
  var curPrice = price.value;

  document.getElementById('reduction_percent').value = 0;
  if (isInReductionPeriod()) {
    var rprice = document.getElementById('reduction_price');
    if (parseFloat(curPrice) <= parseFloat(rprice.value)) {
      rprice.value = curPrice;
    }
    if (parseFloat(rprice.value) < 0 || isNaN(parseFloat(curPrice))) {
      rprice.value = 0;
    }
    curPrice = curPrice - rprice.value;
  }

  newprice.innerHTML = (ps_round(parseFloat(curPrice), priceDisplayPrecision) + getEcotaxTaxIncluded()).toFixed(priceDisplayPrecision);
  var rpriceWithoutTaxes = ps_round(removeTaxes(rprice.value), priceDisplayPrecision);
  newpriceWithoutTax.innerHTML = ps_round(priceWhithoutTaxes.value - rpriceWithoutTaxes, priceDisplayPrecision).toFixed(priceDisplayPrecision);
}

function reductionPercent() {
  var price = document.getElementById('priceTI');
  var newprice = document.getElementById('finalPrice');
  var newpriceWithoutTax = document.getElementById('finalPriceWithoutTax');
  var curPrice = price.value;

  document.getElementById('reduction_price').value = 0;
  if (isInReductionPeriod()) {
    newprice = document.getElementById('finalPrice');
    var rpercent = document.getElementById('reduction_percent');

    if (parseFloat(rpercent.value) >= 100) {
      rpercent.value = 100;
    }
    if (parseFloat(rpercent.value) < 0) {
      rpercent.value = 0;
    }
    curPrice = price.value * (1 - (rpercent.value / 100));
  }

  newprice.innerHTML = (ps_round(parseFloat(curPrice), priceDisplayPrecision) + getEcotaxTaxIncluded()).toFixed(priceDisplayPrecision);
  newpriceWithoutTax.innerHTML = ps_round(parseFloat(removeTaxes(ps_round(curPrice, priceDisplayPrecision))), priceDisplayPrecision).toFixed(priceDisplayPrecision);
}

function isInReductionPeriod() {
  var start = document.getElementById('reduction_from').value;
  var end = document.getElementById('reduction_to').value;

  if (start === end && !start && start !== '0000-00-00 00:00:00') {
    return true;
  }

  var sdate = new Date(start.replace(/-/g, '/'));
  var edate = new Date(end.replace(/-/g, '/'));
  var today = new Date();

  return (sdate <= today && edate >= today);
}

function decimalTruncate(source, decimals) {
  if (typeof decimals === 'undefined') {
    decimals = 6;
  }
  source = source.toString();
  var pos = source.indexOf('.');
  return parseFloat(source.substr(0, pos + decimals + 1));
}

function unitPriceWithTax(type) {
  var priceWithTax = parseFloat(document.getElementById(type + '_price').value.replace(/,/g, '.'));
  var newPrice = addTaxes(priceWithTax);
  $('#' + type + '_price_with_tax').html((isNaN(newPrice) || newPrice < 0) ? '0.00' : ps_round(newPrice, priceDisplayPrecision).toFixed(priceDisplayPrecision));
}

function unitySecond() {
  $('#unity_second').html($('#unity').val());
  if ($('#unity').get(0).value.length > 0) {
    $('#unity_third').html($('#unity').val());
    $('#tr_unit_impact').show();
  }
  else {
    $('#tr_unit_impact').hide();
  }
}

function changeCurrencySpecificPrice(index) {
  var id_currency = $('#spm_currency_' + index).val();
  if (id_currency > 0) {
    $('#sp_reduction_type option[value="amount"]').text($('#spm_currency_' + index + ' option[value= ' + id_currency + ']').text());
  } else if (typeof currencyName !== 'undefined') {
    $('#sp_reduction_type option[value="amount"]').text(currencyName);
  }

  if (currencies[id_currency]['format'] === 2 || currencies[id_currency]['format'] === 4) {
    $('#spm_currency_sign_pre_' + index).html('');
    $('#spm_currency_sign_post_' + index).html(' ' + currencies[id_currency]['sign']);
  }
  else if (currencies[id_currency]['format'] === 1 || currencies[id_currency]['format'] === 3) {
    $('#spm_currency_sign_post_' + index).html('');
    $('#spm_currency_sign_pre_' + index).html(currencies[id_currency]['sign'] + ' ');
  }
}
