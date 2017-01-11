$(function() {

  if (!!$.prototype.fancybox) {
    $('#send_friend_button').fancybox({
      'hideOnContentClick': false
    });
  }

  $('#send_friend_form_content').find('.closefb').on('click', function(e) {
    $.fancybox.close();
    e.preventDefault();
  });

  var $alert = $('#send_friend_form_error');

  $('#sendEmail').on('click', function() {
    var name = $('#friend_name').val();
    var email = $('#friend_email').val();
    $alert.hide();
    if (name && email && !isNaN(id_product)) {
      $.ajax({
        url: baseDir + 'modules/sendtoafriend/sendtoafriend_ajax.php?rand=' + new Date().getTime(),
        type: 'POST',
        headers: {'cache-control': 'no-cache'},
        data: {
          action: 'sendToMyFriend',
          secure_key: stf_secure_key,
          name: name,
          email: email,
          id_product: id_product
        },
        dataType: 'json',
        success: function(result) {
          $.fancybox.close();
          fancyMsgBox((result ? stf_msg_success : stf_msg_error), stf_msg_title);
        }
      });
    } else {
      $alert.show().text(stf_msg_required);
    }
  });

});
