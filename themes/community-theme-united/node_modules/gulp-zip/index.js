'use strict';
var path = require('path');
var gutil = require('gulp-util');
var through = require('through2');
var chalk = require('chalk');
var Yazl = require('yazl');
var concatStream = require('concat-stream');

module.exports = function (filename, opts) {
	if (!filename) {
		throw new gutil.PluginError('gulp-zip', chalk.blue('filename') + ' required');
	}

	opts = opts || {};
	opts.compress = typeof opts.compress === 'boolean' ? opts.compress : true;

	var firstFile;
	var zip = new Yazl.ZipFile();

	return through.obj(function (file, enc, cb) {
		if (!firstFile) {
			firstFile = file;
		}

		// because Windows...
		var pathname = file.relative.replace(/\\/g, '/');

		if (!pathname) {
			cb();
			return;
		}

		if (file.isNull() && file.stat && file.stat.isDirectory && file.stat.isDirectory()) {
			zip.addEmptyDirectory(pathname, {
				mtime: file.stat.mtime || new Date(),
				mode: file.stat.mode
			});
		} else {
			var stat = {
				compress: opts.compress,
				mtime: file.stat ? file.stat.mtime : new Date(),
				mode: file.stat ? file.stat.mode : null
			};

			if (file.isStream()) {
				zip.addReadStream(file.contents, pathname, stat);
			}

			if (file.isBuffer()) {
				zip.addBuffer(file.contents, pathname, stat);
			}
		}

		cb();
	}, function (cb) {
		if (!firstFile) {
			cb();
			return;
		}

		zip.end(function () {
			zip.outputStream.pipe(concatStream(function (data) {
				this.push(new gutil.File({
					cwd: firstFile.cwd,
					base: firstFile.base,
					path: path.join(firstFile.base, filename),
					contents: data
				}));

				cb();
			}.bind(this)));
		}.bind(this));
	});
};
