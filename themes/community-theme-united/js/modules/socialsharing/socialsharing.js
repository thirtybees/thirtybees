$(function() {

  $('.socialsharing_product').find('.btn').on('click', function() {
    var type = $(this).attr('data-type');
    if (type.length) {
      switch (type) {
        case 'twitter':
          window.open('https://twitter.com/intent/tweet?text=' + sharing_name + ' ' + encodeURIComponent(sharing_url), 'sharertwt', 'toolbar=0,status=0,width=640,height=445');
          break;
        case 'facebook':
          window.open('http://www.facebook.com/sharer.php?u=' + sharing_url, 'sharer', 'toolbar=0,status=0,width=660,height=445');
          break;
        case 'google-plus':
          window.open('https://plus.google.com/share?url=' + sharing_url, 'sharer', 'toolbar=0,status=0,width=660,height=445');
          break;
        case 'pinterest':
          var img_url = sharing_img;
          var $img = $('#bigpic');
          var productPageImg = $img.attr('src');
          if (productPageImg) {
            img_url = $img.attr('src');
          }
          window.open('http://www.pinterest.com/pin/create/button/?media=' + img_url + '&url=' + sharing_url, 'sharerpinterest', 'toolbar=0,status=0,width=660,height=445');
          break;
      }
    }
  });
});
