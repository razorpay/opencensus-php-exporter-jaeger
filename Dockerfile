FROM c.rzp.io/razorpay/onggi:php-7.2-nginx

ARG GIT_COMMIT_HASH
ARG GIT_TOKEN
ENV GIT_COMMIT_HASH=${GIT_COMMIT_HASH}

COPY --chown=nginx:nginx . /app/

## Downgrading composer version from 2.0 to 1.10 due to ps4 autoloading issues
## (https://medium.com/legacybeta/using-composer-2-0-with-psr4-388b78b98aaa)

ENV COMPOSER_VERSION="1.10.16"

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php --version="${COMPOSER_VERSION}" && \
    mv composer.phar /usr/local/bin/composer && \
    rm -f composer-setup.php

WORKDIR /app

RUN composer config -g github-oauth.github.com ${GIT_TOKEN} \
    && composer global require hirak/prestissimo \
    && composer install --no-interaction --no-dev \
    && composer clear-cache \
    # Disable opcache for now
    && rm /etc/php7/conf.d/00_opcache.ini

EXPOSE 80

ENTRYPOINT ["/app/dockerconf/entrypoint.sh"]
