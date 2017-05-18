#!/bin/bash

# wait for db to be provisioned
sleep 30

# Fix permissions
echo  "Fix permissions"
cd /app/ && chmod 777 -R storage

# Copy config
cp dockerconf/auth.docker.conf /etc/nginx/conf.d/auth.conf && \
cp environment/.env.docker environment/.env.testing && \
cp environment/env.sample.php environment/env.php && \
sed -i 's/dev/testing/g' ./environment/env.php

# DB Migrate
echo  "DB Migrate"
cd /app/ && php artisan migrate --force

# start nginx
mkdir /tmp/run
chown 0775 /tmp/run/
/usr/sbin/php-fpm7
/usr/sbin/nginx -g 'daemon off;'