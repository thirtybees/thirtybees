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
 * @author    thirty bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2024 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

$(function () {
  function getCheckedGroups() {
    return $('.groupBox:checked').map(function () {
      var $group = $(this);
      var name = $.trim($group.closest('tr').find('label').first().text());

      return {
        id_group: $group.val(),
        name: name || $group.val()
      };
    }).get();
  }

  function syncDefaultGroupSelect() {
    var $select = $('#id_default_group');
    if (!$select.length) {
      return;
    }

    var currentValue = $select.val();
    var groups = getCheckedGroups();

    $select.empty();
    $.each(groups, function (i, group) {
      $('<option/>').val(group.id_group).text(group.name).appendTo($select);
    });

    if (currentValue && $select.find('option[value="' + currentValue + '"]').length) {
      $select.val(currentValue);
    } else if (groups.length) {
      $select.prop('selectedIndex', 0);
    }
  }

  $('.groupBox').on('change', syncDefaultGroupSelect);

  // checkDelBoxes() updates checkboxes directly and does not trigger change events.
  $(document).on('click', 'input[type="checkbox"][onclick*="checkDelBoxes"][onclick*="groupBox"]', function () {
    setTimeout(syncDefaultGroupSelect, 0);
  });

  syncDefaultGroupSelect();
});
