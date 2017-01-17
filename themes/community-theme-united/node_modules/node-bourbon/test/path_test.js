const chai = require('chai');
const assert = chai.assert;
const bourbon = require('../');

describe('#includePaths', function() {
  it('should export an array of paths', function() {
    assert.isArray(bourbon.includePaths);
    assert.isString(bourbon.includePaths[0], 'first #includePaths path');
  });
});
