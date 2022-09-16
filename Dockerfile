ARG ONGGI_IMAGE=c.rzp.io/razorpay/onggi:php-8.1-nginx

FROM $ONGGI_IMAGE as opencensus-ext

WORKDIR /
ARG OPENCENSUS_VERSION_TAG=v0.8.0-beta
RUN set -eux && \
    wget -O - https://github.com/razorpay/opencensus-php/tarball/"${OPENCENSUS_VERSION_TAG}" | tar xz --strip=1
RUN cd /ext && phpize81 && ./configure --enable-opencensus --with-php-config=/usr/bin/php-config81 && make install

FROM $ONGGI_IMAGE

ARG GIT_COMMIT_HASH
ENV GIT_COMMIT_HASH=${GIT_COMMIT_HASH}

COPY --chown=nginx:nginx . /app/

WORKDIR /

ARG LIBRDKAFKA_VERSION_TAG=1.2.2

RUN set -eux && \
    wget https://github.com/edenhill/librdkafka/archive/v"${LIBRDKAFKA_VERSION_TAG}".tar.gz  -O - | tar -xz && \
    cd librdkafka-"${LIBRDKAFKA_VERSION_TAG}" && ./configure && \
    make && \
    make install

RUN pear81 config-set php_ini /etc/php81/php.ini && \
    pecl81 install rdkafka

RUN pip install --no-cache-dir "razorpay.alohomora==0.5.0"

WORKDIR /app

ARG GIT_USERNAME
RUN --mount=type=secret,id=git_token set -eux \
    && git config --global user.name ${GIT_USERNAME} \
    && composer config -g -a github-oauth.github.com $(cat /run/secrets/git_token) \
    && composer install --no-interaction --no-dev \
    && composer clear-cache \
    # Disable opcache for now
    && rm /etc/php81/conf.d/00_opcache.ini


RUN  pear81 config-set php_ini /etc/php81/php.ini \
    && pecl81 install opencensus-alpha

COPY --from=opencensus-ext /usr/lib/php81/modules/opencensus.so /usr/lib/php81/modules

EXPOSE 80

ENTRYPOINT ["/app/dockerconf/entrypoint.sh"]
