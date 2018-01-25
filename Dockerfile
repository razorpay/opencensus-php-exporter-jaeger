FROM razorpay/containers:rzp-php7.1-nginx

ARG GIT_COMMIT_HASH
ARG GIT_TOKEN
ENV GIT_COMMIT_HASH=${GIT_COMMIT_HASH}

COPY . /app/

COPY ./dockerconf/boot.sh /boot.sh

WORKDIR /app

RUN composer config -g github-oauth.github.com ${GIT_TOKEN} \
    && composer install --no-interaction \
    && mkdir /opt \
    && cd /opt \
    && chown -R nginx.nginx /app

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/dumb-init", "--"]
CMD ["/boot.sh"]
