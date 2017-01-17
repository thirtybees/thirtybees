/* global quickView, page_name, FancyboxI18nClose, FancyboxI18nNext, FancyboxI18nPrev, highDPI, request, url */

var PrestaShop = (function() {

  function showWindowAlert(msg, title) {
    var content = title ? title + '\n\n' : '';
    content +=  $.isArray(msg) ? msg.join('\n') : msg;
    alert(content);
  }

  function showFancyboxAlert(msg, title, wrapperClass) {
    if ($.isArray(msg)) {
      msg = '<ul><li>' + msg.join('</li><li>') + '</li><ul>';
    }
    $.fancybox.open([{
      type: 'inline',
      autoScale: true,
      minHeight: 30,
      content: '<div class="fancybox-error' + (wrapperClass ? ' ' + wrapperClass : '') + '">' +
                (title ? '<p><b>' + title + '</b></p>' : '') + msg + '</div>'
    }], {
      padding: 0
    });
  }

  return {
    showError: function(msg, title) {
      if (!!$.prototype.fancybox) {
        showFancyboxAlert(msg, title);
      } else {
        showWindowAlert(msg, title);
      }
    },
    showSuccess: function(msg, title) {
      if (!!$.prototype.fancybox) {
        showFancyboxAlert(msg, title, 'fancybox-success');
      } else {
        showWindowAlert(msg, title);
      }
    }
  };

})();

$(function() {

  var touch = !!isTouchDevice();
  $('body').toggleClass('touch', touch).toggleClass('no-touch', !touch);

  highdpiInit();

  if (typeof quickView !== 'undefined' && quickView) {
    quick_view();
  }

  if (typeof page_name != 'undefined' && !in_array(page_name, ['index', 'product'])) {
    bindGrid();

    $(document).on('change', '.selectProductSort', function() {
      var order = $(this).val().split(':');
      var params = url('?') || {};
      params.orderby = '' + order[0];
      params.orderway = '' + order[1];
      window.location.search = $.param(params);
    });

    $(document).on('change', 'select[name="n"]', function() {
      $(this.form).submit();
    });

    $(document).on('change', 'select[name="currency_payment"]', function() {
      setCurrency($(this).val());
    });
  }

  // Make image responsive in rich description, prevent them from overflowing their container
  $('.rte img').addClass('img-responsive');

  $(document).on('change', 'select[name="manufacturer_list"], select[name="supplier_list"]', function() {
    if (this.value != '') {
      location.href = this.value;
    }
  });

  $(document).on('click', '.back', function(e) {
    e.preventDefault();
    history.back();
  });

  jQuery.curCSS = jQuery.css;
  if (!!$.prototype.cluetip) {
    $('a.cluetip').cluetip({
      local: true,
      cursor: 'pointer',
      dropShadow: false,
      dropShadowSteps: 0,
      showTitle: false,
      tracking: true,
      sticky: false,
      mouseOutClose: true,
      fx: {
        open:       'fadeIn',
        openSpeed:  'fast'
      }
    }).css('opacity', 0.8);
  }

  if (typeof(FancyboxI18nClose) !== 'undefined' && typeof(FancyboxI18nNext) !== 'undefined' && typeof(FancyboxI18nPrev) !== 'undefined' && !!$.prototype.fancybox) {
    $.extend($.fancybox.defaults.tpl, {
      closeBtn: '<a title="' + FancyboxI18nClose + '" class="fancybox-item fancybox-close" href="javascript:;"></a>',
      next: '<a title="' + FancyboxI18nNext + '" class="fancybox-nav fancybox-next" href="javascript:;"><span></span></a>',
      prev: '<a title="' + FancyboxI18nPrev + '" class="fancybox-nav fancybox-prev" href="javascript:;"><span></span></a>'
    });
  }

});

function highdpiInit() {
  if (typeof highDPI === 'undefined') {
    return;
  }

  if (highDPI && $('.replace-2x').css('font-size') == '1px') {
    var els = $('img.replace-2x').get();
    for (var i = 0; i < els.length; i++) {
      src = els[i].src;
      extension = src.substr((src.lastIndexOf('.') + 1));
      src = src.replace('.' + extension, '2x.' + extension);

      var img = new Image();
      img.src = src;
      els[i].src = img.height != 0 ? src : els[i].src;
    }
  }
}

function quick_view() {
  $(document).on('click', '.quick-view', function(e) {
    e.preventDefault();
    var url = this.rel;
    var anchor = '';

    if (url.indexOf('#') != -1) {
      anchor = url.substring(url.indexOf('#'), url.length);
      url = url.substring(0, url.indexOf('#'));
    }

    url += (url.indexOf('?') != -1) ? '&' : '?';

    if (!!$.prototype.fancybox) {
      $.fancybox({
        'padding':  0,
        'width':    1087,
        'height':   610,
        'type':     'iframe',
        'href':     url + 'content_only=1' + anchor
      });
    }
  });
}

function bindGrid() {
  var storage = false;
  if (typeof(getStorageAvailable) !== 'undefined') {
    storage = getStorageAvailable();
  }
  if (!storage) {
    return;
  }

  var view = $.totalStorage('display');
  display(view);

  $(document).on('click', '#grid', function(e) {
    e.preventDefault();
    display('grid');
  });

  $(document).on('click', '#list', function(e) {
    e.preventDefault();
    display('list');
  });
}

function display(layoutType) {
  var grid = layoutType == 'grid';
  $('.product_list').toggleClass('grid', grid).toggleClass('list', !grid);
  $('#list').toggleClass('selected active', !grid);
  $('#grid').toggleClass('selected active', grid);
  $.totalStorage('display', grid ? 'grid' : 'list');
}

var touchDevice = null;
function isTouchDevice() {
  if (touchDevice === null) {
    var userAgent = navigator.userAgent || navigator.vendor || window.opera;
    touchDevice = browserHasTouchEvents() && isMobileBrowser(userAgent);
  }
  return touchDevice;
}

function browserHasTouchEvents() {
  return 'ontouchstart' in window ||
    navigator.maxTouchPoints > 0 ||
    navigator.msMaxTouchPoints > 0 ||
    'onmsgesturechange' in window ||
    (window.DocumentTouch && document instanceof DocumentTouch);
}

function isMobileBrowser(userAgent) {
  // window.isMobile - corresponds both to PrestaShop mobile settings and Mobile_Detect lib results, cannot be fully used on it's own
  return window.isMobile ||
    /(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(userAgent) ||
    /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(userAgent.substr(0,4));
}
