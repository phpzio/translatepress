var gulp = require('gulp'),
    pot = require( 'gulp-wp-pot' ),
    fs = require('fs'),
    git = require('gulp-git');

var addons = [ 'tp-add-on-browse-as-other-roles', 'tp-add-on-extra-languages', 'tp-add-on-language-by-get-parameter', 'tp-add-on-navigation-based-on-language',
    'tp-add-on-seo-pack', 'trp-add-on-translator-accounts-add-on', 'tp-add-on-automatic-language-detection' ];


/* create a pot file from the main plugin and all the addons */
gulp.task( 'pot', function () {

    //make sure we have all the addon folders and they are on master branch latest
    addons.forEach(function(addon) {
        fs.exists( './../'+ addon, function (exists) {
            if( !exists ){ //if the folder does not exists clone it from bitbucket ( you should have ssh keys )
                git.clone('git@bitbucket.org:cozmoslabs/'+addon+'.git', {args: './../'+addon}, function (err) {
                    if (err) {
                        console.log(err);
                    }
                });
            }
            else{
                // change working dir to the addon so we can run the git commands bellow
                process.chdir('./../'+addon);
                //chang to master
                git.checkout( 'master', function (err) {
                    if (err) {
                        console.log(err);
                    }
                });
                //pull latest
                git.pull( 'origin', 'master', function (err) {
                    if (err) {
                        console.log(err);
                    }
                });
            }
        });
    });

    //make sure we are in the original working directory
    process.chdir('./../translatepress');

    //create all the paths in witch we look for gettext
    lookIn = [ 'includes/**/*.php','partials/**/*.php', '*.php' ];
    addons.forEach(function(addon){
        lookIn.push( './../'+ addon +'/**/*.php' );
    });

    //create the
    return gulp.src( lookIn )
        .pipe( pot({
            domain: 'translatepress-multilingual',
            package: 'TranslatePress Multilingual'
        }) )
        .pipe( gulp.dest( 'languages/translatepress-multilingual.pot', { cwd: './../translatepress' } ) );
});

//create a php catalog that contain all the strings in the pot. this will reside in the translation folder and is used for translate.wordpress.org when they scan for the strings and the string is not in the free version
gulp.task('catalog', function(cb) {

    var fileContent = fs.readFileSync("languages/translatepress-multilingual.pot", "utf8");
    returnFile = '';    
    regex = new RegExp( /msgid ([^]*?)msgstr/g ); //grab everything between msgid and msgstr
    match = regex.exec(fileContent.toString());
    while (match != null) {
        if( typeof match[1] != 'undefined' ) {
            match[1] = match[1].replace( /(?:\r\n|\r|\n)/g, '' ); //remove the new lines inserted by pot
            match[1] = match[1].replace( /(?<!\\|^)""/g, '' ); //recombine in a single string by removing the ""
            returnFile += '<?php __(' + match[1] + ', "translatepress-multilingual"); ?>\n';
        }
        match = regex.exec(fileContent.toString());
    }
    fs.writeFile( 'languages/translatepress-multilingual.catalog.php', returnFile, cb );

});