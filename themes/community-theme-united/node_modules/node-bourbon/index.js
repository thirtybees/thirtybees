var path = require('path');
var bourbon = require('bourbon');

module.exports = {
  includePaths: bourbon.includePaths,

  with: function() {
    var paths  = Array.prototype.slice.call(arguments);
    return [].concat.apply([bourbon.includePaths], paths);
  }
};
