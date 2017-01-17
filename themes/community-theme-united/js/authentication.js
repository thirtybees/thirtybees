$(function() {
  $(document).on('submit', '#create-account_form', function(e) {
    e.preventDefault();
    $(this).addClass('loading-overlay');
    submitFunction();
  });

  $('#login_form').on('submit', function() {
    $(this).addClass('loading-overlay');
  });

  $('.is_customer_param').hide();
});

function submitFunction() {
  $('#create_account_error').html('').hide();
  $.ajax({
    type: 'POST',
    url: baseUri + '?rand=' + new Date().getTime(),
    async: true,
    cache: false,
    dataType: 'json',
    headers: {'cache-control': 'no-cache'},
    data:
    {
      controller: 'authentication',
      SubmitCreate: 1,
      ajax: true,
      email_create: $('#email_create').val(),
      back: $('input[name=back]').val(),
      token: token
    },
    success: function(jsonData) {
      if (jsonData.hasError) {
        var errors = '';
        for (error in jsonData.errors)
          //IE6 bug fix
          if (error != 'indexOf')
            errors += '<li>' + jsonData.errors[error] + '</li>';
        $('#create_account_error').html('<ol>' + errors + '</ol>').show();
        $('#create-account_form').removeClass('loading-overlay');
      } else {
        // adding a div to display a transition
        $('#center_column').html('<div id="noSlide">' + $('#center_column').html() + '</div>');
        $('#noSlide').fadeOut('slow', function() {
          $('#noSlide').html(jsonData.page);
          $(this).fadeIn('slow', function() {
            if (typeof bindStateInputAndUpdate !== 'undefined') {
              bindStateInputAndUpdate();
            }
          });
        });
      }
    },
    error: function(XMLHttpRequest, textStatus, errorThrown) {
      PrestaShop.showError(
        'TECHNICAL ERROR: unable to load form.\n\nDetails:\nError thrown: ' +
        XMLHttpRequest + '\n' + 'Text status: ' + textStatus
      );
    }
  });
}
