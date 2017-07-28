FROM razorpay/containers:base-nginx-php7

ARG GIT_COMMIT_HASH

ENV GIT_COMMIT_HASH=${GIT_COMMIT_HASH}

COPY . /app/

RUN chown -R nginx.nginx /app

RUN pip install razorpay.alohomora

COPY ./dockerconf/boot.sh /boot.sh

WORKDIR /app

ARG GITHUB_TOKEN

RUN composer config -g github-oauth.github.com ${GITHUB_TOKEN} && \
    composer install --no-interaction

EXPOSE 80

ENTRYPOINT ["/boot.sh"]
