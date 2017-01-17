$(document).ready(function() {
  $('#idTabs a').on('click', function(e) {
    e.preventDefault();
    $(this).tab('show');
  });
});
