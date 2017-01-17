module.exports = function (grunt) {
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-sass');

    // Project configuration.
    grunt.initConfig({
	pkg: grunt.file.readJSON('package.json'),
	builddir: '.',
	meta: {
	    banner: '/**\n' +
		' * <%= pkg.description %>\n' +
		' * @version v<%= pkg.version %> - ' +
		'<%= grunt.template.today("yyyy-mm-dd") %>\n' +
		' * @link <%= pkg.homepage %>\n' +
		' * @license <%= pkg.license %>' + ' */'
	},
        convert_less: {
            default_options: {
                files: [{
                    expand: true,
                    src: ['**/*.less']
                }],
                extension: '.scss'
            }
        },
	swatch: {
	    amelia:{}, cerulean:{}, cosmo:{}, cyborg:{}, flatly:{}, journal:{},
	    readable:{}, simplex:{}, slate:{}, spacelab:{}, united:{},
	    custom:{}
	},
	clean: {
	    build: {
		src: ['*/build.scss', '!global/build.scss']
	    }
	},
	concat: {
	    dist: {
		src: [],
		dest: ''
	    }
	},
        sass: {
            dist: {
                options: {
                    style: 'nested'
                },
                files: {}
            }
        }
    });

    grunt.registerTask('none', function() {});

    grunt.registerMultiTask('convert_less', 'naively convert less to scss (may require some debugging)', function() {

        //grunt.log.writeln('---', this.files);
        this.files.forEach(function(f) {
            f.src.filter(function(filepath) {
                if (filepath.indexOf('node_modules') !== -1) {
                    return false;
                } else {
                    return true;
                }
            }).forEach(function(f) {
                var srcContents = grunt.file.read(f);

                var out = srcContents.replace(/@/g, '$')
                        .replace(/\.([\w\-]*)\s*\((.*)\)\s*\{/g, '@mixin $1\($2\) {\n')
                        .replace(/\.([\w\-]*\(.*\)\s*;)/g, '@include $1')
                        .replace(/~"(.*)"/g, '#{"$1"}')
                        .replace(/\$(media|font-face|import)/g, '@$1')
                        .replace(/bootstrap\/less\//g, 'sass-bootstrap/lib/')
                        .replace(/less/g, 'scss')
                        .replace(/spin\(/g, 'adjust-hue(')
                        .replace(/\#gradient\ \>\ \@include\ vertical/g, '@include gradient-vertical');

                if (out.match(/&-/g)) {
                    grunt.log.warn("This file may contain illegal parent selector usage (.navbar { &-brand {}}) that will have to be manually refactored", f);
                    var howto = "";
                    grunt.log.warn(howto);
                }

                var dest = f.replace(/\.less$/, '.scss')
                        .replace(/(bootswatch|variables)/, '_$1');
                grunt.file.write(dest, out);
                grunt.log.writeln('Converted less file:', f);
            });
        });



    });

    grunt.registerTask('build', 'build a regular theme', function(theme, compress) {
	var compress = compress == undefined ? true : compress;

	var concatSrc;
	var concatDest;
	var sassDest;
	var sassSrc;
	var files = {};
	var dist = {};
	concatSrc = 'global/build.scss';
	concatDest = theme + '/build.scss';
	sassDest = '<%=builddir%>/' + theme + '/bootstrap.css';
	sassSrc = [ theme + '/' + 'build.scss' ];

	dist = {src: concatSrc, dest: concatDest};
	grunt.config('concat.dist', dist);
	files = {}; files[sassDest] = sassSrc;
	grunt.config('sass.dist.files', files);
	grunt.config('sass.dist.options.style', 'nested');

	grunt.task.run(['concat', 'sass:dist', 'clean:build',
			compress ? 'compress:'+sassDest+':'+'<%=builddir%>/' + theme + '/bootstrap.min.css':'none']);
    });

    grunt.registerTask('compress', 'compress a generic css', function(fileSrc, fileDst) {
	var files = {}; files[fileDst] = fileSrc;
	grunt.log.writeln('compressing file ' + fileSrc);

	grunt.config('sass.dist.files', files);
	grunt.config('sass.dist.options.style', 'compressed');
	grunt.task.run(['sass:dist']);
    });

    grunt.registerMultiTask('swatch', 'build a theme', function() {
	var t = this.target;
        grunt.log.writeln('target', t);
	grunt.task.run('build:'+t);
    });

    grunt.registerTask('default', 'build a theme', function() {
	grunt.task.run('swatch');
    });
};
