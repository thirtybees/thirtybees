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
 * @author    Thirty Bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017 Thirty Bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

// These variables are defined in the dashboard view.tpl
/* global window, jQuery, $, jAlert, dashboard_ajax_url, adminstats_ajax_url, no_results_translation, dashboard_use_push, read_more */

function refreshDashboard(moduleName, usePush, extra) {
  var moduleList = [];
  this.getWidget = function (idModule) {
    $.ajax({
      url: dashboard_ajax_url,
      data: {
        ajax: true,
        action: 'refreshDashboard',
        module: moduleList[idModule],
        dashboard_use_push: Number(usePush),
        extra: extra
      },
      // Ensure to get fresh data
      headers: { 'cache-control': 'no-cache' },
      cache: false,
      global: false,
      dataType: 'json',
      success: function (widgets) {
        $.each(widgets, function (widgetName) {
          $.each(widgets[widgetName], function (dataType) {
            window[dataType](widgetName, widgets[widgetName][dataType]);
          });
        });
        if (parseInt(dashboard_use_push, 10) === 1) {
          refreshDashboard(false, true);
        }
      },
      contentType: 'application/json'
    });
  };
  if (moduleName === false) {
    $('.widget').each(function () {
      moduleList.push($(this).attr('id'));
      if (!usePush) {
        $(this).addClass('loading');
      }
    });
  } else {
    moduleList.push(moduleName);
    if (!usePush) {
      $('#' + moduleName + ' section').each(function () {
        $(this).addClass('loading');
      });
    }
  }
  $.each(moduleList, function (idModule) {
    if (usePush && !$('#' + moduleList[idModule]).hasClass('allow_push')) {
      return;
    }
    self.getWidget(idModule);
  });
}

function setDashboardDateRange(action) {
  $('#datepickerFrom, #datepickerTo').parent('.input-group').removeClass('has-error');
  var data = 'ajax=true&action=setDashboardDateRange&submitDatePicker=true&' + $('#calendar_form').serialize() + '&' + action + '=1';
  $.ajax({
    url: adminstats_ajax_url,
    data: data,
    dataType: 'json',
    type: 'POST',
    success: function (jsonData) {
      if (!jsonData.has_errors) {
        refreshDashboard(false, false);
        $('#datepickerFrom').val(jsonData.date_from);
        $('#datepickerTo').val(jsonData.date_to);
      }
      else {
        $('#datepickerFrom, #datepickerTo').parent('.input-group').addClass('has-error');
      }
    }
  });
}

function data_value(widgetName, data) {
  $.each(data, function (idData) {
    $('#' + idData + ' ').html(data[idData]);
    $('#' + idData + ', #' + widgetName).closest('section').removeClass('loading');
  });
}

function data_trends(widgetName, data) {
  if (!$.isArray(data)) {
    return;
  }

  $.each(data, function (idData) {
    this.el = $('#' + idData);
    this.el.html(data[idData].value);
    if (data[idData].way === 'up') {
      this.el.parent().removeClass('dash_trend_down').removeClass('dash_trend_right').addClass('dash_trend_up');
    } else if (data[idData].way === 'down') {
      this.el.parent().removeClass('dash_trend_up').removeClass('dash_trend_right').addClass('dash_trend_down');
    } else {
      this.el.parent().removeClass('dash_trend_down').removeClass('dash_trend_up').addClass('dash_trend_right');
    }
    this.el.closest('section').removeClass('loading');
  });
}

function data_table(widgetName, data) {
  if (!$.isArray(data)) {
    return;
  }

  $.each(data, function (idData) {
    // fill header
    var tr = '<tr>';
    $.each(data[idData].header, function (header) {
      var head = data[idData].header[header];
      var th = '<th ' + (head.class ? ' class="' + head.class + '" ' : '' ) + ' ' + (head.id ? ' id="' + head.id + '" ' : '' ) + '>';
      th += (head.wrapper_start ? ' ' + head.wrapper_start + ' ' : '' );
      th += head.title;
      th += (head.wrapper_stop ? ' ' + head.wrapper_stop + ' ' : '' );
      th += '</th>';
      tr += th;
    });
    tr += '</tr>';
    $('#' + idData + ' thead').html(tr);

    // fill body
    $('#' + idData + ' tbody').html('');

    if (typeof data[idData].body === 'string') {
      $('#' + idData + ' tbody').html('<tr><td class="text-center" colspan="' + data[idData].header.length + '"><br/>' + data[idData].body + '</td></tr>');
    } else if (data[idData].body.length) {
      $.each(data[idData].body, function (idBodyContent) {
        tr = '<tr>';
        $.each(data[idData].body[idBodyContent], function (bodyContent) {
          var body = data[idData].body[idBodyContent][bodyContent];
          var td = '<td ' + (body.class ? ' class="' + body.class + '" ' : '') + ' ' + (body.id ? ' id="' + body.id + '" ' : '') + '>';
          td += (body.wrapper_start ? ' ' + body.wrapper_start + ' ' : '');
          td += body.value;
          td += (body.wrapper_stop ? ' ' + body.wrapper_stop + ' ' : '');
          td += '</td>';
          tr += td;
        });
        tr += '</tr>';
        $('#' + idData + ' tbody').append(tr);
      });
    } else {
      $('#' + idData + ' tbody').html('<tr><td class="text-center" colspan="' + data[idData].header.length + '">' + window.no_results_translation + '</td></tr>');
    }
  });
}

function data_chart(widgetName, charts) {
  $.each(charts, function (idChart) {
    window[charts[idChart].chart_type](widgetName, charts[idChart]);
  });
}

function data_list_small(widgetName, data) {
  $.each(data, function (idData) {
    $('#' + idData).html('');
    $.each(data[idData], function (item) {
      $('#' + idData).append('<li><span class="data_label">' + item + '</span><span class="data_value size_s">' + data[idData][item] + '</span></li>');
    });
    $('#' + idData + ', #' + widgetName).closest('section').removeClass('loading');
  });
}

function getBlogRss() {
  $.ajax({
    url: dashboard_ajax_url,
    data: {
      ajax: true,
      action: 'getBlogRss'
    },
    dataType: 'json',
    success: function (jsonData) {
      if (typeof jsonData !== 'undefined' && jsonData !== null && !jsonData.has_errors) {
        $.each(jsonData.rss, function (article) {
          var articleHtml = '<article><h4><a href="' + jsonData.rss[article].link + '" class="_blank" onclick="return !window.open(this.href);">' + jsonData.rss[article].title + '</a></h4><span class="dash-news-date text-muted">' + jsonData.rss[article].date + '</span><p>' + jsonData.rss[article].short_desc + ' <a href="' + jsonData.rss[article].link + '">' + read_more + '</a><p></article><hr/>';
          $('.dash_news .dash_news_content').append(articleHtml);
        });
      } else {
        $('.dash_news').hide();
      }
    }
  });
}

function toggleDashConfig(widget) {
  var funcName = widget + '_toggleDashConfig';
  if ($('#' + widget + ' section.dash_config').hasClass('hide')) {
    $('#' + widget + ' section').not('.dash_config').slideUp(500, function () {
      $('#' + widget + ' section.dash_config').fadeIn(500).removeClass('hide');
      if (typeof window[funcName] !== 'undefined') {
        window[funcName]();
      }
    });
  } else {
    $('#' + widget + ' section.dash_config').slideUp(500, function () {
      $('#' + widget + ' section').not('.dash_config').slideDown(500).removeClass('hide');
      $('#' + widget + ' section.dash_config').addClass('hide');
      if (typeof window[funcName] !== 'undefined') {
        window[funcName]();
      }
    });
  }
}

function bindSubmitDashConfig() {
  $('.submit_dash_config').on('click', function () {
    saveDashConfig($(this).closest('section.widget').attr('id'));
    return false;
  });
}

function bindCancelDashConfig() {
  $('.cancel_dash_config').on('click', function () {
    toggleDashConfig($(this).closest('section.widget').attr('id'));
    return false;
  });
}

function saveDashConfig(widgetName) {
  $('section#' + widgetName + ' .form-group').removeClass('has-error');
  $('#' + widgetName + '_errors').remove();
  var configs = '';

  $('#' + widgetName + ' form input, #' + widgetName + ' form textarea , #' + widgetName + ' form select').each(function () {
    if ($(this).attr('type') === 'radio' && !$(this).attr('checked')) {
      return;
    }
    configs += '&configs[' + $(this).attr('name') + ']=' + $(this).val();
  });

  var data = 'ajax=true&action=saveDashConfig&module=' + widgetName + configs + '&hook=' + $('#' + widgetName).closest('[id^=hook]').attr('id');

  $.ajax({
    url: dashboard_ajax_url,
    data: data,
    dataType: 'json',
    error: function (XMLHttpRequest, textStatus) {
      jAlert('TECHNICAL ERROR: \n\nDetails:\nError thrown: ' + XMLHttpRequest + '\n Text status: ' + textStatus);
    },
    success: function (jsonData) {
      if (!jsonData.has_errors) {
        var $widgetName = $('#' + widgetName);
        $widgetName.find('section').not('.dash_config').remove();
        $widgetName.append($(jsonData.widget_html).find('section').not('.dash_config'));
        refreshDashboard(widgetName);
        toggleDashConfig(widgetName);
      } else {
        window.errors_str = '<div class="alert alert-danger" id="' + widgetName + '_errors">';
        $.each(jsonData.errors, function (error) {
          window.errors_str += jsonData.errors[error] + '<br/>';
          $('#' + error).closest('.form-group').addClass('has-error');
        });
        window.errors_str += '</div>';
        $('section#' + widgetName + '_config header').after(window.errors_str);
        window.errors_str += '</div>';
      }
    }
  });
}

$(document).ready(function () {
  $('#calendar_form input[type="submit"]').on('click', function (elt) {
    elt.preventDefault();
    setDashboardDateRange(elt.currentTarget.name);
  });

  refreshDashboard(false, false);
  getBlogRss();
  bindSubmitDashConfig();
  bindCancelDashConfig();

  $('#page-header-desc-configuration-switch_demo').tooltip().click(function () {
    $.ajax({
      url: dashboard_ajax_url,
      data: {
        ajax: true,
        action: 'setSimulationMode',
        PS_DASHBOARD_SIMULATION: $(this).find('i').hasClass('process-icon-toggle-on') ? 0 : 1
      },
      success: function () {
        if ($('#page-header-desc-configuration-switch_demo i').hasClass('process-icon-toggle-on')) {
          $('#page-header-desc-configuration-switch_demo i').removeClass('process-icon-toggle-on').addClass('process-icon-toggle-off');
        } else {
          $('#page-header-desc-configuration-switch_demo i').removeClass('process-icon-toggle-off').addClass('process-icon-toggle-on');
        }
        refreshDashboard(false, false);
      }
    });
  });
});
