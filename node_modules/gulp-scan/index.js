'use strict';
var gutil = require('gulp-util'),
	through = require('through2');

module.exports = function (options) {
	var term;

	if (!options.term) {
		throw new gutil.PluginError('gulp-scan', '`term` required');
	}

	if (!(options.term instanceof RegExp) &&
		  !(typeof options.term === 'string' || options.term instanceof RegExp)) {
		throw new gutil.PluginError('gulp-scan', '`term` must be a string or RegExp');
	}

	if (!options.fn) {
		throw new gutil.PluginError('gulp-scan', '`fn` required');
	}

	if (!(options.fn instanceof Function)) {
		throw new gutil.PluginError('gulp-scan', '`fn` must be a Function.');
	}

	term = options.term instanceof RegExp ? options.term : new RegExp(options.term, 'g');

	return through.obj(function (file, enc, cb) {
		var content,
			matches;

		if (file.isNull()) {
			cb(null, file);
			return;
		}

		if (file.isStream()) {
			cb(new gutil.PluginError('gulp-scan', 'Streaming not supported'));
			return;
		}

		try {
			content = file.contents.toString();
			matches = content.match(term);

			if(matches !== null) {
				matches.forEach(function(match) {
					options.fn(match, file.clone());
				});
			}
		}
		catch (err) {
			this.emit('error', new gutil.PluginError('gulp-scan', err));
		}

		cb(null, file);
	});
};
