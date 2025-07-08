#!/bin/sh
set -euo pipefail

auth_TMP_DIR=/tmp/auth-service ## defined in the environment file
SONAR="sonar"
GITHUB_BRANCH="$(echo ${GITHUB_REF##*/})";
LIBRDKAFKA_VERSION_TAG=1.2.2
GRPC_VERSION=v1.66.0

SRC_DIR=/__w/auth-service/auth-service

# Define PHP extension build dependencies
PHPIZE_DEPS="autoconf dpkg-dev dpkg file g++ gcc libc-dev make pkgconf re2c"

function init_setup
{
    echo "initiate setup"
    apk update

    echo "copying env file for testing"
    cp ./environment/.env.sample ./environment/.env.testing

    echo "sonar branch : ${GITHUB_BRANCH}, Argument : ${SONAR}"

    echo "installing build dependencies"
    apk add --no-cache build-base autoconf ${PHPIZE_DEPS}

    echo "adding xdebug"
    apk --no-cache add pcre-dev
    pecl install xdebug
    echo 'zend_extension=xdebug.so' >> /usr/local/etc/php/php.ini
    echo 'xdebug.mode=coverage' >> /usr/local/etc/php/php.ini
    sed -i 's/max_execution_time.*/max_execution_time=120/' /usr/local/etc/php/php.ini
    sed -i 's/memory_limit.*/memory_limit=-1/' /usr/local/etc/php/php.ini

    echo "adding rdkafka"
    set -eux && \
    wget https://github.com/edenhill/librdkafka/archive/v"${LIBRDKAFKA_VERSION_TAG}".tar.gz  -O - | tar -xz && \
    cd librdkafka-"${LIBRDKAFKA_VERSION_TAG}" && ./configure && \
    make && \
    make install

    pear config-set php_ini /usr/local/etc/php/php.ini && \
    pecl install rdkafka

    # ref: https://github.com/grpc/grpc/issues/34278#issuecomment-1871059454
    echo "adding grpc"
    apk add --no-cache git grpc-cpp grpc-dev && \
    GRPC_VERSION=$(apk info grpc -d | grep grpc | cut -d- -f2) && \
    git clone --depth 1 -b v${GRPC_VERSION} https://github.com/grpc/grpc /tmp/grpc && \
    cd /tmp/grpc/src/php/ext/grpc && \
    phpize && \
    ./configure && \
    make && \
    make install && \
    rm -rf /tmp/grpc && \
    apk del --no-cache git grpc-dev && \
    echo "extension=grpc.so" >> /usr/local/etc/php/php.ini

    echo "php.ini looks like"
    cat /usr/local/etc/php/php.ini

    cd ${SRC_DIR}
    touch /usr/local/etc/php/conf.d/assertion.ini
    echo "zend.assertions=1" >> /usr/local/etc/php/conf.d/assertion.ini
    echo "assert.exception=1" >> /usr/local/etc/php/conf.d/assertion.ini
    php -m
    chmod 777 -R storage

    echo "running composer install"
    composer config -g -a github-oauth.github.com ${GIT_TOKEN} && composer install --no-interaction && composer clear-cache && rm -f /usr/local/etc/php/conf.d/00_opcache.ini

    echo "cleaning up build dependencies"
    apk del --no-cache build-base autoconf ${PHPIZE_DEPS}

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
