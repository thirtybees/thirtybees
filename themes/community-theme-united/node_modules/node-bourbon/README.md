[![Bourbon Sass Mixin Library](http://bourbon.io/images/shared/bourbon-logo.png)](http://bourbon.io)

*This is a node-sass port of the [Bourbon](http://bourbon.io) library. If you
are looking for the original Ruby/Rails version, you can find it
[here](https://github.com/thoughtbot/bourbon).*

[![Build Status](https://travis-ci.org/lacroixdesign/node-bourbon.png?branch=master)](https://travis-ci.org/lacroixdesign/node-bourbon)

# Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
  - [Basic Usage](#basic-usage)
  - [gulp.js](#gulpjs-usage)
  - [Grunt](#grunt-usage)
  - [node-sass](#node-sass-usage)
- [Getting Help](#getting-help)
- [Testing](#testing)
- [Credits](#credits)
- [License](#license)

# Requirements
- [node](http://nodejs.org)
- [gulp.js](http://gulpjs.com) -or- [Grunt](http://gruntjs.com) -or- [node-sass](https://github.com/andrew/node-sass)

# Installation

To install as a development dependency, run:

```bash
npm install --save-dev node-bourbon
```

If you need it in production, replace `--save-dev` with `--save`.

# Usage

## Basic Usage

To use `node-bourbon` with tools like [gulp.js](#gulpjs-usage), [Grunt](#grunt-usage), or directly with [node-sass](#node-sass-usage), provide the path to Bourbon in your Sass config. There are a couple of convenience methods for this, depending on whether you want Sass to include additional directories or not.

### with() Function

The `with()` function will include any additional paths you pass as arguments.

Returns an array of paths.

```javascript
var bourbon = require('node-bourbon');
// Any of these will return an array of Bourbon paths plus your custom path(s)
bourbon.with('path/to/stylesheets')
bourbon.with('path/to/stylesheets1', 'path/to/stylesheets2')
bourbon.with(['path/to/stylesheets1', 'path/to/stylesheets2'])
```

### includePaths Property

The `includePaths` property returns an array of Bourbon's paths to use in your config.

```javascript
var bourbon = require('node-bourbon');
bourbon.includePaths // Array of Bourbon paths
```

### Stylesheet usage

Use either method above with the Sass config for your chosen tool (gulp.js, Grunt, etc.), then it's business as usual for Bourbon in your stylesheet:

```scss
@import "bourbon";
```

## gulp.js Usage

Using the [gulp-sass](https://github.com/dlmanning/gulp-sass) plugin.

```javascript
var gulp = require('gulp');
var sass = require('gulp-sass');

gulp.task('sass', function () {
  gulp.src('path/to/input.scss')
    .pipe(sass({
      // includePaths: require('node-bourbon').with('other/path', 'another/path')
      // - or -
      includePaths: require('node-bourbon').includePaths
    }))
    .pipe(gulp.dest('path/to/output.css'));
});
```

## Grunt Usage

### Using *grunt-sass*

The [grunt-sass](https://github.com/sindresorhus/grunt-sass) task uses
[node-sass](https://github.com/andrew/node-sass)
([LibSass](https://github.com/hcatlin/libsass)) underneath, and is the recommended
way to use Grunt with node-bourbon.

Example config:

```javascript
grunt.initConfig({
  sass: {
    dist: {
      options: {
        // includePaths: require('node-bourbon').with('other/path', 'another/path')
        // - or -
        includePaths: require('node-bourbon').includePaths
      },
      files: {
        'path/to/output.css': 'path/to/input.scss'
      }
    }
  }
});
```

### Using *grunt-contrib-sass*

If you are using the Ruby version of Sass with node-bourbon, then you will need to use
the [grunt-contrib-sass](https://github.com/gruntjs/grunt-contrib-sass) task instead.

*Note that node-bourbon is __NOT__ tested against the __Ruby__ version – only against __LibSass__.*

Example config:

```javascript
grunt.initConfig({
  sass: {
    dist: {
      options: {
        // loadPath: require('node-bourbon').with('other/path', 'another/path')
        // - or -
        loadPath: require('node-bourbon').includePaths
      },
      files: {
        'path/to/output.css': 'path/to/input.scss'
      }
    }
  }
});
```

## node-sass Usage

Using it directly with [node-sass](https://github.com/andrew/node-sass).

```javascript
var sass    = require('node-sass')
var bourbon = require('node-bourbon');

sass.render({
  file: './application.scss',
  success: function(css){
    console.log(css);
  },
  error: function(error) {
    console.log(error);
  },
  // includePaths: bourbon.with('other/path', 'another/path'),
  // - or -
  includePaths: bourbon.includePaths,
  outputStyle: 'compressed'
});
```

# Getting Help

Feel free to tweet me with questions [@iamlacroix](https://twitter.com/iamlacroix), or [open a ticket](https://github.com/lacroixdesign/node-bourbon/issues) on GitHub.

# Testing

`node-bourbon` is tested against the examples provided in the 
[Bourbon documentation](http://bourbon.io/docs). The tests check for compile 
errors, so if a feature compiles but the expected output is incorrect, be sure 
to [open a ticket](https://github.com/lacroixdesign/node-bourbon/issues).

Run the tests with:

```
make test
```

# Credits

This node-sass port is maintained by Michael LaCroix, however all credits for
the Bourbon library go to [thoughtbot, inc](http://thoughtbot.com/community):

> ![thoughtbot](http://thoughtbot.com/images/tm/logo.png)
>
> Bourbon is maintained and funded by [thoughtbot, inc](http://thoughtbot.com/community)
>
> The names and logos for thoughtbot are trademarks of thoughtbot, inc.
>
> Got questions? Need help? Tweet at [@phillapier](http://twitter.com/phillapier).

# License

node-bourbon is Copyright © 2013-2014 Michael LaCroix. It is free software, and may be redistributed under the terms specified in the LICENSE file.
