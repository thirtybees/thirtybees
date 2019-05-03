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

function addTaxes(priceWithTaxes) {
  var taxes = getTaxes();

  if (taxes.computation_method === 0) {
    priceWithTaxes *= 1 + taxes.rates[0] / 100;
  } else if (taxes.computation_method === 1) {
    var rate = 0;
    $.each(taxes.rates, function (i) {
      rate += taxes.rates[i];
    });

    priceWithTaxes *= 1 + rate / 100;
  } else if (taxes.computation_method === 2) {
    $.each(taxes.rates, function (i) {
      priceWithTaxes = parseFloat(
        (priceWithTaxes * (1 + taxes.rates[i] / 100))
        .toFixed(priceDatabasePrecision)
      );
    });
  }

  return parseFloat(priceWithTaxes.toFixed(priceDatabasePrecision));
}

function removeTaxes(priceWithoutTaxes) {
  var taxes = getTaxes();

  if (taxes.computation_method === 0) {
    priceWithoutTaxes /= 1 + taxes.rates[0] / 100;
  }
  else if (taxes.computation_method === 1) {
    var rate = 0;
    $.each(taxes.rates, function (i) {
      rate += taxes.rates[i];
    });

    priceWithoutTaxes /= 1 + rate / 100;
  } else if (taxes.computation_method === 2) {
    $.each(taxes.rates, function (i) {
      priceWithoutTaxes = parseFloat(
        (priceWithoutTaxes / (1 + taxes.rates[i] / 100))
        .toFixed(priceDatabasePrecision)
      );
    });
  }

  return parseFloat(priceWithoutTaxes.toFixed(priceDatabasePrecision));
}

function getEcotaxTaxIncluded() {
  return parseFloat(
    (window.ecotax_tax_excl * (1 + ecotaxTaxRate))
    .toFixed(priceDatabasePrecision)
  );
}

function getEcotaxTaxExcluded() {
  return parseFloat(
    parseFloat(window.ecotax_tax_excl).toFixed(priceDatabasePrecision)
  );
}

function formatPrice(price) {
  console.log('Deprecated with v1.1.0. Use displayPriceValue() directly.');

  return displayPriceValue();
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
  var priceTE = parseFloat(document.getElementById('priceTEReal').value);
  var newPrice = addTaxes(priceTE);

  document.getElementById('priceTI').value =
    displayPriceValue(newPrice + getEcotaxTaxIncluded());
  document.getElementById('finalPrice').innerHTML =
    displayPrice(newPrice);
  document.getElementById('finalPriceWithoutTax').innerHTML =
    displayPrice(priceTE);
}

function calcPriceTE() {
  var priceTI = parseFloat(document.getElementById('priceTI').value);
  var newPrice = removeTaxes(priceTI);

  document.getElementById('priceTE').value =
    displayPriceValue(newPrice - getEcotaxTaxIncluded());
  document.getElementById('priceTEReal').value =
    displayPriceValue(newPrice);
  document.getElementById('finalPrice').innerHTML =
    displayPrice(priceTI);
  document.getElementById('finalPriceWithoutTax').innerHTML =
    displayPrice(newPrice);
}

function calcImpactPriceTI() {
  var priceTE = parseFloat(document.getElementById('attribute_priceTEReal').value);
  var newPrice = addTaxes(priceTE);

  document.getElementById('attribute_priceTI').value =
    displayPriceValue(newPrice);

  $('#attribute_new_total_price').html(displayPrice(
    parseFloat($('#attribute_priceTI').val())
    * parseInt($('#attribute_price_impact').val())
    + parseFloat($('#finalPrice').html())
  ));
}

function calcImpactPriceTE() {
  var priceTI = parseFloat(document.getElementById('attribute_priceTI').value);
  var newPrice = removeTaxes(priceTI);

  document.getElementById('attribute_price').value =
    displayPriceValue(newPrice);
  document.getElementById('attribute_priceTEReal').value =
    displayPriceValue(newPrice);

  $('#attribute_new_total_price').html(displayPrice(
    parseFloat($('#attribute_priceTI').val())
    * parseInt($('#attribute_price_impact').val())
    + parseFloat($('#finalPrice').html())
  ));
}

function calcReduction() {
  console.log('Deprecated with v1.1.0. Nowhere in use.');

  if (parseFloat($('#reduction_price').val()) > 0) {
    reductionPrice();
  } else if (parseFloat($('#reduction_percent').val()) > 0) {
    reductionPercent();
  }
}

function reductionPrice() {
  console.log('Deprecated with v1.1.0. Nowhere in use.');

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

  newprice.innerHTML = displayPrice(curPrice + getEcotaxTaxIncluded());
  newpriceWithoutTax.innerHTML = displayPrice(
    priceWhithoutTaxes.value - removeTaxes(rprice.value)
  );
}

function reductionPercent() {
  console.log('Deprecated with v1.1.0. Nowhere in use.');

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
    curPrice =  parseFloat(
      (price.value * (1 - rpercent.value / 100))
      .toFixed(priceDatabasePrecision)
    );
  }

  newprice.innerHTML = displayPrice(curPrice + getEcotaxTaxIncluded());
  newpriceWithoutTax.innerHTML = displayPrice(removeTaxes(curPrice));
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
  console.log('Deprecated with v1.1.0. Use toFixed() instead.');

  if (typeof decimals === 'undefined') {
    decimals = priceDatabasePrecision;
  }
  source = source.toString();
  var pos = source.indexOf('.');
  return parseFloat(source.substr(0, pos + decimals + 1));
}

function unitPriceWithTax(type) {
  var newPrice = parseFloat(document.getElementById(type + '_price').value);
  newPrice = addTaxes(newPrice);

  $('#' + type + '_price_with_tax').html(displayPrice(newPrice));
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
