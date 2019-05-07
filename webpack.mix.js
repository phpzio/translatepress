let mix = require('laravel-mix');

//require('laravel-mix-polyfill');

mix.js( 'assets/src/js/trp-editor.js', 'assets/js' )
    .sass( 'assets/src/scss/trp-editor.scss', 'assets/css/' )
    // .polyfill({
    //     enabled     : true,
    //     useBuiltIns : "usage",
    //     targets     : { "firefox": "50", "ie": 11 }
    // })
    .browserSync( {
        proxy : 'localhost/local/',
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
    .sourceMaps();
