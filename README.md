# Auth Microservice

[![wercker status](https://app.wercker.com/status/f092b0270510fdc892d8c86ab3d7846b/m/master "wercker status")](https://app.wercker.com/project/byKey/f092b0270510fdc892d8c86ab3d7846b)

Razorpay's Auth Microservice - Powers OAuth and auth.razorpay.com

## Docker setup [local]
- composer install
- copy/overwrite these files
  - changes in entrypoint.sh
     - ```change to #!/bin/sh on first line```
     -    comment ```php artisan migrate --force```
  - changes in auth.nginx.conf.j2
      - server_name resolve : ``` server_name auth.razorpay.in razorpay-auth;```
      - port listen to 8888 ```listen 8888;```
  - set environment/env.php to return `docker`
  - cp environment/.env.sample environment/.env.docker
     set below values accordingly in .env.docker file
     - `APP_ENV=testing`
     - `APP_DEBUG=true`
     - `DB_HOST=api_api_db_1`
     - change `APP_API_URL` accordingly.

- `docker-composer -f docker-composer.dev.yml up -d --build`

## Non docker setup
- open /etc/hosts file and add `127.0.0.1	auth.razorpay.in` in a new line
- open /httpd-vhosts.conf file and add a new virtual hosts for auth
- update `ServerName`, `DocumentRoot` and `Directory` path accordingly
- restart apache server - `sudo apachectl restart`
- create `auth` database (if not already created)
- inside auth-service repo
  - run composer install
  - inside environment folder
    - create env.php file and copy env.sample.php file to env.php
    - create .env.dev file and copy .env.sample to .env.dev
    - set env.php to return `dev` and
      set below values accordingly in .env.dev file
      - `APP_ENV=testing`
      - `APP_DEBUG=true`
      - `DB_HOST=auth.razorpay.in`
      - change `DB_USERNAME`, `DB_PASSWORD` and `DB_PORT` accordingly
      - change `APP_API_URL` accordingly
   - run php artisan migrate
 - go to `auth.razorpay.in` in your browser and check if it works!


## Testing

- Create database `auth_test`
- Copy over `environment/.env.sample` to `environment/.env.testing` and configure
DB name and connection info.
- Run `APP_ENV=testing php artisan migrate` first to run migrations on test
- To refresh the database, run the following command
```
APP_ENV=testing php artisan migrate:reset && php artisan migrate
```
- Run `phpunit --debug` for tests
