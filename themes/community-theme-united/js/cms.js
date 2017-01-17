$(function() {
  if (typeof ad !== 'undefined' && ad && typeof adtoken !== 'undefined' && adtoken) {
    $(document).on('click', 'input[name=publish_button]', function(e) {
      e.preventDefault();
      submitPublishCMS(ad, 0, adtoken);
    });
    $(document).on('click', 'input[name=lnk_view]', function(e) {
      e.preventDefault();
      submitPublishCMS(ad, 1, adtoken);
    });
  }
});

function submitPublishCMS(url, redirect, token) {
  var id_cms = $('#admin-action-cms-id').val();

  $.ajax({
    url: url + '/index.php',
    type: 'POST',
    data: {
      action: 'PublishCMS',
      id_cms: id_cms,
      status: 1,
      redirect: redirect,
      ajax: 1,
      tab: 'AdminCmsContent',
      token: token
    },
    success: function(response) {
      if (response.indexOf('error') === -1) {
        window.location.href = data;
      }
    }
  });

  return true;
}
