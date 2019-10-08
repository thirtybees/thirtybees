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
 * @author    thirty bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2018 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */
/* global window, showSuccessMessage, showErrorMessage,
 * token, jAlert, tinyMCE, display_multishop_checkboxes,
 * reload_tab_description, calcImpactPriceTI, getE, ps_round,
 * msg_cancel_combination, msg_new_combination,
 * handleSaveButtonsForPack, handleSaveButtonsForVirtual, handleSaveButtonsForSimple,
 * updateFriendlyURLByName
 */

function handleSaveButtonsForSimple() {
  return '';
}
function handleSaveButtonsForVirtual() {
  return '';
}

function handleSaveButtonsForPack() {
  // if no item left in the pack, disable save buttons
  if (!$('#inputPackItems').val()) {
    return window.empty_pack_msg;
  }

  return '';
}

/**
 * Handles loading of product tabs
 *
 * @return {object} ProductTabsManager object
 */
function ProductTabsManager() {
  var self = this;
  this.product_tabs = {};
  this.tabs_to_preload = [];
  // this.current_request;
  this.stack_done = [];
  this.page_reloading = false;
  this.has_error_loading_tabs = false;

  /**
   * Show / Hide languages semaphore
   */
  this.allow_hide_other_languages = true;

  this.setTabs = function (tabs) {
    this.product_tabs = tabs;
  };

  /**
   * Schedule execution of onReady() function for each tab and bind events
   *
   * @return {undefined}
   */
  this.init = function () {
    $.each(self.product_tabs, function (tabName, tab) {
      if (typeof tab.onReady === 'function' && tab !== self.product_tabs.Pack) {
        self.onLoad(tabName, tab.onReady);
      }
    });

    $('.shopList.chzn-done').on('change', function () {
      if (self.current_request) {
        self.page_reloading = true;
        self.current_request.abort();
      }
    });

    $(window).on('beforeunload', function () {
      self.page_reloading = true;
    });
  };

  /**
   * Execute a callback function when a specific tab has finished loading or right now if the tab has already loaded
   *
   * @param {string}   tabName name of the tab that is checked for loading
   * @param {function} callback function to call
   *
   * @return {undefined}
   */
  this.onLoad = function (tabName, callback) {
    var container = $('#product-tab-content-' + tabName);
    // Some containers are not loaded depending on the shop configuration
    if (container.length === 0) {
      return;
    }

    // onReady() is always called after the dom has been created for the tab (similar to $(document).ready())
    if (container.hasClass('not-loaded')) {
      container.bind('loaded', callback);
    } else {
      callback();
    }
  };

  /**
   * Get a single tab or recursively get tabs in stack then display them
   *
   * @param {string}  tabName name of the tab
   * @param {boolean} selected is the tab selected
   *
   * @return {object|false} Returns a jqXHR object on success, `false` otherwise
   */
  this.display = function (tabName, selected) {
    var tabSelector = $('#product-tab-content-' + tabName);
    $('#product-tab-content-wait').hide();

    // Is the tab already being loaded?
    if (tabSelector.hasClass('not-loaded') && !tabSelector.hasClass('loading')) {
      // Mark the tab as being currently loading
      tabSelector.addClass('loading');

      // send $_POST array with the request to be able to retrieve posted data if there was an error while saving product
      var data;
      var sendType = 'GET';
      if (window.save_error) {
        sendType = 'POST';
        data = window.post_data;
        // set key_tab so that the ajax call returns the display for the current tab
        data.key_tab = tabName;
      }
      return $.ajax({
        url: $('#link-' + tabName).attr('href') + '&ajax=1' + ($('#page').length ? '&page=' + parseInt($('#page').val(), 10) : '') + '&rand=' + +new Date().getTime(),
        async: true,
        cache: false, // cache needs to be set to false or IE will cache the page with outdated product values
        type: sendType,
        headers: { 'cache-control': 'no-cache' },
        data: data,
        timeout: 30000,
        success: function (responseData) {
          tabSelector.html(responseData).find('.dropdown-toggle').dropdown();
          tabSelector.removeClass('not-loaded');

          if (selected) {
            $('#link-' + tabName).addClass('selected');
            $('#product-tab-content-wait').hide();
            tabSelector.show();
          }
          self.stack_done.push(tabName);
          tabSelector.trigger('loaded');
        },
        complete: function () {
          tabSelector.removeClass('loading');
          if (selected) {
            tabSelector.trigger('displayed');
          }
        },
        beforeSend: function () {
          // don't display the loading notification bar
          if (typeof (window.ajax_running_timeout) !== 'undefined') {
            clearTimeout(window.ajax_running_timeout);
          }
          if (selected) {
            $('#product-tab-content-wait').show();
          }
        }
      });
    }

    return false;
  };

  /**
   * Send an ajax call for each tab in the stack
   *
   * @param {string[]} stack contains tab names as strings
   *
   * @return {boolean} Whether showing the bulk actions was succesful
   */
  this.displayBulk = function (stack) {
    this.current_request = this.display(stack[0], false);

    /* In order to prevent mod_evasive DOSPageInterval (Default 1s)*/
    var time = 0;
    if (window.mod_evasive) {
      time = 1000;
    }
    var tabsRunningTimeout = setTimeout(function () {
      stack.shift();
      if (stack.length > 0) {
        self.displayBulk(stack);
      }
    }, time);

    if (typeof this.current_request !== 'undefined' && typeof this.current_request === "function") {
      this.current_request.complete(function (request, status) {
        var wrongStatuses = ['abort', 'error', 'timeout'];
        var wrongStatusCodes = [400, 401, 403, 404, 405, 406, 408, 410, 413, 429, 499, 500, 502, 503, 504];

        if (($.inArray(status, wrongStatuses) !== -1 || $.inArray(request.status, wrongStatusCodes) !== -1) && !self.page_reloading) {
          var currentTab = '';
          if (typeof request.responseText !== 'undefined' && request.responseText && request.responseText.length) {
            currentTab = $(request.responseText);
            if (currentTab) {
              currentTab = currentTab
                .filter('.product-tab')
                .attr('id');
            }
            if (currentTab) {
              currentTab.replace('product-', '');
            }

            if (typeof currentTab === 'undefined' || !currentTab) {
              var urlRegex = /action=([a-zA-Z]+)/g;
              var data = urlRegex.exec(this.url);

              currentTab = data[1];
            } else {
              currentTab = currentTab[0].toUpperCase() + currentTab.slice(1);
            }

            // De-Franglais the name
            if (currentTab === 'Attachements') {
              currentTab = 'Attachments';
            }
          }

          jAlert((currentTab ? 'Tab : ' + currentTab : '') + ' (' + (request.status ? request.status + ' ' : '') + request.statusText + ')\n' + window.reload_tab_description, reload_tab_title);

          // Only the information tab is fatal, we just block the other tabs, so the merchant can keep working with the tabs
          // that are still available
          if (currentTab === 'Informations') {
            self.page_reloading = true;
            self.has_error_loading_tabs = true;
            clearTimeout(tabsRunningTimeout);

            return false;
          }

          $('#link-' + currentTab)
            .addClass('disabled')
            .attr('disabled', 'disabled')
            .attr('href', '#')
            .off();

          return true; // Because we can still continue
        } else if (!self.has_error_loading_tabs && (self.stack_done.length === self.tabs_to_preload.length)) {
          $('[name="submitAddproductAndStay"]').each(function () {
            $(this)
              .prop('disabled', false)
              .find('i')
              .removeClass('process-icon-loading')
              .addClass('process-icon-save');
          });
          $('[name="submitAddproduct"]').each(function () {
            $(this)
              .prop('disabled', false)
              .find('i')
              .removeClass('process-icon-loading')
              .addClass('process-icon-save');
          });
          self.allow_hide_other_languages = true;
          clearTimeout(tabsRunningTimeout);

          return false;
        }

        return true;
      });
    } else if (window.display_multishop_checkboxes && !self.has_error_loading_tabs && (self.stack_done.length === self.tabs_to_preload.length)) {
      // this.current_request unavailable because there is nothing to load
      $('[name="submitAddproductAndStay"]').each(function () {
        $(this)
          .prop('disabled', false)
          .find('i')
          .removeClass('process-icon-loading')
          .addClass('process-icon-save');
      });
      $('[name="submitAddproduct"]').each(function () {
        $(this)
          .prop('disabled', false)
          .find('i')
          .removeClass('process-icon-loading')
          .addClass('process-icon-save');
      });
      self.allow_hide_other_languages = true;
      clearTimeout(tabsRunningTimeout);

      return false;
    }

    return false;
  };
}

/**
 * Load pack data
 */
function loadPack() {
  var idProduct = $('input[name=id_product]').first().val();
  $.ajax({
    url: 'index.php?controller=AdminProducts&token=' + token + '&id_product=' + idProduct + '&action=Pack&updateproduct&ajax=1&rand=' + new Date().getTime(),
    async: true,
    cache: false, // cache needs to be set to false or IE will cache the page with outdated product values
    type: 'GET',
    headers: { 'cache-control': 'no-cache' },
    success: function (responseData) {
      $('#product-pack-container').html(responseData);
      window.product_tabs.Pack.onReady();
    }
  });
}

// array of product tab objects containing methods and dom bindings
// The ProductTabsManager instance will make sure the onReady() methods of each tabs are executed once the tab has loaded
window.product_tabs = {};

window.product_tabs.Customization = new function () {
  this.onReady = function () {
    if (window.display_multishop_checkboxes) {
      window.ProductMultishop.checkAllCustomization();
    }
  };
}();
window.product_tabs.Combinations = new function () {
  var self = this;
  this.bindEdit = function () {
    function editProductAttribute(url, parent) {
      $.ajax({
        url: url,
        data: {
          id_product: window.id_product,
          ajax: true,
          action: 'editProductAttribute'
        },
        dataType: 'json',
        context: this,
        success: function (data) {
          // color the selected line
          parent.siblings().removeClass('selected-line');
          parent.addClass('selected-line');

          $('#add_new_combination').show();
          $('#attribute_quantity').show();
          $('#product_att_list').html('');
          self.removeButtonCombination('update');
          window.scroll_if_anchor('#add_new_combination');
          var wholesalePrice = data[0].wholesale_price;
          var price = data[0].price;
          var weight = data[0].weight;
          var unitImpact = data[0].unit_price_impact;
          var reference = data[0].reference;
          var ean = data[0].ean13;
          var quantity = data[0].quantity;
          var image = false;
          var productAttList = [];
          for (var i = 0; i < data.length; i += 1) {
            productAttList.push(data[i].group_name + ' : ' + data[i].attribute_name);
            productAttList.push(data[i].id_attribute);
          }

          var idProductAttribute = data[0].id_product_attribute;
          var defaultAttribute = data[0].default_on;
          var ecoTax = data[0].ecotax;
          var upc = data[0].upc;
          var minimalQuantity = data[0].minimal_quantity;
          var availableDate = data[0].available_date;

          self.fillCombination(
            wholesalePrice,
            price,
            weight,
            unitImpact,
            reference,
            ean,
            quantity,
            image,
            productAttList,
            idProductAttribute,
            defaultAttribute,
            ecoTax,
            upc,
            minimalQuantity,
            availableDate
          );
          calcImpactPriceTI();
        }
      });
    }

    $('table.configuration').delegate('a.edit', 'click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      editProductAttribute(this.href, $(this).closest('tr'));
    });
  };

  this.defaultProductAttribute = function (url, item) {
    $.ajax({
      url: url,
      data: {
        id_product: window.id_product,
        action: 'defaultProductAttribute',
        ajax: true
      },
      dataType: 'json',
      success: function (data) {
        if (data.status === 'ok') {
          showSuccessMessage(data.message);
          $('.highlighted').removeClass('highlighted');
          $(item).closest('tr').addClass('highlighted');
        } else {
          showErrorMessage(data.message);
        }
      }
    });
  };

  this.bindDefault = function () {
    $('table.configuration').delegate('a.default', 'click', function (e) {
      e.preventDefault();
      self.defaultProductAttribute(this.href, this);
    });
  };

  /**
   * Delete a product attribute
   *
   * @param {string} url
   * @param {object} parent
   */
  this.deleteProductAttribute = function (url, parent) {
    $.ajax({
      url: url,
      data: {
        id_product: window.id_product,
        action: 'deleteProductAttribute',
        ajax: true
      },
      dataType: 'json',
      context: this,
      success: function (data) {
        if (data.status === 'ok') {
          showSuccessMessage(data.message);
          parent.remove();
          if (data.id_product_attribute) {
            if (data.attribute) {
              var td = $('#qty_' + data.id_product_attribute);
              td.attr('id', 'qty_0');
              td.children('input').val('0').attr('name', 'qty_0');
              td.next('td').text(data.attribute[0].name);
            } else {
              $('#qty_' + data.id_product_attribute).parent().hide();
            }
          }
        } else {
          showErrorMessage(data.message);
        }
      }
    });
  };

  /**
   * Bind to the delete button
   */
  this.bindDelete = function () {
    $('table.configuration').delegate('a.delete', 'click', function (e) {
      e.preventDefault();
      self.deleteProductAttribute(this.href, $(this).closest('tr'));
    });
  };

  /**
   * Remove a combination button
   *
   * @return {undefined}
   */
  this.removeButtonCombination = function () {
    $('#add_new_combination').show();
    var $descProductNewCombination = $('#desc-product-newCombination');
    $descProductNewCombination.children('i').first().removeClass('process-icon-new');
    $descProductNewCombination.children('i').first().addClass('process-icon-minus');
    $descProductNewCombination.children('span').first().html(msg_cancel_combination);
    $('id_product_attribute').val(0);
    self.init_elems();
  };

  /**
   * Add a combination button
   *
   * @return {undefined}
   */
  this.addButtonCombination = function () {
    $('#add_new_combination').hide();
    var $descProductNewCombination = $('#desc-product-newCombination');
    $descProductNewCombination.children('i').first().removeClass('process-icon-minus');
    $descProductNewCombination.children('i').first().addClass('process-icon-new');
    $descProductNewCombination.children('span').first().html(msg_new_combination);
  };

  /**
   * Bind toggle add combination button
   *
   * @return {undefined}
   */
  this.bindToggleAddCombination = function () {
    $('#desc-product-newCombination').click(function (e) {
      e.preventDefault();

      if ($(this).children('i').first().hasClass('process-icon-new')) {
        self.removeButtonCombination('add');
      } else {
        self.addButtonCombination('add');
        $('#id_product_attribute').val(0);
      }
    });
  };

  /**
   * Fill the combination table
   *
   * @param {number}    wholesalePrice
   * @param {number}    priceImpact
   * @param {number}    weightImpact
   * @param {number}    unitImpact
   * @param {string}    reference
   * @param {string}    ean
   * @param {number}    quantity
   * @param {undefined} image
   * @param {number}    oldAttr
   * @param {number}    idProductAttribute
   * @param {number}    defaultAttribute
   * @param {float}     ecoTax
   * @param {string}    upc
   * @param {number}    minimalQuantity
   * @param {string}    availableDate
   *
   * @return {undefined}
   */
  this.fillCombination = function (
    wholesalePrice,
    priceImpact,
    weightImpact,
    unitImpact,
    reference,
    ean,
    quantity,
    image,
    oldAttr,
    idProductAttribute,
    defaultAttribute,
    ecoTax,
    upc,
    minimalQuantity,
    availableDate
  ) {
    self.init_elems();
    $('#stock_mvt_attribute').show();
    $('#initial_stock_attribute').hide();
    $('#attribute_quantity')
      .html(quantity)
      .show();
    $('#attr_qty_stock').show();

    $('#attribute_minimal_quantity').val(minimalQuantity);

    getE('attribute_reference').value = reference;

    getE('attribute_ean13').value = ean;
    getE('attribute_upc').value = upc;
    getE('attribute_wholesale_price').value = displayPriceValue(wholesalePrice);
    getE('attribute_price').value = displayPriceValue(priceImpact);
    getE('attribute_priceTEReal').value = displayPriceValue(priceImpact);
    getE('attribute_weight').value = weightImpact;
    getE('attribute_unity').value = unitImpact;
    if ($('#attribute_ecotax').length > 0) {
      getE('attribute_ecotax').value = displayPriceValue(ecoTax);
    }

    getE('attribute_default').checked = parseInt(defaultAttribute, 10) === 1;

    if (priceImpact < 0) {
      getE('attribute_price_impact').options[getE('attribute_price_impact').selectedIndex].value = -1;
      getE('attribute_price_impact').selectedIndex = 2;
    } else if (!priceImpact) {
      getE('attribute_price_impact').options[getE('attribute_price_impact').selectedIndex].value = 0;
      getE('attribute_price_impact').selectedIndex = 0;
    } else if (priceImpact > 0) {
      getE('attribute_price_impact').options[getE('attribute_price_impact').selectedIndex].value = 1;
      getE('attribute_price_impact').selectedIndex = 1;
    }
    if (weightImpact < 0) {
      getE('attribute_weight_impact').options[getE('attribute_weight_impact').selectedIndex].value = -1;
      getE('attribute_weight_impact').selectedIndex = 2;
    } else if (!weightImpact) {
      getE('attribute_weight_impact').options[getE('attribute_weight_impact').selectedIndex].value = 0;
      getE('attribute_weight_impact').selectedIndex = 0;
    } else if (weightImpact > 0) {
      getE('attribute_weight_impact').options[getE('attribute_weight_impact').selectedIndex].value = 1;
      getE('attribute_weight_impact').selectedIndex = 1;
    }
    if (unitImpact < 0) {
      getE('attribute_unit_impact').options[getE('attribute_unit_impact').selectedIndex].value = -1;
      getE('attribute_unit_impact').selectedIndex = 2;
    } else if (!unitImpact) {
      getE('attribute_unit_impact').options[getE('attribute_unit_impact').selectedIndex].value = 0;
      getE('attribute_unit_impact').selectedIndex = 0;
    } else if (unitImpact > 0) {
      getE('attribute_unit_impact').options[getE('attribute_unit_impact').selectedIndex].value = 1;
      getE('attribute_unit_impact').selectedIndex = 1;
    }

    $('#add_new_combination').show();

    /* Reset all combination images */
    window.combinationImages = $('#id_image_attr').find('input[id^=id_image_attr_]');
    window.combinationImages.each(function () {
      this.checked = false;
    });

    /* Check combination images */
    if (typeof window.combination_images[idProductAttribute] !== 'undefined') {
      for (var i = 0; i < window.combination_images[idProductAttribute].length; i += 1) {
        $('#id_image_attr_' + window.combination_images[idProductAttribute][i]).attr('checked', true);
      }
    }
    window.check_impact();
    window.check_weight_impact();
    window.check_unit_impact();

    var elem = getE('product_att_list');

    for (var j = 0; j < oldAttr.length; j += 1) {
      var opt = document.createElement('option');
      opt.text = oldAttr[j];
      j += 1;
      opt.value = oldAttr[j];
      try {
        elem.add(opt, null);
      } catch (ex) {
        elem.add(opt);
      }
    }
    getE('id_product_attribute').value = idProductAttribute;

    $('#available_date_attribute').val(availableDate);
  };

  this.init_elems = function () {
    var impact = getE('attribute_price_impact');
    var impact2 = getE('attribute_weight_impact');
    var elem = getE('product_att_list');

    if (elem.length) {
      for (var i = elem.length - 1; i >= 0; i -= 1) {
        if (elem[i]) {
          elem.remove(i);
        }
      }
    }

    $('input[name="id_image_attr[]"]').each(function () {
      $(this).attr('checked', false);
    });

    $('#attribute_default').attr('checked', false);

    getE('attribute_price_impact').selectedIndex = 0;
    getE('attribute_weight_impact').selectedIndex = 0;
    getE('attribute_unit_impact').selectedIndex = 0;
    $('#span_unit_impact').hide();
    $('#unity_third').html($('#unity_second').html());

    if ($('#unity').is()) {
      if ($('#unity').get(0).value.length > 0) {
        $('#tr_unit_impact').show();
      } else {
        $('#tr_unit_impact').hide();
      }
    }
    try {
      if (parseInt(impact.options[impact.selectedIndex].value, 10) === 0) {
        $('#span_impact').hide();
      }
      if (parseInt(impact2.options[impact.selectedIndex].value, 10) === 0) {
        getE('span_weight_impact').style.display = 'none';
      }
    } catch (e) {
      $('#span_impact').hide();
      getE('span_weight_impact').style.display = 'none';
    }
  };

  this.onReady = function () {
    self.bindEdit();
    self.bindDefault();
    self.bindDelete();
    self.bindToggleAddCombination();
    if (window.display_multishop_checkboxes) {
      window.ProductMultishop.checkAllCombinations();
    }
  };
}();

/**
 * hide save and save-and-stay buttons
 *
 * @access public
 *
 * @return {undefined}
 */
function disableSave() {
  // $('button[name="submitAddproduct"]').hide();
  // $('button[name="submitAddproductAndStay"]').hide();
}

/**
 * show save and save-and-stay buttons
 *
 * @access public
 *
 * @return {undefined}
 */
function enableSave() {
  $('button[name="submitAddproduct"]').show();
  $('button[name="submitAddproductAndStay"]').show();
}

function handleSaveButtons() {
  window.msg = [];
  var $disableSaveMessage = $('#disableSaveMessage');
  // relative to type of product
  if (parseInt(window.product_type, 10) === parseInt(window.product_type_pack, 10)) {
    window.msg.push(handleSaveButtonsForPack());
  } else if (parseInt(window.product_type, 10) === parseInt(window.product_type_pack, 10)) {
    window.msg.push(handleSaveButtonsForVirtual());
  } else {
    window.msg.push(handleSaveButtonsForSimple());
  }

  // common for all products
  $disableSaveMessage.remove();

  if (!$('#name_' + parseInt(window.id_lang_default, 10)).val() && (!window.display_multishop_checkboxes || $('input[name=\'multishop_check[name][' + window.id_lang_default + ']\']').prop('checked'))) {
    window.msg.push(window.empty_name_msg);
  } else if (!$('#link_rewrite_' + parseInt(window.id_lang_default, 10)).val() && (!window.display_multishop_checkboxes || $('input[name=\'link_rewrite[name][' + window.id_lang_default + ']\']').prop('checked'))) {
    // check friendly_url_[defaultlangid] only if name is ok
    window.msg.push(window.empty_link_rewrite_msg);
  }

  if (window.msg.length === 0) {
    $disableSaveMessage.remove();
    enableSave();
  } else {
    $disableSaveMessage.remove();
    window.do_not_save = false;
    for (var key in window.msg) {
      if (key !== '') {
        if (!window.do_not_save) {
          $('.leadin').append('<div id="disableSaveMessage" class="alert alert-danger"></div>');
          window.warnDiv = $disableSaveMessage;
          window.do_not_save = true;
        }
        window.warnDiv.append('<p id="' + key + '">' + window.msg[key] + '</p>');
      }
    }
    if (window.do_not_save) {
      disableSave();
    } else {
      enableSave();
    }
  }
}

window.product_tabs.Seo = new function () {
  this.onReady = function () {
    if ($('#link_rewrite_' + window.id_lang_default).length) {
      if (!$('#link_rewrite_' + window.id_lang_default).val().replace(/^\s+|\s+$/gm, '')) {
        updateFriendlyURLByName();
      }
    }

    // Enable writing of the product name when the friendly url field in tab SEO is loaded
    $('.copy2friendlyUrl').removeAttr('disabled');

    window.displayFlags(window.languages, window.id_language, window.allowEmployeeFormLang);

    if (window.display_multishop_checkboxes) {
      window.ProductMultishop.checkAllSeo();
    }
  };
}();

window.product_tabs.Prices = new function () {
  var self = this;
  // Bind to show/hide new specific price form
  this.toggleSpecificPrice = function () {
    $('#show_specific_price').click(function () {
      $('#add_specific_price').slideToggle();

      $('#add_specific_price').append('<input type="hidden" name="submitPriceAddition"/>');

      $('#hide_specific_price').show();
      $('#show_specific_price').hide();
      return false;
    });

    $('#hide_specific_price').click(function () {
      $('#add_specific_price').slideToggle();
      $('#add_specific_price').find('input[name=submitPriceAddition]').remove();
      $('#hide_specific_price').hide();
      $('#show_specific_price').show();
      return false;
    });
  };

  /**
   * Ajax call to delete a specific price
   *
   * @param {string} url
   * @param {Object} parent
   *
   * @return {undefined}
   */
  this.deleteSpecificPrice = function (url, parent) {
    if (typeof url !== 'undefined') {
      $.ajax({
        url: url,
        data: {
          ajax: true
        },
        dataType: 'json',
        context: this,
        success: function (data) {
          if (data !== null) {
            if (data.status === 'ok') {
              showSuccessMessage(data.message);
              parent.remove();
            } else {
              showErrorMessage(data.message);
            }
          }
        }
      });
    }
  };

  // Bind to delete specific price link
  this.bindDelete = function () {
    $('#specific_prices_list').delegate('a[name="delete_link"]', 'click', function (e) {
      e.preventDefault();
      if (confirm(window.delete_price_rule)) {
        self.deleteSpecificPrice(this.href, $(this).parents('tr'));
      }
    });
  };

  this.loadInformations = function (selectId, action) {
    window.id_shop = $('#sp_id_shop').val();
    $.ajax({
      url: window.product_url + '&action=' + action + '&ajax=true&id_shop=' + window.id_shop,
      success: function (data) {
        $(selectId + ' option').not(':first').remove();
        $(selectId).append(data);
      }
    });
  };

  this.onReady = function () {
    self.toggleSpecificPrice();
    self.deleteSpecificPrice();
    self.bindDelete();

    $('#sp_id_shop').change(function () {
      self.loadInformations('#sp_id_group', 'getGroupsOptions');
      self.loadInformations('#spm_currency_0', 'getCurrenciesOptions');
      self.loadInformations('#sp_id_country', 'getCountriesOptions');
    });
    if (window.display_multishop_checkboxes) {
      window.ProductMultishop.checkAllPrices();
    }
  };
}();

window.product_tabs.Associations = new function () {
  var self = this;
  this.initAccessoriesAutocomplete = function () {
    $('#product_autocomplete_input')
      .autocomplete('ajax_products_list.php?exclude_packs=0&excludeVirtuals=0', {
        minChars: 1,
        autoFill: true,
        max: 20,
        matchContains: true,
        mustMatch: false,
        scroll: false,
        cacheLength: 0,
        formatItem: function (item) {
          return item[1] + ' - ' + item[0];
        }
      }).result(self.addAccessory);

    $('#product_autocomplete_input').setOptions({
      extraParams: {
        excludeIds: self.getAccessoriesIds()
      }
    });
  };

  this.getAccessoriesIds = function () {
    if (!$('#inputAccessories').val()) {
      return window.id_product;
    }
    return window.id_product + ',' + $('#inputAccessories').val().replace(/\-/g, ',');
  };

  /**
   * Add accesory
   *
   * @param {Object}    event
   * @param {Array}     data
   * @param {undefined} formatted
   *
   * @return {undefined}
   */
  this.addAccessory = function (event, data, formatted) {
    if (typeof data === 'undefined') {
      return;
    }
    var productId = data[1];
    var productName = data[0];

    var $divAccessories = $('#divAccessories');
    var $inputAccessories = $('#inputAccessories');
    var $nameAccessories = $('#nameAccessories');

    /* delete product from select + add product line to the div, input_name, input_ids elements */
    $divAccessories.html($divAccessories.html() + '<div class="form-control-static"><button type="button" class="delAccessory btn btn-default" name="' + productId + '"><i class="icon-remove text-danger"></i></button>&nbsp;' + productName + '</div>');
    $nameAccessories.val($nameAccessories.val() + productName + '¤');
    $inputAccessories.val($inputAccessories.val() + productId + '-');
    $('#product_autocomplete_input').val('');
    $('#product_autocomplete_input').setOptions({
      extraParams: { excludeIds: self.getAccessoriesIds() }
    });
  };

  /**
   *
   * @param {number} id
   *
   * @return {undefined}
   */
  this.delAccessory = function (id) {
    var div = getE('divAccessories');
    var input = getE('inputAccessories');
    var name = getE('nameAccessories');

    // Cut hidden fields in array
    var inputCut = input.value.split('-');
    var nameCut = name.value.split('¤');

    if (inputCut.length !== nameCut.length) {
      jAlert('Bad size');

      return;
    }

    // Reset all hidden fields
    input.value = '';
    name.value = '';
    div.innerHTML = '';
    for (var i in inputCut) {
      // If empty, error, next
      if (!inputCut[i] || !nameCut[i]) {
        continue;
      }

      // Add to hidden fields no selected products OR add to select field selected product
      if (inputCut[i] !== id) {
        input.value += inputCut[i] + '-';
        name.value += nameCut[i] + '¤';
        div.innerHTML += '<div class="form-control-static"><button type="button" class="delAccessory btn btn-default" name="' + inputCut[i] + '"><i class="icon-remove text-danger"></i></button>&nbsp;' + nameCut[i] + '</div>';
      } else {
        $('#selectAccessories').append('<option selected="selected" value="' + inputCut[i] + '-' + nameCut[i] + '">' + inputCut[i] + ' - ' + nameCut[i] + '</option>');
      }
    }

    $('#product_autocomplete_input').setOptions({
      extraParams: { excludeIds: self.getAccessoriesIds() }
    });
  };

  /**
   * Update the manufacturer select element with the list of existing manufacturers
   */
  this.getManufacturers = function () {
    $.ajax({
      url: 'ajax-tab.php',
      cache: false,
      dataType: 'json',
      data: {
        ajaxProductManufacturers: '1',
        ajax: '1',
        token: token,
        controller: 'AdminProducts',
        action: 'productManufacturers'
      },
      success: function (j) {
        var options = '';
        if (j) {
          for (var i = 0; i < j.length; i += 1) {
            options += '<option value="' + j[i].optionValue + '">' + j[i].optionDisplay + '</option>';
          }
        }
        $('select#id_manufacturer').chosen({ width: '250px' }).append(options).trigger('chosen:updated');
      },
      error: function (XMLHttpRequest, textStatus) {
        var $selectManufacturer = $('select#id_manufacturer');
        if ($selectManufacturer.length) {
          $selectManufacturer.replaceWith('<p id="id_manufacturer">[TECHNICAL ERROR] ajaxProductManufacturers: ' + textStatus + '</p>');
        }
      }
    });
  };

  this.onReady = function () {
    self.initAccessoriesAutocomplete();
    self.getManufacturers();
    $('#divAccessories').delegate('.delAccessory', 'click', function () {
      self.delAccessory($(this).attr('name'));
    });
    if (window.display_multishop_checkboxes) {
      window.ProductMultishop.checkAllAssociations();
    }
  };
}();

window.product_tabs.Attachments = new function () {
  var self = this;
  this.bindAttachmentEvents = function () {
    $('#addAttachment').on('click', function () {
      $('#selectAttachment2 option:selected').each(function () {
        var val = $('#arrayAttachments').val();
        var tab = val.split(',');
        for (var i = 0; i < tab.length; i += 1) {
          if (tab[i] === $(this).val()) {
            return false;
          }
        }
        $('#arrayAttachments').val(val + $(this).val() + ',');
      });
      return !$('#selectAttachment2 option:selected').remove().appendTo('#selectAttachment1');
    });
    $('#removeAttachment').on('click', function () {
      $('#selectAttachment1 option:selected').each(function () {
        var val = $('#arrayAttachments').val();
        var tab = val.split(',');
        var tabs = '';
        for (var i = 0; i < tab.length; i += 1) {
          if (tab[i] !== $(this).val()) {
            tabs = tabs + ',' + tab[i];
            $('#arrayAttachments').val(tabs);
          }
        }
      });
      return !$('#selectAttachment1 option:selected').remove().appendTo('#selectAttachment2');
    });
    $('#product').submit(function () {
      $('#selectAttachment1 option').each(function () {
        $(this).attr('selected', 'selected');
      });
    });
  };

  this.onReady = function () {
    self.bindAttachmentEvents();
  };
}();

window.product_tabs.Shipping = new function () {
  var self = this;

  this.bindCarriersEvents = function () {
    $('#addCarrier').on('click', function () {
      $('#availableCarriers option:selected').each(function () {
        $('#selectedCarriers').append("<option value='" + $(this).val() + "'>" + $(this).text() + '</option>');
        $(this).remove();
      });
      $('#selectedCarriers option').prop('selected', true);

      if ($('#selectedCarriers').find('option').length === 0) {
        $('#no-selected-carries-alert').show();
      } else {
        $('#no-selected-carries-alert').hide();
      }
    });

    $('#removeCarrier').on('click', function () {
      $('#selectedCarriers option:selected').each(function () {
        $('#availableCarriers').append("<option value='" + $(this).val() + "'>" + $(this).text() + '</option>');
        $(this).remove();
      });
      $('#selectedCarriers option').prop('selected', true);

      if ($('#selectedCarriers').find('option').length === 0) {
        $('#no-selected-carries-alert').show();
      } else {
        $('#no-selected-carries-alert').hide();
      }
    });
  };

  this.onReady = function () {
    self.bindCarriersEvents();
  };
}();

window.product_tabs.Informations = new function () {
  var self = this;
  this.bindAvailableForOrder = function () {
    $('#available_for_order').click(function () {
      if ($(this).is(':checked') || ($('input[name=\'multishop_check[show_price]\']').length && !$('input[name=\'multishop_check[show_price]\']').prop('checked'))) {
        $('#show_price').attr('checked', true);
        $('#show_price').attr('disabled', true);
      } else {
        $('#show_price').attr('disabled', false);
      }
    });

    if ($('#active_on').prop('checked')) {
      window.showRedirectProductOptions(false);
      window.showRedirectProductSelectOptions(false);
    } else {
      window.showRedirectProductOptions(true);
    }

    $('#redirect_type').change(function () {
      window.redirectSelectChange();
    });

    $('#related_product_autocomplete_input')
      .autocomplete('ajax_products_list.php?exclude_packs=0&excludeVirtuals=0&excludeIds=' + window.id_product, {
        minChars: 1,
        autoFill: true,
        max: 20,
        matchContains: true,
        mustMatch: false,
        scroll: false,
        cacheLength: 0,
        formatItem: function (item) {
          return item[0] + ' - ' + item[1];
        }
      }).result(function (e, i) {
        if (typeof i !== 'undefined') {
          window.addRelatedProduct(i[1], i[0]);
        }
        $(this).val('');
      });
    window.addRelatedProduct(window.id_product_redirected, window.product_name_redirected);
  };

  this.bindTagImage = function () {
    function changeTagImage() {
      var smallImage = $('input[name=smallImage]:checked').attr('value');
      var leftRight = $('input[name=leftRight]:checked').attr('value');
      var imageTypes = $('input[name=imageTypes]:checked').attr('value');
      var tag = '[img-' + smallImage + '-' + leftRight + '-' + imageTypes + ']';
      $('#resultImage').val(tag);
    }

    changeTagImage();
    $('#createImageDescription input').change(function () {
      changeTagImage();
    });

    var i = 0;
    $('.addImageDescription').click(function () {
      if (i === 0) {
        $('#createImageDescription').animate({
          opacity: 1, height: 'toggle'
        }, 500);
        i = 1;
      } else {
        $('#createImageDescription').animate({
          opacity: 0, height: 'toggle'
        }, 500);
        i = 0;
      }
    });
  };

  this.switchProductType = function () {
    window.product_type = parseInt(window.product_type, 10);
    if (window.product_type === window.product_type_pack) {
      $('#pack_product').attr('checked', true);
    } else if (window.product_type === window.product_type_virtual) {
      $('#virtual_product').attr('checked', true);
      $('#condition').attr('disabled', true);
      $('#condition option[value=new]').attr('selected', true);
    } else {
      $('#simple_product').attr('checked', true);
    }

    $('input[name="type_product"]').on('click', function(e) {
      // Reset settings
      $('a[id*="VirtualProduct"]').hide();

      $('#product-pack-container').hide();

      $('#is_virtual').val(0);

      window.product_type = parseInt($(this).val(), 10);
      $('#warn_virtual_combinations').hide();
      $('#warn_pack_combinations').hide();
      // until a product is added in the pack
      // if product is PTYPE_PACK, save buttons will be disabled
      if (window.product_type === window.product_type_pack) {
        if (window.has_combinations) {
          $('#simple_product').attr('checked', true);
          $('#warn_pack_combinations').show();
        } else {
          $('#product-pack-container').show();
          // If the pack tab has not finished loaded the changes will be made when the loading event is triggered
          $('#product-tab-content-Pack').on('loaded', function () {
            $('#ppack').val(1).attr('checked', true).attr('disabled', true);
          });
          $('#product-tab-content-Quantities').on('loaded', function () {
            $('.stockForVirtualProduct').show();
          });

          $('a[id*="Combinations"]').hide();
          $('a[id*="Shipping"]').show();

          $('#condition').removeAttr('disabled');
          $('#condition option[value=new]').removeAttr('selected');
          $('.stockForVirtualProduct').show();
          // if pack is enabled, if you choose pack, automatically switch to pack page
        }
      } else if (window.product_type === window.product_type_virtual) {
        if (window.has_combinations) {
          $('#simple_product').attr('checked', true);
          $('#warn_virtual_combinations').show();
        } else {
          $('a[id*="VirtualProduct"]').show();
          $('#is_virtual').val(1);

          window.tabs_manager.onLoad('VirtualProduct', function() {
            $('#virtual_good').show();
          });

          window.tabs_manager.onLoad('Quantities', function() {
            $('.stockForVirtualProduct').hide();
          });

          $('a[id*="Combinations"]').hide();
          $('a[id*="Shipping"]').hide();

          window.tabs_manager.onLoad('Informations', function() {
            $('#condition').attr('disabled', true);
            $('#condition option[value=refurbished]').removeAttr('selected');
            $('#condition option[value=used]').removeAttr('selected');
          });
        }
      } else {
        // 3rd case : product_type is PTYPE_SIMPLE (0)
        $('a[id*="Combinations"]').show();
        $('a[id*="Shipping"]').show();
        $('#condition').removeAttr('disabled');
        $('#condition option[value=new]').removeAttr('selected');
        $('.stockForVirtualProduct').show();
      }
      // this handle the save button displays and warnings
      handleSaveButtons();
    });
  };

  this.onReady = function () {
    loadPack();
    self.bindAvailableForOrder();
    self.bindTagImage();
    self.switchProductType();

    if (window.display_multishop_checkboxes) {
      window.ProductMultishop.checkAllInformations();
      var active_click = function () {
        if (!$('input[name=\'multishop_check[active]\']').prop('checked')) {
          $('.draft').hide();
          window.showOptions(true);
        } else {
          var checked = $('#active_on').prop('checked');
          window.toggleDraftWarning(checked);
          window.showOptions(checked);
        }
      };
      $('input[name=\'multishop_check[active]\']').click(active_click);
      active_click();
    }
  };
}();

window.product_tabs.Pack = new function () {
  var self = this;

  this.bindPackEvents = function () {
    $('body').on('click', '.delPackItem', function () {
      delPackItem($(this).data('delete'), $(this).data('delete-attr'));
    });

    function productFormatResult(item) {
      var itemTemplate = "<div class='media'>";
      itemTemplate += "<div class='pull-left'>";
      itemTemplate += "<img class='media-object' width='40' src='" + item.image + "' alt='" + item.name + "'>";
      itemTemplate += '</div>';
      itemTemplate += "<div class='media-body'>";
      itemTemplate += "<h4 class='media-heading'>" + item.name + '</h4>';
      itemTemplate += '<span>REF: ' + item.ref + '</span>';
      itemTemplate += '</div>';
      itemTemplate += '</div>';

      return itemTemplate;
    }

    function productFormatSelection(item) {
      return item.name;
    }

    var selectedProduct;
    $('#curPackItemName').select2({
      placeholder: window.search_product_msg,
      minimumInputLength: 2,
      width: '100%',
      dropdownCssClass: 'bootstrap',
      ajax: {
        url: 'ajax_products_list.php',
        dataType: 'json',
        data: function (term) {
          return {
            q: term,
            packItself: $('input[name=\'id_product\']').val()
          };
        },
        results: function (data) {
          var excludeIds = getSelectedIds();
          var returnIds = [];
          if (data) {
            for (var i = data.length - 1; i >= 0; i--) {
              var isIn = 0;
              for (var j = 0; j < excludeIds.length; j++) {
                if (data[i].id === excludeIds[j][0] && (typeof data[i].id_product_attribute === 'undefined' || data[i].id_product_attribute === excludeIds[j][1])) {
                  isIn = 1;
                }
              }
              if (!isIn) {
                returnIds.push(data[i]);
              }
            }
            return {
              results: returnIds
            };
          }
          return {
            results: []
          };
        }
      },
      formatResult: productFormatResult,
      formatSelection: productFormatSelection
    })
      .on('select2-selecting', function (e) {
        selectedProduct = e.object;
      });

    function addPackItem() {
      if (selectedProduct) {
        var $curPackItemQty = $('#curPackItemQty');
        selectedProduct.qty = $curPackItemQty.val();
        if ((!selectedProduct.id || !selectedProduct.name) && $curPackItemQty.valid()) {
          window.error_modal(window.error_heading_msg, window.msg_select_one);

          return false;
        } else if (!selectedProduct.qty || !$curPackItemQty.valid() || isNaN($curPackItemQty.val())) {
          window.error_modal(window.error_heading_msg, window.msg_set_quantity);

          return false;
        }

        if (typeof selectedProduct.id_product_attribute === 'undefined') {
          selectedProduct.id_product_attribute = 0;
        }

        var $divPackItems = $('#divPackItems');
        var divContent = $divPackItems.html();
        divContent += '<li class="product-pack-item media-product-pack" data-product-name="' + selectedProduct.name + '" data-product-qty="' + selectedProduct.qty + '" data-product-id="' + selectedProduct.id + '" data-product-id-attribute="' + selectedProduct.id_product_attribute + '">';
        divContent += '<img class="media-product-pack-img" src="' + selectedProduct.image + '"/>';
        divContent += '<span class="media-product-pack-title">' + selectedProduct.name + '</span>';
        divContent += '<span class="media-product-pack-ref">REF: ' + selectedProduct.ref + '</span>';
        divContent += '<span class="media-product-pack-quantity"><span class="text-muted">x</span> ' + selectedProduct.qty + '</span>';
        divContent += '<button type="button" class="btn btn-default delPackItem media-product-pack-action" data-delete="' + selectedProduct.id + '" data-delete-attr="' + selectedProduct.id_product_attribute + '"><i class="icon-trash"></i></button>';
        divContent += '</li>';

        // QTYxID-QTYxID
        // @todo : it should be better to create input for each items and each qty
        // instead of only one separated by x, - and ¤
        var line = selectedProduct.qty + 'x' + selectedProduct.id + 'x' + selectedProduct.id_product_attribute;
        var lineDisplay = selectedProduct.qty + 'x ' + selectedProduct.name;

        $divPackItems.html(divContent);
        $('#inputPackItems').val($('#inputPackItems').val() + line + '-');
        $('#namePackItems').val($('#namePackItems').val() + lineDisplay + '¤');

        selectedProduct = null;
        $('#curPackItemName').select2('val', '');
        $('.pack-empty-warning').hide();
      } else {
        window.error_modal(window.error_heading_msg, window.msg_select_one);

        return false;
      }
    }

    $('#add_pack_item').on('click', addPackItem);

    function delPackItem(id, idAttribute) {
      var reg = new RegExp('-', 'g');
      var regx = new RegExp('x', 'g');

      var input = $('#inputPackItems');
      var namePack = $('#namePackItems');

      var inputCut = input.val().split(reg);
      var nameCut = namePack.val().split(new RegExp('¤', 'g'));

      input.val(null);
      namePack.val(null);
      for (var i = 0; i < inputCut.length; i += 1) {
        if (inputCut[i]) {
          var inputQty = inputCut[i].split(regx);
          if (inputQty[1] != id || inputQty[2] != idAttribute) {
            input.val(input.val() + inputCut[i] + '-');
            namePack.val(namePack.val() + nameCut[i] + '¤');
          }
        }
      }

      var elem = $('.product-pack-item[data-product-id="' + id + '"][data-product-id-attribute="' + idAttribute + '"]');
      elem.remove();

      if ($('.product-pack-item').length === 0) {
        $('.pack-empty-warning').show();
      }
    }

    function getSelectedIds() {
      var reg = new RegExp('-', 'g');
      var regx = new RegExp('x', 'g');

      var input = $('#inputPackItems');

      if (typeof input.val() === 'undefined') {
        return '';
      }

      var inputCut = input.val().split(reg);

      var ints = [];

      for (var i = 0; i < inputCut.length; i += 1) {
        var inInts = [];
        if (inputCut[i]) {
          var inputQty = inputCut[i].split(regx);
          inInts[0] = inputQty[1];
          inInts[1] = inputQty[2];
        }
        ints[i] = inInts;
      }

      return ints;
    }
  };

  this.onReady = function () {
    self.bindPackEvents();
  };
}();

window.product_tabs.Images = new function () {
  this.onReady = function () {
    window.displayFlags(window.languages, window.id_language, window.allowEmployeeFormLang);
  };
}();

window.product_tabs.Features = new function () {
  this.onReady = function () {
    window.displayFlags(window.languages, window.id_language, window.allowEmployeeFormLang);
  };
}();

window.product_tabs.Quantities = new function () {
  var self = this;
  this.ajaxCall = function (data) {
    data.ajaxProductQuantity = 1;
    data.id_product = window.id_product;
    data.token = token;
    data.ajax = 1;
    data.controller = 'AdminProducts';
    data.action = 'productQuantity';

    $.ajax({
      type: 'POST',
      url: 'ajax-tab.php',
      data: data,
      dataType: 'json',
      async: true,
      beforeSend: function () {
        $('.product_quantities_button').attr('disabled', 'disabled');
      },
      complete: function () {
        $('.product_quantities_button').removeAttr('disabled');
      },
      success: function (msg) {
        if (msg.error) {
          showErrorMessage(msg.error);
          return;
        }
        showSuccessMessage(window.quantities_ajax_success);
      },
      error: function (jqXHR, textStatus, errorThrown) {
        if (jqXHR.status && (textStatus === 'error' || errorThrown)) {
          showErrorMessage(textStatus + ': ' + errorThrown);
        }
      }
    });
  };

  this.refreshQtyAvailabilityForm = function () {
    var $availableQuantity = $('.available_quantity');
    if ($('#depends_on_stock_0').prop('checked')) {
      $availableQuantity.find('input').show();
      $availableQuantity.find('span').hide();
    } else {
      $availableQuantity.find('input').hide();
      $availableQuantity.find('span').show();
    }
  };

  this.onReady = function () {
    $('#available_date').datepicker({
      prevText: '',
      nextText: '',
      dateFormat: 'yy-mm-dd'
    });

    $('.depends_on_stock').click(function (e) {
      self.refreshQtyAvailabilityForm();
      self.ajaxCall({ actionQty: 'depends_on_stock', value: $(this).val() });
      if (!$(this).val()) {
        $('.available_quantity input').trigger('change');
      }
    });

    $('.advanced_stock_management').click(function () {
      var val = 0;
      if ($(this).prop('checked')) {
        val = 1;
      }

      self.ajaxCall({ actionQty: 'advanced_stock_management', value: val });
      if (parseInt(val, 10) === 1) {
        $(this).val(1);
        $('#depends_on_stock_1').attr('disabled', false);
      } else {
        $(this).val(0);
        $('#depends_on_stock_1').attr('disabled', true);
        $('#depends_on_stock_0').attr('checked', true);
        self.ajaxCall({ actionQty: 'depends_on_stock', value: 0 });
        self.refreshQtyAvailabilityForm();
      }
      self.refreshQtyAvailabilityForm();
    });

    $('.available_quantity').find('input').change(function () {
      self.ajaxCall({ actionQty: 'set_qty', id_product_attribute: $(this).parent().attr('id').split('_')[1], value: $(this).val() });
    });

    $('.out_of_stock').click(function () {
      self.refreshQtyAvailabilityForm();
      self.ajaxCall({ actionQty: 'out_of_stock', value: $(this).val() });
    });
    if (window.display_multishop_checkboxes) {
      window.ProductMultishop.checkAllQuantities();
    }

    $('.pack_stock_type').click(function () {
      self.refreshQtyAvailabilityForm();
      self.ajaxCall({ actionQty: 'pack_stock_type', value: $(this).val() });
    });

    self.refreshQtyAvailabilityForm();
  };
}();

window.product_tabs.Suppliers = new function () {
  var self = this;

  this.manageDefaultSupplier = function () {
    var defaultIsSet = false;
    var radioButtons = $('input[name="default_supplier"]');

    for (var i = 0; i < radioButtons.length; i += 1) {
      var item = $(radioButtons[i]);

      if (item.is(':disabled')) {
        if (item.is(':checked')) {
          item.removeAttr('checked');
        }
      }

      if (item.is(':checked')) {
        defaultIsSet = true;
      }
    }

    if (!defaultIsSet) {
      for (var j = 0; j < radioButtons.length; j += 1) {
        var $item = $(radioButtons[j]);

        if (!$item.is(':disabled')) {
          $item.attr('checked', true);
        }
      }
    }
  };

  this.onReady = function () {
    $('.supplierCheckBox').on('click', function () {
      var check = $(this);
      var checkbox = $('#default_supplier_' + check.val());

      if (this.checked) {
        // enable default radio button associated
        checkbox.removeAttr('disabled');
      } else {
        // disable default radio button associated
        checkbox.attr('disabled', true);
      }

      // manage default supplier check
      self.manageDefaultSupplier();
    });
  };
}();

window.product_tabs.VirtualProduct = new function () {
  this.onReady = function () {
    $('.datepicker').datepicker({
      prevText: '',
      nextText: '',
      dateFormat: 'yy-mm-dd'
    });

    $('#is_virtual_file_on').on('click', function () {
      $('#is_virtual_file_product').show();
    });
    $('#is_virtual_file_off').on('click', function () {
      $('#is_virtual_file_product').hide();
    });

    // Bind file deletion
    $(('#product-tab-content-VirtualProduct')).delegate('a.delete_virtual_product', 'click', function (e) {
      e.preventDefault();
      if (confirm(window.delete_this_file)) {
        if (!$('#virtual_product_id').val()) {
          $('#upload_input').show();
          $('#virtual_product_name').val('');
          $('#virtual_product_file').val('');
          $('#upload-confirmation').hide().find('span').remove();
        } else {
          var object = this;
          window.ajaxAction(this.href, 'deleteVirtualProduct', function () {
            $(object).closest('tr').remove();
            $('#upload_input').show();
            $('#virtual_product_name').val('');
            $('#virtual_product_file').val('');
            $('#virtual_product_id').remove();
          });
        }
      }
    });
  };
}();

window.product_tabs.Warehouses = new function () {
  this.onReady = function () {
    $('.check_all_warehouse').click(function () {
      // get all checkboxes of current warehouse
      var checkboxes = $('input[name*="' + $(this).val() + '"]');
      var checked = false;

      for (var i = 0; i < checkboxes.length; i++) {
        var item = $(checkboxes[i]);

        if (item.is(':checked')) {
          item.removeAttr('checked');
        } else {
          item.attr('checked', true);
          checked = true;
        }
      }

      if (checked) {
        $(this).find('i').removeClass('icon-check-sign').addClass('icon-check-empty');
      } else {
        $(this).find('i').removeClass('icon-check-empty').addClass('icon-check-sign');
      }
    });
  };
}();

/**
 * Update the product image list position buttons
 *
 * @param {object} imageTable
 *
 * @return {undefined}
 */
function refreshImagePositions(imageTable) {
  imageTable.find('tbody tr').each(function (i, el) {
    $(el).find('td.positionImage').html(i + 1);
  });
  imageTable.find('tr td.dragHandle a:hidden').show();
  imageTable.find('tr td.dragHandle:first a:first').hide();
  imageTable.find('tr td.dragHandle:last a:last').hide();
}

/**
 * Generic ajax call for actions expecting a json return
 *
 * @param {string} url
 * @param {string} action
 * @param {function} successCallback called if the return status is 'ok' (optional)
 * @param {function} failureCallback called if the return status is not 'ok' (optional)
 *
 * @return {undefined}
 */
function ajaxAction(url, action, successCallback, failureCallback) {
  $.ajax({
    url: url,
    data: {
      id_product: window.id_product,
      action: action,
      ajax: true
    },
    dataType: 'json',
    context: this,
    success: function (data) {
      if (data.status === 'ok') {
        showSuccessMessage(data.confirmations);
        if (typeof successCallback === 'function') {
          successCallback();
        }
      } else {
        showErrorMessage(data.error);
        if (typeof failureCallback === 'function') {
          failureCallback();
        }
      }
    },
    error: function () {
      showErrorMessage(('[TECHNICAL ERROR]'));
    }
  });
}

window.ProductMultishop = new function () {
  var self = this;
  this.load_tinymce = {};

  this.checkField = function (checked, id, type) {
    checked = !checked;
    var $id = $('#' + id);
    switch (type) {
    case 'tinymce' :
      $id.attr('disabled', checked);
      if (typeof self.load_tinymce[id] === 'undefined') {
        self.load_tinymce[id] = checked;
      } else if (checked) {
        tinyMCE.get(id).hide();
      } else {
        tinyMCE.get(id).show();
      }
      break;
    case 'radio' :
      $('input[name=\'' + id + '\']').attr('disabled', checked);
      break;
    case 'show_price' :
      if ($('input[name=\'available_for_order\']').prop('checked')) {
        checked = true;
      }
      $('input[name=\'' + id + '\']').attr('disabled', checked);
      break;
    case 'price' :
      $('#priceTE').attr('disabled', checked);
      $('#priceTI').attr('disabled', checked);
      break;
    case 'unit_price' :
      $('#unit_price').attr('disabled', checked);
      $('#unity').attr('disabled', checked);
      break;
    case 'attribute_price_impact' :
      $('#attribute_price_impact').attr('disabled', checked);
      $('#attribute_price').attr('disabled', checked);
      $('#attribute_priceTI').attr('disabled', checked);
      break;
    case 'category_box' :
      $('#' + id + ' input[type=checkbox]').attr('disabled', checked);
      if (!checked) {
        $('#check-all-' + id).removeAttr('disabled');
        $('#uncheck-all-' + id).removeAttr('disabled');
      } else {
        $('#check-all-' + id).attr('disabled', 'disabled');
        $('#uncheck-all-' + id).attr('disabled', 'disabled');
      }
      break;
    case 'attribute_weight_impact' :
      $('#attribute_weight_impact').attr('disabled', checked);
      $('#attribute_weight').attr('disabled', checked);
      break;
    case 'attribute_unit_impact' :
      $('#attribute_unit_impact').attr('disabled', checked);
      $('#attribute_unity').attr('disabled', checked);
      break;
    case 'seo_friendly_url':
      $id.attr('disabled', checked);
      $('#generate-friendly-url').attr('disabled', checked);
      break;
    case 'uploadable_files':
      $('input[name^=label_0_]').attr('disabled', checked);
      $id.attr('disabled', checked);
      break;
    case 'text_fields':
      $('input[name^=label_1_]').attr('disabled', checked);
      $id.attr('disabled', checked);
      break;
    default :
      $id.attr('disabled', checked);
      break;
    }
  };

  this.checkAllInformations = function () {
    window.ProductMultishop.checkField($('input[name=\'multishop_check[active]\']').prop('checked'), 'active', 'radio');
    window.ProductMultishop.checkField($('input[name=\'multishop_check[visibility]\']').prop('checked'), 'visibility');
    window.ProductMultishop.checkField($('input[name=\'multishop_check[available_for_order]\']').prop('checked'), 'available_for_order');
    window.ProductMultishop.checkField($('input[name=\'multishop_check[show_price]\']').prop('checked'), 'show_price', 'show_price');
    window.ProductMultishop.checkField($('input[name=\'multishop_check[online_only]\']').prop('checked'), 'online_only');
    window.ProductMultishop.checkField($('input[name=\'multishop_check[condition]\']').prop('checked'), 'condition');
    $.each(window.languages, function (k, v) {
      window.ProductMultishop.checkField($('input[name=\'multishop_check[name][' + v.id_lang + ']\']').prop('checked'), 'name_' + v.id_lang);
      window.ProductMultishop.checkField($('input[name=\'multishop_check[description_short][' + v.id_lang + ']\']').prop('checked'), 'description_short_' + v.id_lang, 'tinymce');
      window.ProductMultishop.checkField($('input[name=\'multishop_check[description][' + v.id_lang + ']\']').prop('checked'), 'description_' + v.id_lang, 'tinymce');
    });
  };

  this.checkAllPrices = function () {
    window.ProductMultishop.checkField($('input[name=\'multishop_check[wholesale_price]\']').prop('checked'), 'wholesale_price');
    window.ProductMultishop.checkField($('input[name=\'multishop_check[price]\']').prop('checked'), 'price', 'price');
    window.ProductMultishop.checkField($('input[name=\'multishop_check[id_tax_rules_group]\']').prop('checked'), 'id_tax_rules_group');
    window.ProductMultishop.checkField($('input[name=\'multishop_check[unit_price]\']').prop('checked'), 'unit_price', 'unit_price');
    window.ProductMultishop.checkField($('input[name=\'multishop_check[on_sale]\']').prop('checked'), 'on_sale');
    window.ProductMultishop.checkField($('input[name=\'multishop_check[ecotax]\']').prop('checked'), 'ecotax');
  };

  this.checkAllSeo = function () {
    $.each(window.languages, function (k, v) {
      window.ProductMultishop.checkField($('input[name=\'multishop_check[meta_title][' + v.id_lang + ']\']').prop('checked'), 'meta_title_' + v.id_lang);
      window.ProductMultishop.checkField($('input[name=\'multishop_check[meta_description][' + v.id_lang + ']\']').prop('checked'), 'meta_description_' + v.id_lang);
      window.ProductMultishop.checkField($('input[name=\'multishop_check[meta_keywords][' + v.id_lang + ']\']').prop('checked'), 'meta_keywords_' + v.id_lang);
      window.ProductMultishop.checkField($('input[name=\'multishop_check[link_rewrite][' + v.id_lang + ']\']').prop('checked'), 'link_rewrite_' + v.id_lang, 'seo_friendly_url');
    });
  };

  this.checkAllQuantities = function () {
    $.each(window.languages, function (k, v) {
      window.ProductMultishop.checkField($('input[name=\'multishop_check[minimal_quantity]\']').prop('checked'), 'minimal_quantity');
      window.ProductMultishop.checkField($('input[name=\'multishop_check[available_later][' + v.id_lang + ']\']').prop('checked'), 'available_later_' + v.id_lang);
      window.ProductMultishop.checkField($('input[name=\'multishop_check[available_now][' + v.id_lang + ']\']').prop('checked'), 'available_now_' + v.id_lang);
      window.ProductMultishop.checkField($('input[name=\'multishop_check[available_date]\']').prop('checked'), 'available_date');
    });
  };

  this.checkAllAssociations = function () {
    window.ProductMultishop.checkField($('input[name=\'multishop_check[id_category_default]\']').prop('checked'), 'id_category_default');
    window.ProductMultishop.checkField($('input[name=\'multishop_check[id_category_default]\']').prop('checked'), 'associated-categories-tree', 'category_box');
  };

  this.checkAllCustomization = function () {
    window.ProductMultishop.checkField($('input[name=\'multishop_check[uploadable_files]\']').prop('checked'), 'uploadable_files', 'uploadable_files');
    window.ProductMultishop.checkField($('input[name=\'multishop_check[text_fields]\']').prop('checked'), 'text_fields', 'text_fields');
  };

  this.checkAllCombinations = function () {
    window.ProductMultishop.checkField($('input[name=\'multishop_check[attribute_wholesale_price]\']').prop('checked'), 'attribute_wholesale_price');
    window.ProductMultishop.checkField($('input[name=\'multishop_check[attribute_price_impact]\']').prop('checked'), 'attribute_price_impact', 'attribute_price_impact');
    window.ProductMultishop.checkField($('input[name=\'multishop_check[attribute_weight_impact]\']').prop('checked'), 'attribute_weight_impact', 'attribute_weight_impact');
    window.ProductMultishop.checkField($('input[name=\'multishop_check[attribute_unit_impact]\']').prop('checked'), 'attribute_unit_impact', 'attribute_unit_impact');
    window.ProductMultishop.checkField($('input[name=\'multishop_check[attribute_ecotax]\']').prop('checked'), 'attribute_ecotax');
    window.ProductMultishop.checkField($('input[name=\'multishop_check[attribute_minimal_quantity]\']').prop('checked'), 'attribute_minimal_quantity');
    window.ProductMultishop.checkField($('input[name=\'multishop_check[available_date_attribute]\']').prop('checked'), 'available_date_attribute');
    window.ProductMultishop.checkField($('input[name=\'multishop_check[attribute_default]\']').prop('checked'), 'attribute_default');
  };
}();

var tabs_manager = new ProductTabsManager();
tabs_manager.setTabs(window.product_tabs);

$(document).ready(function () {
  // The manager schedules the onReady() methods of each tab to be called when the tab is loaded
  window.tabs_manager.init();
  window.updateCurrentText();
  var $linkRewrite = $('#name_' + window.id_lang_default + ',#link_rewrite_' + window.id_lang_default);
  $linkRewrite
    .on('change', function () {
      $(this).trigger('handleSaveButtons');
    });
  // bind that custom event
  $linkRewrite
    .on('handleSaveButtons', function () {
      handleSaveButtons();
    });

  // Pressing enter in an input field should not submit the form
  var $productForm = $('#product_form');
  $productForm.delegate('input', 'keypress', function (e) {
    var code = (e.keyCode ? e.keyCode : e.which);
    return parseInt(code, 10) !== 13;
  });

  $productForm.submit(function () {
    $('#selectedCarriers option').attr('selected', 'selected');
    $('#selectAttachment1 option').attr('selected', 'selected');
  });
});
