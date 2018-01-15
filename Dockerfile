FROM razorpay/containers:base-nginx-php7

ARG GIT_COMMIT_HASH
ARG GIT_TOKEN
ENV GIT_COMMIT_HASH=${GIT_COMMIT_HASH}
ENV NR_INSTALL_SILENT true

COPY . /app/

COPY ./dockerconf/boot.sh /boot.sh

WORKDIR /app

RUN composer config -g github-oauth.github.com ${GIT_TOKEN} \
    && composer install --no-interaction \
    && mkdir /opt && cd /opt \
    && wget https://download.newrelic.com/php_agent/archive/7.6.0.201/newrelic-php5-7.6.0.201-linux-musl.tar.gz \
    && tar -xzvf newrelic-php5-7.6.0.201-linux-musl.tar.gz \
    && ./newrelic-php5-7.6.0.201-linux-musl/newrelic-install install

RUN chown -R nginx.nginx /app

EXPOSE 80

ENTRYPOINT ["/boot.sh"]
