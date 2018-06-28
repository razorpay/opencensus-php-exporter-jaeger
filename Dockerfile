FROM razorpay/pithos:rzp-php7.1-nginx

ARG GIT_COMMIT_HASH
ARG GIT_TOKEN
ENV GIT_COMMIT_HASH=${GIT_COMMIT_HASH}

COPY --chown=nginx:nginx . /app/

WORKDIR /app

RUN composer config -g github-oauth.github.com ${GIT_TOKEN} \
    && composer install --no-interaction --no-dev \
    && composer clear-cache \
    # Disable opcache for now
    && rm /etc/php7/conf.d/00_opcache.ini

EXPOSE 80

ENTRYPOINT ["/app/dockerconf/entrypoint.sh"]
