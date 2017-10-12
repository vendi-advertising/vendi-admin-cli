#!/bin/bash

#This directory
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

#The directory to build to
BUILD_DIR="$DIR/tmp"

#Find our PHP executable
PHP=`which php`

#Erase the build directory if it exists already
if [ -d $BUILD_DIR ]; then
    echo 'Removing temp directory'
    rm -rf $BUILD_DIR
fi

#Clone our repo
git clone https://github.com/vendi-advertising/vendi-admin-cli.git $BUILD_DIR

#Enter the directory
cd $BUILD_DIR

#Update composer, don't include dev items
composer update --no-dev

$PHP --file "$BUILD_DIR/build-phar.php"
