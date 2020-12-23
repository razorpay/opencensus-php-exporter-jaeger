#!/bin/sh
set -euo pipefail

#SRC_DIR=/drone/src/github.com/razorpay/auth-service

#ORIG_DIR=/github/workspace/
#SRC_DIR=/drone/src/github.com/razorpay/auth-service
auth_TMP_DIR=/tmp/auth-service ## defined in the environment file

SRC_DIR=/github/workspace/

function init_setup
{
    apk update
    #mkdir -p /go/src/github.com/razorpay/auth-service
    #cp -Rp $ORIG_DIR $SRC_DIR
    echo "changing dir to $SRC_DIR"
    cd $SRC_DIR
    #cp -r workspace/* .
    echo "copying env file for testing"
    #cp ${SRC_DIR}/environment/.env.sample ${SRC_DIR}/environment/.env.testing
    cp ./environment/.env.sample ./environment/.env.testing
#
#    echo "installing composer"
#    -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
#    -r "if (hash_file('sha384', 'composer-setup.php') === '756890a4488ce9024fc62c56153228907f1545c228516cbf63f885e036d37e9a59d27d63f46af1d4d07ee0f76181c7d3') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
#    composer-setup.php
#    -r "unlink('composer-setup.php');"

    echo "running composer install"
    composer config -g github-oauth.github.com ${GIT_TOKEN}
    echo "running composer install"
    composer config -g repos.packagist composer https://packagist.rzp.io
#    echo "running composer install"
#    composer global require hirak/prestissimo
    echo "running composer install"
    #composer install --no-interaction
    composer install --no-interaction --optimize-autoloader

    if [ ! -d "$auth_TMP_DIR" ]; then
        mkdir -p $auth_TMP_DIR
    fi
}

function run_tests
{
    cd $SRC_DIR
    export APP_MODE=testing
    echo "APP_MODE=testing" > .env

    # Run Migrations
    echo "running migrations"
    APP_ENV=testing php artisan migrate

    # Run tests
    echo "running tests"
    APP_MODE=testing php -d memory_limit=1024M vendor/phpunit/phpunit/phpunit --debug --verbose
}

init_setup
run_tests
exit $?
