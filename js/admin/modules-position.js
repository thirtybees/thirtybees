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

$(function () {

  //
  // Used for the modules listing
  //
  if ($('#position_filer').length !== 0) {
    var panelSelection = $('#modules-position-selection-panel');
    var panelSelectionSingleSelection = panelSelection.find('#modules-position-single-selection');
    var panelSelectionMultipleSelection = panelSelection.find('#modules-position-multiple-selection');

    var panelSelectionOriginalY = panelSelection.offset().top;
    var panelSelectionOriginalYTopMargin = 111;

    panelSelection.css('position', 'relative').hide();

    $(window).on('scroll', function () {
      var scrollTop = $(window).scrollTop();
      panelSelection.css(
        'top',
        scrollTop < panelSelectionOriginalYTopMargin
          ? 0
          : (scrollTop - panelSelectionOriginalY) + panelSelectionOriginalYTopMargin
      );
    });

    var moduleList = $('.modules-position-checkbox');

    moduleList.on('change', function () {
      var checkedCount = moduleList.filter(':checked').length;

      panelSelection.hide();
      panelSelectionSingleSelection.hide();
      panelSelectionMultipleSelection.hide();

      if (checkedCount === 1) {
        panelSelection.show();
        panelSelectionSingleSelection.show();
      }
      else if (checkedCount > 1) {
        panelSelection.show();
        panelSelectionMultipleSelection.show();
        panelSelectionMultipleSelection.find('#modules-position-selection-count').html(checkedCount);
      }
    });

    panelSelection.find('button').click(function () {
      $("button[name='unhookform']").trigger('click');
    });

    var hooksList = [];
    $('section.hook_panel').find('.hook_name').each(function () {
      var $this = $(this);
      hooksList.push({
        title: $this.html(),
        element: $this,
        container: $this.parents('.hook_panel')
      });
    });

    var showModules = $('#show_modules');
    showModules.select2();
    showModules.bind('change', function () {
      modulesPositionFilterHooks();
    });

    var hookPosition = $('#hook_position');
    hookPosition.bind('change', function () {
      modulesPositionFilterHooks();
    });

    $('#hook_search').bind('input', function () {
      modulesPositionFilterHooks();
    });

    function modulesPositionFilterHooks() {
      var id;
      var hookName = $('#hook_search').val();
      var idModule = $('#show_modules').val();
      var position = hookPosition.prop('checked');
      var regex = new RegExp('(' + hookName + ')', 'gi');

      for (id = 0; id < hooksList.length; id += 1) {
        hooksList[id].container.toggle(hookName === '' && idModule === 'all');
        hooksList[id].element.html(hooksList[id].title);
        hooksList[id].container.find('.module_list_item').removeClass('highlight');
      }

      if (hookName !== '' || idModule !== 'all') {
        var hooksToShowFromModule = $();
        var hooksToShowFromHookName = $();

        if (idModule !== 'all') {
          for (id = 0; id < hooksList.length; id += 1) {
            var currentHooks = hooksList[id].container.find('.module_position_' + idModule);
            if (currentHooks.length > 0) {
              hooksToShowFromModule = hooksToShowFromModule.add(hooksList[id].container);
              currentHooks.addClass('highlight');
            }
          }
        }

        if (hookName !== '') {
          for (id = 0; id < hooksList.length; id += 1) {
            var start = hooksList[id].title.toLowerCase().search(hookName.toLowerCase());
            if (start !== -1) {
              hooksToShowFromHookName = hooksToShowFromHookName.add(hooksList[id].container);
              hooksList[id].element.html(hooksList[id].title.replace(regex, '<span class="highlight">$1</span>'));
            }
          }
        }

        if (idModule === 'all' && hookName !== '') {
          hooksToShowFromHookName.show();
        } else if (hookName === '' && idModule !== 'all') {
          hooksToShowFromModule.show();
        } else {
          hooksToShowFromHookName.filter(hooksToShowFromModule).show();
        }
      }

      if (!position) {
        for (id = 0; id < hooksList.length; id += 1) {
          if (hooksList[id].container.is('.hook_position')) {
            hooksList[id].container.hide();
          }
        }
      }
    }
  }

  //
  // Used for the anchor module page
  //
  $('#hook_module_form').find("select[name='id_module']").change(function () {

    var $this = $(this);
    var hookSelect = $("select[name='id_hook']");

    if (parseInt($this.val(), 10) !== 0) {
      $this.find("[value='0']").remove();
      hookSelect.find('option').remove();

      $.ajax({
        type: 'POST',
        url: 'index.php',
        async: true,
        dataType: 'json',
        data: {
          action: 'getPossibleHookingListForModule',
          tab: 'AdminModulesPositions',
          ajax: 1,
          module_id: $this.val(),
          token: window.token
        },
        success: function (jsonData) {
          if (jsonData.hasError) {
            for (var error in jsonData.errors) {
              if (error !== 'indexOf') {
                $('<div />').html(jsonData.errors[error]).text() + '\n';
              }
            }
          } else {
            for (var currentHook = 0; currentHook < jsonData.length; currentHook += 1) {
              hookSelect.append('<option value="' + jsonData[currentHook].id_hook + '">' + jsonData[currentHook].name + '</option>');
            }

            hookSelect.prop('disabled', false);
          }
        }
      });
    }
  });
});
