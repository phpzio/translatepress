# gulp-scan [![Build Status](https://travis-ci.org/gilt/gulp-scan.svg?branch=master)](https://travis-ci.org/gilt-tech/gulp-scan)

A plugin to scan a file for a string or expression


## Install

```
$ npm install --save-dev gulp-scan
```


## Usage

```js
var gulp = require('gulp');
var scan = require('gulp-scan');

gulp.task('default', function () {
	return gulp.src('src/file.ext')
		.pipe(scan({ term: '@import', fn: function (match, file) {
			// do something with {String} `match`
			// `file` is a clone of the vinyl file.
		}}));
});
```


## API

### scan(options)

#### options

##### term

Type: `string` or `RegExp`  

A term to scan the file for. Can be either a string or regular expression.

##### fn

Type: `Function`  

A function that will receive the individual matches found in a file.

## License

MIT © [Gilt Groupe](https://github.com/gilt)
