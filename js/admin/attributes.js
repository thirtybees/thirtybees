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

var storeUsedGroups = {};

function populate_attrs() {
  var attrGroup = getE('attribute_group');
  if (!attrGroup) {
    return;
  }
  var attrName = getE('attribute');
  var number = attrGroup.options.length ? attrGroup.options[attrGroup.selectedIndex].value : 0;

  if (!number) {
    attrName.options.length = 0;
    attrName.options[0] = new Option('---', 0);
    return;
  }

  var list = window.attrs[number];
  attrName.options.length = 0;
  if (typeof list !== 'undefined') {
    for (var i = 0; i < list.length; i += 2) {
      attrName.options[i / 2] = new Option(list[i + 1], list[i]);
    }
  }
}

function check_impact() {
  if (parseInt($('#attribute_price_impact').get(0).selectedIndex, 10) === 0) {
    $('#attribute_price, #attribute_priceTEReal, #attribute_priceTI')
    .val(displayPriceValue(0));
    $('#span_impact').hide();
  } else {
    $('#span_impact').show();
  }
}

function check_weight_impact() {
  if (parseInt($('#attribute_weight_impact').get(0).selectedIndex, 10) === 0) {
    $('#span_weight_impact').hide();
    $('#attribute_weight').val('0.00');
  } else {
    $('#span_weight_impact').show();
  }
}

function check_unit_impact() {
  if (parseInt($('#attribute_unit_impact').get(0).selectedIndex, 10) === 0) {
    $('#span_unit_impact').hide();
    $('#attribute_unity').val('0.00');
  } else {
    $('#span_unit_impact').show();
  }
}

function attr_selectall() {
  var elem = getE('product_att_list');
  if (elem) {
    var i;
    for (i = 0; i < elem.length; i += 1) {
      elem.options[i].selected = true;
    }
  }
}

function del_attr_multiple() {
  var attr = getE('attribute_group');

  if (!attr) {
    return;
  }
  var length = attr.length;
  var target;

  for (var i = 0; i < length; i += 1) {
    var elem = attr.options[i];
    if (elem.selected) {
      target = getE('table_' + elem.parentNode.getAttribute('name'));
      if (target && getE('result_' + elem.getAttribute('name'))) {
        target.removeChild(getE('result_' + elem.getAttribute('name')));
        if (!target.lastChild || !target.lastChild.id) {
          toggle(target.parentNode, false);
        }
      }
    }
  }
}

function create_attribute_row(id, idGroup, name, price, weight) {
  var html = '';
  html += '<tr id="result_' + id + '">';
  html += '<td><input type="hidden" value="' + id + '" name="options[' + idGroup + '][' + id + ']" />' + name + '</td>';
  html += '<td><input type="text" id="related_to_price_impact_ti_' + id + '" class="price_impact" value="' + displayPriceValue(price) + '" name="price_impact_' + id + '" onkeyup="calcPrice($(this), false)"></td>';
  html += '<td><input type="text" id="related_to_price_impact_' + id + '" class="price_impact_ti" value="" name="price_impact_ti_' + id + '" onkeyup="calcPrice($(this), true)"></td>';
  html += '<td><input type="text" value="' + weight + '" name="weight_impact_' + id + '"></td>';
  html += '</tr>';

  return html;
}

function add_attr_multiple() {
  var attr = getE('attribute_group');
  if (!attr) {
    return;
  }
  var length = attr.length;
  var target;
  var newElem;

  for (var i = 0; i < length; i += 1) {
    var elem = attr.options[i];
    if (elem.selected) {
      var name = elem.parentNode.getAttribute('name');
      target = $('#table_' + name);
      if (target && !getE('result_' + elem.getAttribute('name'))) {
        newElem = create_attribute_row(elem.getAttribute('name'), elem.parentNode.getAttribute('name'), elem.value, '0.00', '0.00');
        target.append(newElem);
        toggle(target.parent()[0], true);
      }
    }
  }
}

/**
 * Delete one or several attributes from the declination multilist
 */
function del_attr() {
  $('#product_att_list option:selected').each(function () {
    delete storeUsedGroups[$(this).attr('groupid')];
    $(this).remove();
  });
}

/**
 * Add an attribute from a group in the declination multilist
 */
function add_attr() {
  var attrGroup = $('#attribute_group option:selected');
  if (parseInt(attrGroup.val(), 10) === 0) {
    return jAlert(window.msg_combination_1);
  }

  var attrName = $('#attribute option:selected');
  if (parseInt(attrName.val(), 10) === 0) {
    return jAlert(window.msg_combination_2);
  }

  if (attrGroup.val() in storeUsedGroups) {
    return jAlert(window.msg_combination_3);
  }

  storeUsedGroups[attrGroup.val()] = true;
  $('<option></option>')
    .attr('value', attrName.val())
    .attr('groupid', attrGroup.val())
    .text(attrGroup.text() + ' : ' + attrName.text())
    .appendTo('#product_att_list');
}

function openCloseLayer(whichLayer) {
  var style;
  if (document.getElementById) {
    style = document.getElementById(whichLayer).style;
  } else if (document.all) {
    style = document.all[whichLayer].style;
  } else if (document.layers) {
    style = document.layers[whichLayer].style;
  }
  style.display = style.display === 'none' ? 'block' : 'none';
}

$(document).ready(function () {
  $('#product_form').submit(function () {
    attr_selectall();
    // If the new combination form is hidden, remove it so that empty fields are not submitted
    if ($('#add_new_combination').is(':hidden')) {
      $('#add_new_combination').remove();
    }
  });
});
