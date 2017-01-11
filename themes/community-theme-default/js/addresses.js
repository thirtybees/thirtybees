$(document).ready(function() {
  if (typeof addressesConfirm !== 'undefined' && addressesConfirm) {
    $('a[data-id="addresses_confirm"]').click(function() {
      return confirm(addressesConfirm);
    });
  }
});
