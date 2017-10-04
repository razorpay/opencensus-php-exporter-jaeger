#!/usr/bin/env sh
set -euo pipefail

echo "Creating Log Files"
mkdir -p /var/log/nginx
touch /var/log/nginx/auth.access.log
touch /var/log/nginx/auth.error.log

echo $GIT_COMMIT_HASH > /app/commit.txt

ALOHOMORA_BIN=$(which alohomora)

# Lumen in docker picks system environment variable from .env
echo "APP_MODE=${APP_MODE}" > /app/.env

cat /app/.env

$ALOHOMORA_BIN cast --region ap-south-1 --env $APP_MODE --app auth "dockerconf/auth.nginx.conf.j2"
cp dockerconf/auth.nginx.conf /etc/nginx/conf.d/auth.conf

if [[ "${APP_MODE}" == "dev" ]]; then
  cp environment/.env.docker environment/.env.testing && \
  cp environment/env.sample.php environment/env.php && \
  sed -i 's/dev/testing/g' ./environment/env.php && \
  sed -i "s|dev-auth.razorpay.com|auth.razorpay.dev|g" /etc/nginx/conf.d/auth.conf
else
  # casting alohomora to unlock the secrets
  $ALOHOMORA_BIN cast --region ap-south-1 --env $APP_MODE --app auth "environment/.env.vault.j2"
  cp dockerconf/auth.nginx.conf /etc/nginx/conf.d/auth.conf
  $ALOHOMORA_BIN cast --region ap-south-1 --env $APP_MODE --app auth "environment/env.php.j2"
fi

# Fix permissions
echo  "Fix permissions"
cd /app/ && chmod 777 -R storage

## Start the Harvester Process
echo "Starting Auth Service"

echo "GIT_COMMIT_HASH=${GIT_COMMIT_HASH}" >> /app/.env

# Start nginx. The PID file is created since nginx does not start without it
mkdir /run/nginx
touch /run/nginx/nginx.pid
/usr/sbin/php-fpm7
nginx -g 'daemon off;'
