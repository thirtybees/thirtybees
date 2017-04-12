var fs = require('fs');
var path = require('path');
var crypto = require('crypto');

var files = {};
var currentDirectory = path.join(__dirname, '../');

var check = 1;
var walk = function (dir, action, done) {

  // this flag will indicate if an error occurred (in this case we don't want to go on walking the tree)
  var dead = false;

  // this flag will store the number of pending async operations
  var pending = 0;

  var fail = function (err) {
    if (!dead) {
      dead = true;
      done(err);
    }
  };

  var checkSuccess = function () {
    if (!dead && pending == 0) {
      done();
    }
  };

  var performAction = function (file, stat) {
    if (!dead) {
      try {
        action(file, stat);
      }
      catch (error) {
        fail(error);
      }
    }
  };

  // this function will recursively explore one directory in the context defined by the variables above
  var dive = function (dir) {
    pending++; // async operation starting after this line
    fs.readdir(dir, function (err, list) {
      if (!dead) { // if we are already dead, we don't do anything
        if (err) {
          fail(err); // if an error occured, let's fail
        }
        else { // iterate over the files
          list.forEach(function (file) {
            if (!dead) { // if we are already dead, we don't do anything
              var path = dir + "/" + file;
              pending++; // async operation starting after this line
              fs.stat(path, function (err, stat) {
                if (!dead) { // if we are already dead, we don't do anything
                  if (err) {
                    fail(err); // if an error occured, let's fail
                  }
                  else {
                    if (stat && stat.isDirectory()) {
                      dive(path); // it's a directory, let's explore recursively
                    }
                    else {
                      performAction(path, stat); // it's not a directory, just perform the action
                    }
                    pending--;
                    checkSuccess(); // async operation complete
                  }
                }
              });
            }
          });
          pending--;
          checkSuccess(); // async operation complete
        }
      }
    });
  };

  // start exploration
  dive(dir);
};

function logFile(filePath, stat) {
  if (filePath) {
    if (path.basename(filePath) !== 'index.php') {
      var data = fs.readFileSync(filePath, 'utf-8');
      files['/' + path.relative(currentDirectory, filePath)] = {
        md5: checksum(data),
        action: 'add',
      };
    }
  }
}

function walkFinished(error) {
  check--;
  if (check <= 0) {
    fs.writeFile('files.json', JSON.stringify(files, null, 4), function (err) {
      process.exit();
    });
  }
}

function checksum(str, algorithm, encoding) {
  return crypto
    .createHash(algorithm || 'md5')
    .update(str, 'utf8')
    .digest(encoding || 'hex');
}

walk(path.join(__dirname, 'vendor/'), logFile, walkFinished);

