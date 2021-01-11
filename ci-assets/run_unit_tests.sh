#!/bin/sh
set -euo pipefail

auth_TMP_DIR=/tmp/auth-service ## defined in the environment file

SRC_DIR=/github/workspace/

function init_setup
{
    echo "initiate setup"
    apk update

    echo "copying env file for testing"
    cp ./environment/.env.sample ./environment/.env.testing

    touch /etc/php7/conf.d/assertion.ini
    echo "zend.assertions=1" >> /etc/php7/conf.d/assertion.ini
    echo "assert.exception=1" >> /etc/php7/conf.d/assertion.ini
    php -m
    chmod 777 -R storage

    echo "running composer install"
    composer config -g github-oauth.github.com ${GIT_TOKEN}
    composer config -g repos.packagist composer https://packagist.rzp.io
    composer global require hirak/prestissimo
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
