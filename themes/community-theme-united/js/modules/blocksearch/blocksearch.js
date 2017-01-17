$(function() {

  var module = {
    blockType:   window.blocksearch_type,
    searchUrl:   window.search_url,
    ajaxSearch: !!window.ajaxsearch,
    instantSearch: !!window.instantsearch
  };
  var id_lang    = window.id_lang || 1;
  var $input     = $('#search_query_top');
  var blockWidth = $input.parents('form').outerWidth();

  if (module.instantSearch) {
    bindInstantSearch();
  } else if (module.ajaxSearch) {
    bindAjaxSearch();
  }

  function bindAjaxSearch() {
    $input.autocomplete(module.searchUrl, {
        resultsClass: 'ac_results blocksearch',
        minChars: 3,
        max: 10,
        width: blockWidth,
        selectFirst: false,
        scroll: false,
        dataType: 'json',
        formatItem: function(data, i, max, value, term) {
          return value;
        },
        parse: function(data) {
          return data.map(function(product) {
            return {
              data: product,
              value: product.cname + ' > ' + product.pname
            };
          });
        },
        extraParams: {
          ajaxSearch: 1,
          id_lang: id_lang
        }
      })
      .result(function(event, data, formatted) {
        $input.val(data.pname);
        window.location.href = data.product_link;
      });
  }

  function bindInstantSearch() {

    var buffer = null;
    var latestQuery = '';

    $input.on('keyup', function() {
      clearTimeout(buffer);
      buffer = setTimeout(checkInput, 450);
    });

    $(document).on('click', '.js-close-instant-search', function(e) {
      e.preventDefault();
      $input.val('');
      closeInstantResults();
    });

    function checkInput() {
      var query = $input.val();

      // Ignore keyup events from up/down keys, etc.
      if (query == latestQuery) {
        return;
      }

      latestQuery = query;

      if (query.length < 4) {
        closeInstantResults();
        return;
      }

      $('#center_column').addClass('loading-overlay');
      fetchInstantResults(query, function(html) {
        if (query == latestQuery) {
          showInstantResults(html);
        }
      });
    }

    function fetchInstantResults(query, cb) {
      $.ajax({
        url: module.searchUrl + '?rand=' + new Date().getTime(),
        data: {
          instantSearch: 1,
          id_lang: id_lang,
          q: query
        },
        dataType: 'html',
        type: 'POST',
        headers: {'cache-control': 'no-cache'},
        async: true,
        cache: false,
        success: cb
      });
    }

    function showInstantResults(html) {

      closeInstantResults();

      $('#center_column').removeClass('loading-overlay').attr('id', 'old_center_column');
      var $oldCenterColumn = $('#old_center_column');

      $oldCenterColumn.after(
        '<div id="center_column" class="' + $oldCenterColumn.attr('class') + '">' + html + '</div>'
      ).hide();

      // Button override
      ajaxCart.overrideButtonsInThePage();
    }

    function closeInstantResults() {
      var $oldCenterColumn = $('#old_center_column');
      if ($oldCenterColumn.length > 0) {
        $('#center_column').remove();
        $oldCenterColumn.attr('id', 'center_column').removeClass('loading-overlay').show();
      }
    }

  }

});
