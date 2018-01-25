FROM razorpay/containers:rzp-php7.1-nginx

ARG GIT_COMMIT_HASH
ARG GIT_TOKEN
ENV GIT_COMMIT_HASH=${GIT_COMMIT_HASH}

RUN mkdir /app/

COPY . /app/

COPY ./dockerconf/boot.sh /boot.sh

WORKDIR /app

RUN apk update \
    && apk add --no-cache \
    php7-xmlwriter \
    php7-tokenizer \
    php7-simplexml \
    && composer config -g github-oauth.github.com ${GIT_TOKEN} \
    && composer install --no-interaction \
    && mkdir /opt \
    && cd /opt \
    && composer clear-cache

RUN chown -R nginx.nginx /app

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/dumb-init", "--"]
CMD ["/boot.sh"]
