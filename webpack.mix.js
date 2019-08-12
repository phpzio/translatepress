let mix = require('laravel-mix');

mix.js( 'assets/src/js/trp-editor.js', 'assets/js' )
    .sass( 'assets/src/scss/trp-editor.scss', 'assets/css/' )
    .browserSync( {
        //create a .env file in the project root with the variable from below: MIX_LOCAL_URL=pms.test
        proxy : process.env.MIX_LOCAL_URL,
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
