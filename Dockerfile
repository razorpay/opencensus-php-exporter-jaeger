ARG ONGGI_IMAGE=c.rzp.io/razorpay/onggi:php-7.2-nginx

FROM $ONGGI_IMAGE as opencensus-ext
WORKDIR /
ARG OPENCENSUS_VERSION_TAG=v0.7.6.4
RUN set -eux && \
    wget -O - https://github.com/razorpay/opencensus-php/tarball/"${OPENCENSUS_VERSION_TAG}" | tar xz --strip=1
RUN cd /ext && phpize && ./configure --enable-opencensus && make install


FROM $ONGGI_IMAGE

ARG GIT_COMMIT_HASH
ARG GIT_TOKEN
ENV GIT_COMMIT_HASH=${GIT_COMMIT_HASH}

COPY --chown=nginx:nginx . /app/

## Downgrading composer version from 2.0 to 1.10 due to ps4 autoloading issues
## (https://medium.com/legacybeta/using-composer-2-0-with-psr4-388b78b98aaa)

WORKDIR /

ARG LIBRDKAFKA_VERSION_TAG=1.2.2

RUN set -eux && \
    wget https://github.com/edenhill/librdkafka/archive/v"${LIBRDKAFKA_VERSION_TAG}".tar.gz  -O - | tar -xz && \
    cd librdkafka-"${LIBRDKAFKA_VERSION_TAG}" && ./configure && \
    make && \
    make install
RUN pear config-set php_ini /etc/php7/php.ini && \
    pecl install rdkafka

ENV COMPOSER_VERSION="1.10.16"

WORKDIR /app

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php --version="${COMPOSER_VERSION}" && \
    mv composer.phar /usr/local/bin/composer && \
    rm -f composer-setup.php \

WORKDIR /app

RUN composer config -g github-oauth.github.com ${GIT_TOKEN} \
    && composer global require hirak/prestissimo \
    && composer install --no-interaction --no-dev --no-autoloader --no-scripts\
    && rm -rf /root/.composer \
    && composer clear-cache \
    # Disable opcache for now
    && rm /etc/php7/conf.d/00_opcache.ini \


RUN  pear config-set php_ini /etc/php7/php.ini \
    && pecl install opencensus-alpha \
    && mkdir -p public && echo "${GIT_COMMIT_HASH}" > public/commit.txt

COPY --from=opencensus-ext /usr/lib/php7/modules/opencensus.so /usr/lib/php7/modules

EXPOSE 80

ENTRYPOINT ["/app/dockerconf/entrypoint.sh"]
