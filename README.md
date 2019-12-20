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
