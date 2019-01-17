let mix = require('laravel-mix');

mix.js( 'assets/src/js/trp-editor.js', 'assets/js' )
    .sass( 'assets/src/scss/trp-editor.scss', 'assets/css/' )
    .browserSync( {
        proxy : 'pms.test',
        files : [
            '**/*.php',
            'assets/**/*.js',
            'assets/**/*.css'
        ],
    } )
    .webpackConfig({
        externals: {
            "jquery" : "jQuery",
        }
    });
