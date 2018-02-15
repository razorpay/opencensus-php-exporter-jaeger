#!/bin/sh
set -euo pipefail

SRC_DIR=/drone/src/github.com/razorpay/auth-service
auth_TMP_DIR=/tmp/auth-service ## defined in the environment file

function init_setup
{
    apk update
    echo "changing dir to $SRC_DIR"
    cd $SRC_DIR

    echo "copying env file for testing"
    cp ./environment/.env.sample ./environment/.env.testing

    echo "running composer install"
    composer config -g github-oauth.github.com ${GIT_TOKEN}
    composer install --no-interaction

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
