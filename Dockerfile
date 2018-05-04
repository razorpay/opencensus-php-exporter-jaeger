FROM razorpay/pithos:rzp-php7.1-nginx

ARG GIT_COMMIT_HASH
ARG GIT_TOKEN
ENV GIT_COMMIT_HASH=${GIT_COMMIT_HASH}

RUN mkdir /app/

COPY . /app/

COPY ./dockerconf/boot.sh /boot.sh

WORKDIR /app

RUN apk update \
    && composer config -g github-oauth.github.com ${GIT_TOKEN} \
    && composer install --no-interaction \
    && mkdir /opt \
    && cd /opt \
    && composer clear-cache

RUN chown -R nginx.nginx /app

EXPOSE 80

ENTRYPOINT ["/boot.sh"]
