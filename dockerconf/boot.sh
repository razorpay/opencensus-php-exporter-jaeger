#!/bin/bash

# wait for db to be provisioned
sleep 30

# Fix permissions
echo  "Fix permissions"
cd /app/ && chmod 777 -R storage

# Copy config
if [[ "${APP_CONTEXT}" == "dev" ]]; then
  cp dockerconf/auth.docker.conf /etc/nginx/conf.d/auth.conf && \
  cp environment/.env.docker environment/.env.testing && \
  cp environment/env.sample.php environment/env.php && \
  sed -i 's/dev/testing/g' ./environment/env.php
else
  # copy nginx config
  cp dockerconf/auth.docker.conf /etc/nginx/conf.d/auth.conf

  # change log path
  ACCESS_LOG_PATH="access_log /var/log/nginx/auth.razorpay.dev.access.log combined"
  sed -i "s|access_log|${ACCESS_LOG_PATH}|g" /etc/nginx/conf.d/auth.conf

  # change domain reference
  if [[ "${APP_CONTEXT}" != "prod" ]]; then
    sed -i "s|auth.razorpay.dev|${APP_CONTEXT}-auth.razorpay.com|g" /etc/nginx/conf.d/auth.conf
  elif [[ "${APP_CONTEXT}" == "prod" ]]; then
    sed -i "s|auth.razorpay.dev|auth.razorpay.com|g" /etc/nginx/conf.d/auth.conf
  fi

  # use alohomora to generate vault and env.php
  # TODO: APP_CONTEXT would be an arbitrary string when deployed in k8s
  $ALOHOMORA_BIN cast --region ap-south-1 --env $APP_CONTEXT --app $APP_NAME "environment/.env.vault.j2"
  $ALOHOMORA_BIN cast --region ap-south-1 --env $APP_CONTEXT --app $APP_NAME "environment/env.php.j2"
fi
# DB Migrate
echo  "DB Migrate"
cd /app/ && php artisan migrate --force

# start nginx
mkdir /tmp/run
chown 0775 /tmp/run/
/usr/sbin/php-fpm7
/usr/sbin/nginx -g 'daemon off;'
