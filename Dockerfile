ARG ONGGI_IMAGE=c.rzp.io/razorpay/rzp-docker-image-inventory-multi-arch:rzp-golden-image-base-php-8.2-fpm-alpine3.20

FROM $ONGGI_IMAGE as opencensus-ext

RUN apk add --no-cache dumb-init php82-dev build-base

WORKDIR /
ARG OPENCENSUS_VERSION_TAG=v0.8.0-beta
RUN set -eux && \
    wget -O - https://github.com/razorpay/opencensus-php/tarball/"${OPENCENSUS_VERSION_TAG}" | tar xz --strip=1
RUN cd /ext && phpize82 && ./configure --enable-opencensus --with-php-config=/usr/bin/php-config82 && make install

FROM $ONGGI_IMAGE

ARG GIT_COMMIT_HASH
ENV GIT_COMMIT_HASH=${GIT_COMMIT_HASH}

COPY --chown=nginx:nginx . /app/

WORKDIR /

ARG LIBRDKAFKA_VERSION_TAG=1.2.2

RUN apk add --no-cache bash build-base autoconf && \
    set -eux && \
    wget https://github.com/edenhill/librdkafka/archive/v"${LIBRDKAFKA_VERSION_TAG}".tar.gz  -O - | tar -xz && \
    cd librdkafka-"${LIBRDKAFKA_VERSION_TAG}" && ./configure && \
    make && \
    make install

RUN pear config-set php_ini /etc/php82/php.ini && \
    pecl install rdkafka

ENV GRPC_VERSION=v1.66.0

RUN apk update

# ref: https://github.com/grpc/grpc/issues/34278#issuecomment-1871059454
RUN apk add --no-cache git grpc-cpp grpc-dev $PHPIZE_DEPS && \
    GRPC_VERSION=$(apk info grpc -d | grep grpc | cut -d- -f2) && \
    git clone --depth 1 -b v${GRPC_VERSION} https://github.com/grpc/grpc /tmp/grpc && \
    cd /tmp/grpc/src/php/ext/grpc && \
    phpize && \
    ./configure && \
    make && \
    make install && \
    rm -rf /tmp/grpc && \
    apk del --no-cache git grpc-dev $PHPIZE_DEPS && \
    echo "extension=grpc.so" >> /etc/php82/php.ini

RUN apk add py3-pip

RUN pip install --no-cache-dir "razorpay.alohomora==0.5.0"

COPY ./dockerconf/php-fpm-www.conf /etc/php82/php-fpm.conf

WORKDIR /app

ARG GIT_USERNAME
RUN --mount=type=secret,id=git_token set -eux \
    && git config --global user.name ${GIT_USERNAME} \
    && composer config -g -a github-oauth.github.com $(cat /run/secrets/git_token) \
    && composer install --no-interaction --no-dev \
    && composer clear-cache \
    # Disable opcache for now
    && rm /etc/php82/conf.d/00_opcache.ini


RUN  pear config-set php_ini /etc/php82/php.ini \
    && pecl install opencensus-alpha

COPY --from=opencensus-ext /usr/lib/php82/modules/opencensus.so /usr/lib/php82/modules

EXPOSE 80

#--rewrite 15:3 rewrties SIGTERM to SIGQUIT before proxying it through dumb-init
ENTRYPOINT ["/usr/bin/dumb-init", "--rewrite", "15:3", "--single-child","/app/dockerconf/entrypoint.sh"]
