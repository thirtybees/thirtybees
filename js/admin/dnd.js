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

/* global jQuery, $, window, showSuccessMessage, showErrorMessage */

function objToString(obj) {
  var str = '';
  $.each(obj, function (p) {
    if (obj.hasOwnProperty(p)) {
      str += p + '=' + obj[p] + '&';
    }
  });

  return str;
}

function initTableDnD(table) {
  if (typeof table === 'undefined') {
    table = 'table.tableDnD';
  }

  $(table).tableDnD({
    onDragStart: function (table, row) {
      originalOrder = $.tableDnD.serialize();
      reOrder = ':even';
      if (table.tBodies[0].rows[1] && $('#' + table.tBodies[0].rows[1].id).hasClass('alt_row')) {
        reOrder = ':odd';
      }
      $(table).find('#' + row.id).parent('tr').addClass('myDragClass');
    },
    dragHandle: 'dragHandle',
    onDragClass: 'myDragClass',
    onDrop: function (table, row) {
      if (originalOrder != $.tableDnD.serialize()) {
        var way = (originalOrder.indexOf(row.id) < $.tableDnD.serialize().indexOf(row.id)) ? 1 : 0;
        var ids = row.id.split('_');
        var tableDrag = table;
        var params = {};
        var tableId = table.id.replace('table-', '');
        if (tableId === 'cms_block_0' || tableId === 'cms_block_1') {
          params = {
            updatePositions: true,
            configure: 'blockcms'
          };
        } else if (tableId === 'category') {
          params = {
            action: 'updatePositions',
            id_category_parent: ids[1],
            id_category_to_move: ids[2],
            way: way
          };
        } else if (tableId === 'cms_category') {
          params = {
            action: 'updateCmsCategoriesPositions',
            id_cms_category_parent: ids[1],
            id_cms_category_to_move: ids[2],
            way: way
          };
        } else if (tableId === 'cms') {
          params = {
            action: 'updateCmsPositions',
            id_cms_category: ids[1],
            id_cms: ids[2],
            way: way
          };
        } else if (window.come_from === 'AdminModulesPositions') {
          params = {
            action: 'updatePositions',
            id_hook: ids[0],
            id_module: ids[1],
            way: way
          };
        } else if (tableId.indexOf('attribute') !== -1 && tableId !== 'attribute_group') {
          params = {
            action: 'updateAttributesPositions',
            id_attribute_group: ids[1],
            id_attribute: ids[2],
            way: way
          };
        } else if (tableId === 'attribute_group') {
          params = {
            action: 'updateGroupsPositions',
            id_attribute_group: ids[2],
            way: way
          };
        } else if (tableId === 'product') {
          params = {
            action: 'updatePositions',
            id_category: ids[1],
            id_product: ids[2],
            way: way
          };
        } else if (tableId.indexOf('module-') !== -1) {
          module = tableId.replace('module-', '');

          params = {
            updatePositions: true,
            configure: module
          };
        } else {
          params = {
            action: 'updatePositions',
            id: ids[2],
            way: way
          };
        }

        params.ajax = 1;
        params.page = parseInt($('input[name=page]').val(), 10);
        params.selected_pagination = parseInt($('input[name=selected_pagination]').val(), 10);

        var data = $.tableDnD.serialize().replace(/table-/g, '');
        if ((tableId === 'category') && (data.indexOf('_0&') !== -1)) {
          data += '&found_first=1';
        }
        $.ajax({
          type: 'POST',
          headers: { 'cache-control': 'no-cache' },
          url: window.currentIndex + '&token=' + window.token + '&rand=' + new Date().getTime(),
          data: data + '&' + objToString(params),
          success: function () {
            var nodragLines = $(tableDrag).find('tr:not(".nodrag")');
            var newPos;
            if (window.come_from === 'AdminModulesPositions') {
              nodragLines.each(function (i) {
                $(this).find('.positions').html(i + 1);
              });
            } else {
              var reg;
              if (tableId === 'product' || tableId.indexOf('attribute') !== -1 || tableId === 'attribute_group' || tableId === 'feature') {
                reg = /_[0-9][0-9]*$/g;
              } else {
                reg = /_[0-9]$/g;
              }

              nodragLines.each(function (i) {
                if (params.page > 1) {
                  newPos = i + ((params.page - 1) * params.selected_pagination);
                } else {
                  newPos = i;
                }

                $(this).attr('id', $(this).attr('id').replace(reg, '_' + newPos));
                $(this).find('.positions').text(newPos + 1);
              });
            }

            nodragLines.removeClass('odd');
            nodragLines.filter(':odd').addClass('odd');
            nodragLines.children('td.dragHandle').find('a').attr('disabled', false);

            if (typeof alternate !== 'undefined' && alternate) {
              nodragLines.children('td.dragHandle:first').find('a:odd').attr('disabled', true);
              nodragLines.children('td.dragHandle:last').find('a:even').attr('disabled', true);
            } else {
              nodragLines.children('td.dragHandle:first').find('a:even').attr('disabled', true);
              nodragLines.children('td.dragHandle:last').find('a:odd').attr('disabled', true);
            }
            showSuccessMessage(window.update_success_msg);
          }
        });
      }
    }
  });
}

$(document).ready(function () {
  initTableDnD();
});
