FROM razorpay/containers:base-nginx-php7

ARG GIT_COMMIT_HASH

ARG GIT_TOKEN

ENV GIT_COMMIT_HASH=${GIT_COMMIT_HASH}

COPY . /app/

COPY ./dockerconf/boot.sh /boot.sh

WORKDIR /app

RUN composer config -g github-oauth.github.com ${GITHUB_TOKEN} && \
    composer install --no-interaction

RUN chown -R nginx.nginx /app

EXPOSE 80

ENTRYPOINT ["/boot.sh"]
