#!/bin/sh
set -euo pipefail

auth_TMP_DIR=/tmp/auth-service ## defined in the environment file
SONAR="sonar"
GITHUB_BRANCH="$(echo ${GITHUB_REF##*/})";

SRC_DIR=/github/workspace/

function init_setup
{
    echo "initiate setup"
    apk update

    echo "copying env file for testing"
    cp ./environment/.env.sample ./environment/.env.testing

    echo "sonar branch : ${GITHUB_BRANCH}, Argument : ${SONAR}"

    echo "adding xdebug"
    apk --no-cache add pcre-dev
    pecl install xdebug
    echo 'zend_extension=xdebug.so' >> /etc/php7/php.ini
    echo 'xdebug.mode=coverage' >> /etc/php7/php.ini
    sed -i 's/max_execution_time.*/max_execution_time=120/' /etc/php7/php.ini
    sed -i 's/memory_limit.*/memory_limit=-1/' /etc/php7/php.ini

    touch /etc/php7/conf.d/assertion.ini
    echo "zend.assertions=1" >> /etc/php7/conf.d/assertion.ini
    echo "assert.exception=1" >> /etc/php7/conf.d/assertion.ini
    php -m
    chmod 777 -R storage

    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php --version="1.10.16" && \
    mv composer.phar /usr/local/bin/composer && \
    rm -f composer-setup.php

    echo "running composer install"
    composer config -g github-oauth.github.com ${GIT_TOKEN}
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
    APP_MODE=testing php -d memory_limit=1024M vendor/phpunit/phpunit/phpunit --debug --verbose --coverage-clover clover.xml

    cat clover.xml
    pwd
}

init_setup
run_tests
exit $?
