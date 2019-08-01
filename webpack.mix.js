let mix = require('laravel-mix');

mix.js( 'assets/src/js/trp-editor.js', 'assets/js' )
    .sass( 'assets/src/scss/trp-editor.scss', 'assets/css/' )
    .browserSync( {
        proxy : 'localhost/tpdemo/',
        files : [
            '**/*.php',
            'assets/**/*.js',
            'assets/**/*.css'
        ],
        ghostMode : false
    } )
    .webpackConfig({
        externals: {
            "jquery" : "jQuery",
        }
    })
    .sourceMaps(true, 'source-map')
