FROM razorpay/docker:base-nginx-php7

COPY . /app/

RUN chown -R nginx.nginx /app

COPY ./dockerconf/boot.sh /boot.sh

WORKDIR /app

ARG GIT_TOKEN

RUN composer config -g github-oauth.github.com ${GIT_TOKEN} && \
    composer install --no-interaction

EXPOSE 80

ENTRYPOINT ["/boot.sh"]
