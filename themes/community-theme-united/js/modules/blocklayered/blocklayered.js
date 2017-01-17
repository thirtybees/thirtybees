/* global param_product_url */

var ajaxQueries = [];
var sliderList = [];
var slidersInit = false;

$(function() {
  cancelFilter();

  // Click on color
  $(document).on('click', '#layered_form input[type=button], #layered_form label.layered_color', function() {
    var $hiddenInput = $('input[name=' + $(this).attr('name') + '][type=hidden]');
    if (!$hiddenInput.length) {
      $('<input />').attr('type', 'hidden').attr('name', $(this).attr('name')).val($(this).data('rel')).appendTo('#layered_form');
    } else {
      $hiddenInput.remove();
    }
    reloadContent(true);
  });

  // @TODO Click on label
  $(document).on('click', '#layered_form input[type=checkbox]', function() {
    reloadContent(true);
  });

  // Doesn't work with document element
  $('body').on('change', '#layered_form select, #layered_form input[type=radio]', function() {
    reloadContent(true);
  });

  // Changing content of an input text
  $(document).on('keyup', '#layered_form input.layered_input_range', function() {

    if ($(this).attr('timeout_id')) {
      window.clearTimeout($(this).attr('timeout_id'));
    }

    // IE Hack, setTimeout do not accept the third parameter
    var reference = this;

    $(this).attr('timeout_id', window.setTimeout(function(it) {
      if (!$(it).attr('id')) {
        it = reference;
      }

      var filter = $(it).attr('id').replace(/^layered_(.+)_range_.*$/, '$1');

      var $filterRangeMin = $('#layered_' + filter + '_range_min');
      var $filterRangeMax = $('#layered_' + filter + '_range_max');

      var value_min = parseInt($filterRangeMin.val()) || 0;
      $filterRangeMin.val(value_min);

      var value_max = parseInt($filterRangeMax.val()) || 0;
      $filterRangeMax.val(value_max);

      if (value_max < value_min) {
        $filterRangeMin.val($(it).val());
        $filterRangeMax.val($(it).val());
      }
      reloadContent();

    }, 500, this));
  });

  $(document).on('click', '#layered_block_left .radio', function() {
    var name = $(this).attr('name');
    $.each($(this).parent().parent().find('input[type=button]'), function(it, item) {
      if ($(item).hasClass('on') && $(item).attr('name') != name)
        $(item).click();
    });
    return true;
  });

  // Click on label
  $(document).on('click', '#layered_block_left label:not(.layered_color) a', function(e) {
    e.preventDefault();
    var disable = $(this).parent().parent().find('input').attr('disabled');
    if (disable == '' || typeof(disable) == 'undefined' || disable == false) {
      $(this).parent().parent().find('input').click();
    }
  });

  // Global var
  window.layered_hidden_list = {};

  $(document).on('click', '.hide-action', function() {
    var id = $(this).closest('ul').attr('id');
    layered_hidden_list[id] = !layered_hidden_list[id];
    hideFilterValueAction($(this));
  });

  $('.hide-action').each(function() {
    hideFilterValueAction($(this));
  });

  $(document).off('change', '.selectProductSort').on('change', '.selectProductSort', function() {
    $('.selectProductSort').val($(this).val());
    if ($('#layered_form').length > 0) {
      reloadContent('forceSlide');
    }
  });

  $(document).off('change', 'select[name="n"]').on('change', 'select[name="n"]', function() {
    $('select[name=n]').val($(this).val());
    reloadContent('forceSlide');
  });

  paginationButton(false);
  initLayered();
});

function initFilters() {
  if (typeof filters !== 'undefined') {
    for (var key in filters) {
      if (filters.hasOwnProperty(key)) {
        var filter = filters[key];
      }

      if (typeof filter.slider !== 'undefined' && parseInt(filter.filter_type) == 0) {
        var filterRange = parseInt(filter.max) - parseInt(filter.min);
        var step = filterRange / 100;

        if (step > 1) {
          step = parseInt(step);
        }

        var sliderOptions = {
          range: true,
          step: step,
          min: parseInt(filter.min),
          max: parseInt(filter.max),
          values: [filter.values[0], filter.values[1]],
          slide: function(event, ui) {
            stopAjaxQuery();

            var from, to;
            if (parseInt($(event.target).data('format')) < 5) {
              from = formatCurrency(ui.values[0], parseInt($(event.target).data('format')), $(event.target).data('unit'));
              to   = formatCurrency(ui.values[1], parseInt($(event.target).data('format')), $(event.target).data('unit'));
            } else {
              from = ui.values[0] + $(event.target).data('unit');
              to   = ui.values[1] + $(event.target).data('unit');
            }

            $('#layered_' + $(event.target).data('type') + '_range').html(from + ' - ' + to);
          },
          stop: function() {
            reloadContent(true);
          }
        };

        addSlider(filter.type, sliderOptions, filter.unit, parseInt(filter.format));

      } else if (typeof filter.slider !== 'undefined' && parseInt(filter.filter_type) == 1) {
        $('#layered_' + filter.type + '_range_min').attr('limitValue', filter.min);
        $('#layered_' + filter.type + '_range_max').attr('limitValue', filter.max);
      }

      $('.layered_' + filter.type).show();
    }
  }
}

function hideFilterValueAction($toggle) {
  var $list  = $toggle.closest('ul');
  var listId = $list.attr('id');
  var expand = !!layered_hidden_list[listId];
  $list.find('.hiddable').toggle(expand);
  $list.find('.hide-action.less').toggle(expand);
  $list.find('.hide-action.more').toggle(!expand);
}

function addSlider(type, data, unit, format) {
  sliderList.push({
    type: type,
    data: data,
    unit: unit,
    format: format
  });
}

function initSliders() {
  $(sliderList).each(function(i, slider) {

    var $slider      = $('#layered_' + slider['type'] + '_slider');
    var $sliderRange = $('#layered_' + slider['type'] + '_range');

    $slider.slider(slider['data']);

    var from = '';
    var to = '';

    switch (slider['format']) {
      case 1:
      case 2:
      case 3:
      case 4:
        from = formatCurrency($slider.slider('values', 0), slider['format'], slider['unit']);
        to   = formatCurrency($slider.slider('values', 1), slider['format'], slider['unit']);
        break;
      case 5:
        from = $slider.slider('values', 0) + slider['unit'];
        to   = $slider.slider('values', 1) + slider['unit'];
        break;
    }
    $sliderRange.html(from + ' - ' + to);
  });
}

function initLayered() {
  initFilters();
  initSliders();
  initLocationChange();
  updateProductUrl();
  if (window.location.href.split('#').length == 2 && window.location.href.split('#')[1] != '') {
    var params = window.location.href.split('#')[1];
    reloadContent('&selected_filters=' + params);
  }
}

function paginationButton(nbProductsIn, nbProductOut) {
  if (typeof(current_friendly_url) == 'undefined') {
    current_friendly_url = '#';
  }

  $('.content_sortPagiBar .pagination a').each(function() {
    var page;
    if ($(this).attr('href').search(/(\?|&)p=/) == -1) {
      page = 1;
    } else {
      page = parseInt($(this).attr('href').replace(/^.*(\?|&)p=(\d+).*$/, '$2'));
    }

    var location = window.location.href.replace(/#.*$/, '');
    $(this).attr('href', location + current_friendly_url.replace(/\/page-(\d+)/, '') + '/page-' + page);
  });

  //product count refresh
  if (nbProductsIn != false) {

    var $productCount = $('.product-count');

    if (isNaN(nbProductsIn) == 0) {
      // add variables

      var productCountRow = $productCount.html();
      var currentPageText = $('.content_sortPagiBar .pagination li.current').first().text();
      var nbPage = parseInt(currentPageText) || 1;
      var nb_products = nbProductsIn;
      var nbPerPage;
      var $option = $('#nb_item').find('option:selected');

      if ($option.length == 0) {
        nbPerPage = nb_products;
      } else {
        nbPerPage = parseInt($option.val());
      }

      nbPage = isNaN(nbPage) ? 1 : nbPage;
      nbPerPage * nbPage < nb_products ? productShowing = nbPerPage * nbPage : productShowing = (nbPerPage * nbPage - nb_products - nbPerPage * nbPage) * -1;
      nbPage == 1 ? productShowingStart = 1 : productShowingStart = nbPerPage * nbPage - nbPerPage + 1;

      //insert values into a .product-count
      productCountRow = $.trim(productCountRow);
      productCountRow = productCountRow.split(' ');

      var backStart = [];
      for (var row in productCountRow) {
        if (productCountRow.hasOwnProperty(row)) {
          if (parseInt(productCountRow[row]) + 0 == parseInt(productCountRow[row])) {
            backStart.push(row);
          }
        }
      }

      if (typeof backStart[0] !== 'undefined') {
        productCountRow[backStart[0]] = productShowingStart;
      }

      if (typeof backStart[1] !== 'undefined') {
        productCountRow[backStart[1]] = (nbProductOut != 'undefined') && (nbProductOut > productShowing) ? nbProductOut : productShowing;
      }

      if (typeof backStart[2] !== 'undefined') {
        productCountRow[backStart[2]] = nb_products;
      }

      if (typeof backStart[1] !== 'undefined' && typeof backStart[2] !== 'undefined' && productCountRow[backStart[1]] > productCountRow[backStart[2]]) {
        productCountRow[backStart[1]] = productCountRow[backStart[2]];
      }

      productCountRow = productCountRow.join(' ');
      $productCount.html(productCountRow).show();
    } else {
      $productCount.hide();
    }
  }
}

function cancelFilter() {
  $(document).on('click', '#enabled_filters a', function(e) {
    var rel = $(this).data('rel');
    var $el = $('#' + rel);
    var $rangeMin = $('#' + rel.replace(/_slider$/, '_range_min'));

    if (rel.search(/_slider$/) > 0) {
      if ($el.length) {
        $el.slider('values' , 0, $el.slider('option' , 'min'));
        $el.slider('values' , 1, $el.slider('option' , 'max'));
        $el.slider('option', 'slide')(0,{values: [$el.slider('option' , 'min'), $el.slider('option' , 'max')]});
      } else if ($rangeMin.length) {
        var $rangeMax = $('#' + rel.replace(/_slider$/, '_range_max'));
        $rangeMin.val($rangeMin.attr('limitValue'));
        $rangeMax.val($rangeMax.attr('limitValue'));
      }
    } else {
      if ($('option#' + rel).length) {
        $el.parent().val('');
      } else {
        $el.attr('checked', false);
        $('.' + rel).attr('checked', false);
        $('#layered_form').find('input[type=hidden][name=' + rel + ']').remove();
      }
    }
    reloadContent(true);
    e.preventDefault();
  });
}

function stopAjaxQuery() {
  if (typeof(ajaxQueries) == 'undefined')
    ajaxQueries = [];
  for (var i = 0; i < ajaxQueries.length; i++) {
    ajaxQueries[i].abort();
  }

  ajaxQueries = [];
}

function reloadContent(params_plus) {

  var $form = $('#layered_form');
  var $categoryProducts = $('#category-products');

  stopAjaxQuery();

  $form.addClass('loading-overlay');
  $categoryProducts.addClass('loading-overlay');

  var data = $form.serialize();
  $('.layered_slider').each(function() {
    var sliderStart = $(this).slider('values', 0);
    var sliderStop = $(this).slider('values', 1);
    if (typeof(sliderStart) == 'number' && typeof(sliderStop) == 'number')
      data += '&' + $(this).attr('id') + '=' + sliderStart + '_' + sliderStop;
  });

  $(['price', 'weight']).each(function(it, sliderType) {
    var $sliderRangeMin = $('#layered_' + sliderType + '_range_min');
    var $sliderRangeMax = $('#layered_' + sliderType + '_range_max');

    if ($sliderRangeMin.length) {
      data += '&layered_' + sliderType + '_slider=' + $sliderRangeMin.val() + '_' + $sliderRangeMax.val();
    }
  });

  $form.find('select option').each(function() {
    if ($(this).attr('id') && $(this).parent().val() == $(this).val()) {
      data += '&' + $(this).attr('id') + '=' + $(this).val();
    }
  });

  var $selectProductSort = $('.selectProductSort');

  if ($selectProductSort.length && $selectProductSort.val()) {
    var splitData;
    if ($selectProductSort.val().search(/orderby=/) > 0) {
      // Old ordering working
      splitData = [
        $selectProductSort.val().match(/orderby=(\w*)/)[1],
        $selectProductSort.val().match(/orderway=(\w*)/)[1]
      ];
    } else {
      // New working for default theme 1.4 and theme 1.5
      splitData = $selectProductSort.val().split(':');
    }
    data += '&orderby=' + splitData[0] + '&orderway=' + splitData[1];
  }

  var $selectN = $('select[name=n]:first');

  if ($selectN.length) {
    if (params_plus) {
      data += '&n=' + $selectN.val();
    } else {
      data += '&n=' + $('.showall').find('input[name="n"]').val();
    }
  }

  var slideUp = true;
  if (typeof params_plus === 'undefined' || !(typeof params_plus === 'string')) {
    params_plus = '';
    slideUp = false;
  }

  // Get nb items per page
  var n = '';
  if (params_plus) {
    var $opt = $('.js-per-page select[name=n]').find('option:selected');
    if ($opt.length) {
      n = '&n=' + $opt.val();
    }
  }

  ajaxQuery = $.ajax({
    type: 'GET',
    url: baseDir + 'modules/blocklayered/blocklayered-ajax.php',
    data: data + params_plus + n,
    dataType: 'json',
    cache: false, // @todo see a way to use cache and to add a timestamps parameter to refresh cache each 10 minutes for example
    success: function(result) {
      if (typeof(result) === 'undefined') {
        return;
      }

      if (result.meta_description != '') {
        $('meta[name="description"]').attr('content', result.meta_description);
      }

      if (result.meta_keywords != '') {
        $('meta[name="keywords"]').attr('content', result.meta_keywords);
      }

      if (result.meta_title != '') {
        $('title').html(result.meta_title);
      }

      if (result.heading != '') {
        $('.page-heading .cat-name').html(result.heading);
      }

      $('#layered_block_left').replaceWith(utf8_decode(result.filtersBlock));
      $('.category-product-count, .heading-counter').replaceWith(result.categoryCount);

      if (result.nbRenderedProducts == result.nbAskedProducts) {
        $('.js-per-page').hide();
      }

      if (result.productList) {
        $('.product_list').replaceWith(utf8_decode(result.productList));
      } else {
        $('.product_list').html('');
      }

      $categoryProducts.removeClass('loading-overlay');
      $form.removeClass('loading-overlay');

      if (result.pagination.search(/[^\s]/) >= 0) {
        var pagination = $('<div/>').html(result.pagination);
        var pagination_bottom = $('<div/>').html(result.pagination_bottom);

        if ($('<div/>').html(pagination).find('#pagination').length) {
          $('#pagination').show().replaceWith(pagination.find('#pagination'));
        } else {
          $('#pagination').hide();
        }

        if ($('<div/>').html(pagination_bottom).find('#pagination_bottom').length) {
          $('#pagination_bottom').show().replaceWith(pagination_bottom.find('#pagination_bottom'));
        } else {
          $('#pagination_bottom').hide();
        }

      } else {
        $('#pagination, #pagination_bottom').hide();
      }

      paginationButton(result.nbRenderedProducts, result.nbAskedProducts);
      ajaxLoaderOn = 0;

      // On submitting nb items form, reload with the good nb of items
      $('.showall form').on('submit', function(e) {
        e.preventDefault();
        var num = $(this).find('input[name="n"]').val();

        $('.content_sortPagiBar select[name="n"] option').each(function() {
          var $opt = $(this);
          if ($opt.val() == num) {
            $opt.attr('selected', true);
          } else {
            $opt.removeAttr('selected');
          }
        });

        // Reload products and pagination
        reloadContent();
      });

      if (typeof(ajaxCart) != 'undefined') {
        ajaxCart.overrideButtonsInThePage();
      }

      if (typeof(reloadProductComparison) == 'function') {
        reloadProductComparison();
      }

      filters = result.filters;
      initFilters();
      initSliders();

      current_friendly_url = result.current_friendly_url;

      // Currente page url
      if (typeof(current_friendly_url) === 'undefined') {
        current_friendly_url = '#';
      }

      // Get all sliders value
      $(['price', 'weight']).each(function(it, sliderType) {
        var $slider = $('#layered_' + sliderType + '_slider');
        var $sliderRangerMin = $('#layered_' + sliderType + '_range_min');
        var $sliderRangeMax = $('#layered_' + sliderType + '_range_max');

        if ($slider.length) {
          // Check if slider is enable & if slider is used
          if (typeof($slider.slider('values', 0)) != 'object') {
            if ($slider.slider('values', 0) != $slider.slider('option' , 'min') || $slider.slider('values', 1) != $slider.slider('option' , 'max')) {
              current_friendly_url += '/' + blocklayeredSliderName[sliderType] + '-' + $slider.slider('values', 0) + '-' + $slider.slider('values', 1);
            }
          }
        } else if ($sliderRangerMin.length) {
          current_friendly_url += '/' + blocklayeredSliderName[sliderType] + '-' + $sliderRangerMin.val() + '-' + $sliderRangeMax.val();
        }
      });

      if (history.pushState) {
        history.pushState(null, '', current_friendly_url);
      } else {
        window.location.hash = current_friendly_url;
      }

      if (current_friendly_url != '#/show-all') {
        $('.js-per-page').show();
      }

      lockLocationChecking = true;

      if (slideUp) {
        $.scrollTo('.product_list', 400);
      }
      updateProductUrl();

      $('.hide-action').each(function() {
        hideFilterValueAction($(this));
      });

      if (display instanceof Function) {
        var view = $.totalStorage('display');
        if (view && view != 'grid') {
          display(view);
        }
      }
    }
  });
  ajaxQueries.push(ajaxQuery);
}

function initLocationChange(func, time) {
  if (!time) {
    time = 500;
  }

  var current_friendly_url = getUrlParams();
  setInterval(function() {
    if (getUrlParams() != current_friendly_url && !lockLocationChecking) {
      // Don't reload page if current_friendly_url and real url match
      if (current_friendly_url.replace(/^#(\/)?/, '') == getUrlParams().replace(/^#(\/)?/, '')) {
        return;
      }

      lockLocationChecking = true;
      reloadContent('&selected_filters=' + getUrlParams().replace(/^#/, ''));
    } else {
      lockLocationChecking = false;
      current_friendly_url = getUrlParams();
    }
  }, time);
}

function getUrlParams() {
  if (typeof(current_friendly_url) === 'undefined') {
    current_friendly_url = '#';
  }

  var params = current_friendly_url;
  if (window.location.href.split('#').length == 2 && window.location.href.split('#')[1] != '') {
    params = '#' + window.location.href.split('#')[1];
  }

  return params;
}

function updateProductUrl() {
  // Adding the filters to URL product
  if (typeof(param_product_url) != 'undefined' && param_product_url != '' && param_product_url != '#') {
    $.each($('ul.product_list li.ajax_block_product .product_img_link,' +
      'ul.product_list li.ajax_block_product h5 a,' +
      'ul.product_list li.ajax_block_product .product_desc a,' +
      'ul.product_list li.ajax_block_product .lnk_view'), function() {
      $(this).attr('href', $(this).attr('href') + param_product_url);
    });
  }
}

/**
 * Copy of the php function utf8_decode()
 */
function utf8_decode(utfstr) {
  var res = '';
  for (var i = 0; i < utfstr.length;) {
    var c = utfstr.charCodeAt(i);
    var c1;
    var c2;

    if (c < 128) {
      res += String.fromCharCode(c);
      i++;
    } else if ((c > 191) && (c < 224)) {
      c1 = utfstr.charCodeAt(i + 1);
      res += String.fromCharCode(((c & 31) << 6) | (c1 & 63));
      i += 2;
    } else {
      c1 = utfstr.charCodeAt(i + 1);
      c2 = utfstr.charCodeAt(i + 2);
      res += String.fromCharCode(((c & 15) << 12) | ((c1 & 63) << 6) | (c2 & 63));
      i += 3;
    }
  }
  return res;
}
