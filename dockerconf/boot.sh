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
sed -i "s|NGINX_HOST|$HOSTNAME|g" dockerconf/auth.nginx.conf
cp dockerconf/auth.nginx.conf /etc/nginx/conf.d/auth.conf

## Copy the nginx fpm config. This is applicable across all environments. This is
## needed for getting the OS environment variables where getenv does not get
## all the environment variables in nginx fpm mode. Details
## available at https://stackoverflow.com/questions/19659675/no-environment-variables-are-available-via-php-fpmnginx
cp dockerconf/php-fpm-www.conf /etc/php7/php-fpm.d/www.conf

if [[ "${APP_MODE}" == "dev" ]]; then
  cp environment/.env.docker environment/.env.testing && \
  cp environment/env.sample.php environment/env.php && \
  sed -i 's/dev/testing/g' ./environment/env.php 
else
  # casting alohomora to unlock the secrets
  $ALOHOMORA_BIN cast --region ap-south-1 --env $APP_MODE --app auth "environment/.env.vault.j2"
  cp dockerconf/auth.nginx.conf /etc/nginx/conf.d/auth.conf
  $ALOHOMORA_BIN cast --region ap-south-1 --env $APP_MODE --app auth "environment/env.php.j2"
fi

## enable newrelic only for prod and maybe later on for perf
if [[ "${APP_MODE}" == "prod" ]]; then
  $ALOHOMORA_BIN cast --region ap-south-1 --env $APP_MODE --app auth "dockerconf/newrelic.ini.j2"
  cp dockerconf/newrelic.ini /etc/php7/conf.d/newrelic.ini
fi
    


## opcache settings. These are independent of the environment
## Note: whenever opcache needs to be enabled, uncomment the 
## below settings
#sed -i 's/;opcache.enable=0/opcache.enable=1/g' /etc/php7/php.ini
## below not needed for this app. But just keeping it for consistency
#sed -i 's/;opcache.enable_cli=0/opcache.enable_cli=1/g' /etc/php7/php.ini
#sed -i 's/;opcache.memory_consumption=64/opcache.memory_consumption=256/g' /etc/php7/php.ini
#sed -i 's/;opcache.interned_strings_buffer=4/opcache.interned_strings_buffer=16/g' /etc/php7/php.ini
## Max accelerated files for opcache default is 2k and keeping it that way as per current prod gimli
#sed -i "s/;opcache.error_log=/opcache.error_log=\/var\/log\/nginx\/$HOSTNAME-gimli-opcache-error.log/g" /etc/php7/php.ini
#sed -i 's/;opcache.log_verbosity_level=1/opcache.log_verbosity_level=2/g' /etc/php7/php.ini
#sed -i 's/;opcache.fast_shutdown=0/opcache.fast_shutdown=1/g' /etc/php7/php.ini
#sed -i 's/;opcache.force_restart_timeout=180/opcache.force_restart_timeout=300/g' /etc/php7/php.ini
## End of opcache settings

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
