#!/usr/bin/dumb-init /bin/sh
set -euo pipefail

echo "Creating Log Files"
mkdir -p /var/log/nginx
touch /var/log/nginx/auth.access.log
touch /var/log/nginx/auth.error.log

echo $GIT_COMMIT_HASH > /app/public/commit.txt

ALOHOMORA_BIN=$(which alohomora)

# Lumen in docker picks system environment variables from .env
echo "APP_MODE=${APP_MODE}" > /app/.env
echo "GIT_COMMIT_HASH=${GIT_COMMIT_HASH}" >> /app/.env

# Nginx Conf
echo "Casting alohomora - nginx conf"
$ALOHOMORA_BIN cast --region ap-south-1 --env $APP_MODE --app auth "dockerconf/auth.nginx.conf.j2"
echo "Copying nginx conf"
cp dockerconf/auth.nginx.conf /etc/nginx/conf.d/auth.conf

devserve="${DEV_SERVE:-false}"

# Env files
if [[ "${APP_MODE}" == "dev" ]]; then
  cp environment/.env.docker environment/.env.testing && \
  cp environment/env.sample.php environment/env.php && \
  sed -i 's/dev/testing/g' ./environment/env.php
elif [ ! "$devserve" == "true" ]; then
  # casting alohomora to unlock the secrets
  $ALOHOMORA_BIN cast --region ap-south-1 --env $APP_MODE --app auth "environment/env.php.j2" "environment/.env.vault.j2"
fi

## Enable stdout logging
TRACE_LOG_PATH="/app/storage/logs/$HOSTNAME-trace.log"
LARAVEL_LOG_PATH="/app/storage/logs/laravel.log"
if [ ! -f $TRACE_LOG_PATH ]
then
    touch $TRACE_LOG_PATH && chmod 777 $TRACE_LOG_PATH
fi
if [ ! -f $LARAVEL_LOG_PATH ]
then
    touch $LARAVEL_LOG_PATH && chmod 777 $LARAVEL_LOG_PATH
fi

# Fix permissions
echo  "Fix storage permissions"
cd /app/ && chmod 777 -R storage

tail -f $TRACE_LOG_PATH >> /dev/stdout 2>&1 &
tail -f $LARAVEL_LOG_PATH >> /dev/stdout 2>&1 &

# Run Migrations
echo  "Running DB migrate"
php artisan migrate --force

# If ARTISAN_COMMAND env is set, run the command and exit
if [ -n "${ARTISAN_COMMAND+x}" ]; then
  echo "Running ${ARTISAN_COMMAND}"
  cd /app
  php artisan "$ARTISAN_COMMAND"
  exit 0
fi

# Fix permissions
echo  "Fix file owner"
chown -R nginx.nginx /app

echo "Starting Auth Service - PHP-FPM"
/usr/sbin/php-fpm81
echo "Starting Auth Service - Nginx"
/usr/sbin/nginx -g 'daemon off;'
