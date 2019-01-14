let mix = require('laravel-mix');

mix.js('assets/src/js/main.js', 'assets/dist/js')
    .browserSync( {
        proxy : 'pms.test',
        files : [
            '**/*.php',
            'assets/**/*.js',
            'assets/**/*.css'
        ],
    } );
