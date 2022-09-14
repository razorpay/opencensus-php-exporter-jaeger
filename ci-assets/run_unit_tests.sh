#!/bin/sh
set -euo pipefail

auth_TMP_DIR=/tmp/auth-service ## defined in the environment file
SONAR="sonar"
GITHUB_BRANCH="$(echo ${GITHUB_REF##*/})";

SRC_DIR=/__w/auth-service/auth-service

function init_setup
{
    echo "initiate setup"
    apk update

    echo "copying env file for testing"
    cp ./environment/.env.sample ./environment/.env.testing

    echo "sonar branch : ${GITHUB_BRANCH}, Argument : ${SONAR}"

    echo "adding xdebug"
    apk --no-cache add pcre-dev
    pecl81 install xdebug
    echo 'zend_extension=xdebug.so' >> /etc/php81/php.ini
    echo 'xdebug.mode=coverage' >> /etc/php81/php.ini
    sed -i 's/max_execution_time.*/max_execution_time=120/' /etc/php81/php.ini
    sed -i 's/memory_limit.*/memory_limit=-1/' /etc/php81/php.ini

    echo "adding rdkafka"
    apk -U upgrade && apk add git alpine-sdk bash zlib-dev libressl-dev cyrus-sasl-dev zstd-dev
    git clone https://github.com/edenhill/librdkafka.git
    cd librdkafka ; ./configure --prefix /usr --install-deps ; make ; make install ; cd ..

    pecl81 install rdkafka
    echo 'extension=rdkafka.so' >> /etc/php81/php.ini

    touch /etc/php81/conf.d/assertion.ini
    echo "zend.assertions=1" >> /etc/php81/conf.d/assertion.ini
    echo "assert.exception=1" >> /etc/php81/conf.d/assertion.ini
    php -m
    chmod 777 -R storage

    echo "running composer install"
    composer config -g -a github-oauth.github.com ${GIT_TOKEN} && composer install --no-interaction && composer clear-cache && rm /etc/php81/conf.d/00_opcache.ini

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
    APP_MODE=testing php -d memory_limit=1024M vendor/phpunit/phpunit/phpunit --debug --verbose --coverage-clover clover.xml


}

init_setup
run_tests
sed -i 's@'$SRC_DIR'@/github/workspace/@g' clover.xml
exit $?
