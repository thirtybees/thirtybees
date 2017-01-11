$(document).ready(function() {
  $(document).on('change', '#our_price_display', function(e) {
    updateLoyaltyView(parseInt($('#our_price_display').text()));
  });
  updateLoyaltyView(parseInt($('#our_price_display').text()));
});

function updateLoyaltyView(new_price) {
  if (typeof(new_price) == 'undefined' || typeof(productPriceWithoutReduction) == 'undefined')
    return;

  var points = Math.floor(new_price / point_rate);
  var total_points = points_in_cart + points;
  var voucher = total_points * point_value;

  if (!none_award && productPriceWithoutReduction != new_price) {
    $('#loyalty').html(loyalty_already);
  } else if (!points) {
    $('#loyalty').html(loyalty_nopoints);
  } else {
    var content = loyalty_willcollect + ' <b><span id="loyalty_points">' + points + '</span> ';
    if (points > 1)
      content += loyalty_points + '</b>. ';
    else
      content += loyalty_point + '</b>. ';

    content += loyalty_total + ' <b><span id="total_loyalty_points">' + total_points + '</span> ';
    if (total_points > 1)
      content += loyalty_points;
    else
      content += loyalty_point;

    content += '</b> ' + loyalty_converted + ' ';
    content += '<span id="loyalty_price">' + formatCurrency(voucher, currencyFormat, currencySign, currencyBlank) + '</span>.';
    $('#loyalty').html(content);
  }
}
