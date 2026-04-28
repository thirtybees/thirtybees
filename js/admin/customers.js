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
  var messages = {
    atLeastOneGroup: window.customer_groups_at_least_one_group || 'A customer must belong to at least one group.',
    updateFailed: window.customer_groups_update_failed || 'Could not update groups.',
    groupsUpdated: window.customer_groups_updated || 'Group associations updated.'
  };

  function showGroupError(message) {
    if (typeof showErrorMessage === 'function') {
      showErrorMessage(message);
    } else if ($.growl && $.growl.error) {
      $.growl.error({ title: '', message: message });
    }
  }

  function showGroupSuccess(message) {
    if (typeof showSuccessMessage === 'function') {
      showSuccessMessage(message);
    } else if ($.growl && $.growl.notice) {
      $.growl.notice({ title: '', message: message });
    }
  }

  function getCheckedGroupsFromForm() {
    return $('.groupBox:checked').map(function () {
      var $group = $(this);
      var name = $.trim($group.closest('tr').find('label').first().text());

      return {
        id_group: $group.val(),
        name: name || $group.val()
      };
    }).get();
  }

  function updateDefaultGroupSelect(groups, preferredDefault) {
    var $sel = $('#id_default_group');
    if (!$sel.length) {
      return;
    }

    var previous = preferredDefault || $sel.val() || $sel.data('prev');
    $sel.empty();

    $.each(groups, function (i, group) {
      $('<option/>').val(group.id_group).text(group.name).appendTo($sel);
    });

    if (previous && $sel.find('option[value="' + previous + '"]').length) {
      $sel.val(String(previous));
    } else if (groups.length) {
      $sel.prop('selectedIndex', 0);
    }

    $sel.data('prev', $sel.val());
  }

  function syncDefaultGroupSelectFromForm(preferredDefault) {
    updateDefaultGroupSelect(getCheckedGroupsFromForm(), preferredDefault);
  }

  // Handle the master header checkbox that calls checkDelBoxes(...)
  // It toggles all .groupBox without firing 'change', so we enforce at least one
  // and then trigger the normal change flow to sync server.
  $(document).on('click', 'input[type="checkbox"][onclick*="checkDelBoxes"][onclick*="groupBox"]', function () {
    setTimeout(function () {
      var $all = $('.groupBox');
      var $checked = $all.filter(':checked');

      if ($checked.length === 0) {
        var def = $('#id_default_group').val();
        if (def && $all.filter('[value="' + def + '"]').length) {
          $all.filter('[value="' + def + '"]').prop('checked', true);
        } else if ($all.length) {
          $all.first().prop('checked', true);
        }

        showGroupError(messages.atLeastOneGroup);
      }

      // trigger our normal handler so associations are saved
      var $trigger = $('.groupBox:checked').first();
      if ($trigger.length) {
        $trigger.trigger('change');
      } else if ($all.length) {
        $all.first().trigger('change');
      }
    }, 0);
  });

  // Individual group checkbox handler
  $('.groupBox').on('change', function () {
    var $changed = $(this);
    var idCustomer = $('input[name="id_customer"]').val();
    var groups = getCheckedGroupsFromForm();

    if (groups.length === 0) {
      showGroupError(messages.atLeastOneGroup);
      // revert the last toggle
      $changed.prop('checked', !$changed.prop('checked'));
      syncDefaultGroupSelectFromForm();
      return;
    }

    syncDefaultGroupSelectFromForm();

    if (!idCustomer) {
      return;
    }

    $.ajax({
      type: 'POST',
      url: window.currentIndex + '&ajax=1&action=updateCustomerGroups&token=' + window.token,
      dataType: 'json',
      data: { id_customer: idCustomer, 'groupBox': $.map(groups, function (group) { return group.id_group; }) }
    })
    .done(function (data) {
      // error from controller
      if (!data || data.success === false) {
        var msg = (data && data.message) ? data.message : messages.updateFailed;
        showGroupError(msg);
        // revert checkbox state
        $changed.prop('checked', !$changed.prop('checked'));
        syncDefaultGroupSelectFromForm();
        return;
      }

      if (!Array.isArray(data.groups) || data.groups.length === 0) {
        var m = (data && data.message) ? data.message : messages.atLeastOneGroup;
        showGroupError(m);
        $changed.prop('checked', !$changed.prop('checked'));
        syncDefaultGroupSelectFromForm();
        return;
      }

      // rebuild the "Default customer group" select
      updateDefaultGroupSelect(data.groups, data.id_default);

      // green popup
      showGroupSuccess(data.message || messages.groupsUpdated);
    })
    .fail(function () {
      showGroupError(messages.updateFailed);
      $changed.prop('checked', !$changed.prop('checked'));
      syncDefaultGroupSelectFromForm();
    });
  });

});
