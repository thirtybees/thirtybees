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

        // red popup
        if (typeof showErrorMessage === 'function') {
          showErrorMessage(window.i18n_at_least_one_group || 'A customer must belong to at least one group.');
        } else if ($.growl && $.growl.error) {
          $.growl.error({ title: '', message: window.i18n_at_least_one_group || 'A customer must belong to at least one group.' });
        }
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
    if (!idCustomer) {
      return;
    }

    var groups = $('.groupBox:checked').map(function () { return $(this).val(); }).get();

    if (groups.length === 0) {
      // popup (red)
      if (typeof showErrorMessage === 'function') {
        showErrorMessage(window.i18n_at_least_one_group || 'A customer must belong to at least one group.');
      } else if ($.growl && $.growl.error) {
        $.growl.error({ title: '', message: window.i18n_at_least_one_group || 'A customer must belong to at least one group.' });
      }
      // revert the last toggle
      $changed.prop('checked', !$changed.prop('checked'));
      return;
    }

    $.ajax({
      type: 'POST',
      url: window.currentIndex + '&ajax=1&action=updateCustomerGroups&token=' + window.token,
      dataType: 'json',
      data: { id_customer: idCustomer, 'groupBox': groups }
    })
    .done(function (data) {
      // error from controller
      if (!data || data.success === false) {
        var msg = (data && data.message) ? data.message : (window.i18n_groups_failed || 'Could not update groups.');
        if (typeof showErrorMessage === 'function') {
          showErrorMessage(msg);
        } else if ($.growl && $.growl.error) {
          $.growl.error({ title: '', message: msg });
        }
        // revert checkbox state
        $changed.prop('checked', !$changed.prop('checked'));
        return;
      }

      if (!Array.isArray(data.groups) || data.groups.length === 0) {
        var m = (data && data.message) ? data.message : (window.i18n_at_least_one_group || 'A customer must belong to at least one group.');
        if (typeof showErrorMessage === 'function') {
          showErrorMessage(m);
        } else if ($.growl && $.growl.error) {
          $.growl.error({ title: '', message: m });
        }
        $changed.prop('checked', !$changed.prop('checked'));
        return;
      }

      // rebuild the "Default customer group" select
      var $sel = $('#id_default_group');
      $sel.empty();
      $.each(data.groups, function (i, g) {
        $('<option/>').val(g.id_group).text(g.name).appendTo($sel);
      });

      // select the server-sent default (covers the case when old default was removed)
      if (typeof data.id_default !== 'undefined' && $sel.find('option[value="' + data.id_default + '"]').length) {
        $sel.val(String(data.id_default));
      } else {
        var prev = $sel.data('prev') || null;
        if (prev && $sel.find('option[value="' + prev + '"]').length) {
          $sel.val(prev);
        } else {
          $sel.prop('selectedIndex', 0);
        }
      }
      $sel.data('prev', $sel.val());

      // green popup
      if (typeof showSuccessMessage === 'function') {
        showSuccessMessage(window.i18n_groups_updated || (data.message || 'Group associations updated.'));
      } else if ($.growl && $.growl.notice) {
        $.growl.notice({ title: '', message: window.i18n_groups_updated || (data.message || 'Group associations updated.') });
      }
    })
    .fail(function () {
      var msg = window.i18n_groups_failed || 'Could not update groups.';
      if (typeof showErrorMessage === 'function') {
        showErrorMessage(msg);
      } else if ($.growl && $.growl.error) {
        $.growl.error({ title: '', message: msg });
      }
      $changed.prop('checked', !$changed.prop('checked'));
    });
  });

});
