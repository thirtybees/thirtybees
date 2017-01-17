$(function() {

  $('#blocknewsletter').find('form').on('submit', function() {
    $(this).addClass('loading-overlay');
  });

  $('#newsletter-input').on({
    focus: function() {
      if ($(this).val() == placeholder_blocknewsletter || $(this).val() == msg_newsl) {
        $(this).val('');
      }
    },
    blur: function() {
      if ($(this).val() == '') {
        $(this).val(placeholder_blocknewsletter);
      }
    }
  });

  var alertClass = 'alert alert-danger';
  if (typeof nw_error != 'undefined' && !nw_error) {
    alertClass = 'alert alert-success';
  }

  if (typeof msg_newsl != 'undefined' && msg_newsl) {
    var $cols =  $('#columns');
    $cols.prepend('<div class="' + alertClass + '">' + alert_blocknewsletter + '</div>');
    $('html, body').animate({
      scrollTop: $cols.offset().top
    }, 'slow');
  }

});
