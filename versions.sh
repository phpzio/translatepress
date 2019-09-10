#!/bin/bash
repositoryroot="$PWD"

if [[ "$OSTYPE" == "linux-gnu" ]]; then
        home=~
else
    home="c:"
fi

#CREATE FREE HERE:
echo "Do you want to update the free version ? (y/n)"
read freeversion
if [ $freeversion == 'y' ]; then
    echo "enter the new free version number:"
    read version

    #create translation pot
    npm run pot
    #create translation catalog
    npm run catalog

    #compile assets
    npm run production

    #change version in readme index and class-translate-press
    sed -i -e "s/^Version: .*$/Version: $version/g" $repositoryroot/index.php
    sed -i -e "s/^        define( 'TRP_PLUGIN_VERSION', .*$/        define( 'TRP_PLUGIN_VERSION', '$version' );/g" $repositoryroot/class-translate-press.php
    sed -i -e "s/^Stable tag: .*$/Stable tag: $version/g" $repositoryroot/readme.txt


    #try to do a changelog
    latesttag=$(git describe --abbrev=0 --tags)

    if [ -z "$latesttag" ]
    then
        latesttag="628e79e"
    fi

    changelog=$(git log --pretty=format:"%h %s" $latesttag..HEAD)
    #remove commit numbers -- not working for now
    #changelog=${changelog//[^[.......\s]]/\* }

    echo "

    $version
    $changelog" >> "$repositoryroot"/readme.txt


    # manually edit the readme file then after enter create archives
    read -p "Press [Enter] key after you manually edited the readme files to push to svn..."

    #push the changes
    git add .
    git commit -m "changing version number"
    git push origin master

    #tag new version
    cd "$repositoryroot"
    git tag -a $version -m 'tagging version $version'
    git push origin $version


    if [ ! -d $home/Work/TRP/FreeSvn ]; then
        mkdir -p $home/Work/TRP/FreeSvn
        cd $home/Work/TRP/FreeSvn
        svn co http://plugins.svn.wordpress.org/translatepress-multilingual
        cd $home/Work/TRP/FreeSvn/translatepress-multilingual
    else
        cd $home/Work/TRP/FreeSvn/translatepress-multilingual
        svn up
    fi

    trunk_dir="$home/Work/TRP/FreeSvn/translatepress-multilingual/trunk"

    rm -rfv $trunk_dir/*
    #copy without git folder
    find "$repositoryroot" -mindepth 1 -maxdepth 1 -name '.git' -or -exec cp -r {} $trunk_dir \;
    rm -rf $trunk_dir/versions.sh
    rm -rf $trunk_dir/node_modules
    rm -rf $trunk_dir/gulpfile.js
    rm -rf $trunk_dir/package-lock.json
    rm -rf $trunk_dir/.gitignore
    rm -rf $trunk_dir/.env

    #remove vue files
    rm -rf $trunk_dir/webpack.mix.js
    rm -rf $trunk_dir/mix-manifest.json
    rm -rf $trunk_dir/package.json
    rm -rf $trunk_dir/assets/src

    #remove testing files
    rm -rf $trunk_dir/bin
    rm -rf $trunk_dir/tests
    rm -rf $trunk_dir/.phpcs.xml.dist
    rm -rf $trunk_dir/.travis.yml
    rm -rf $trunk_dir/phpunit.xml.dist

    #add new tag
    svn cp trunk tags/$version

    #remove deleted files if any add new files that changed and commit changes
    svn st | grep ^! | awk '{print " --force "$2}' | xargs svn rm
    svn add * --force
    svn ci -m "tagging version $version"
fi
