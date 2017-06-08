# Auth Microservice

Razorpay's Auth Microservice - Powers OAuth and auth.razorpay.com

## Testing

- Create database `auth_test`
- Copy over `environment/.env.sample` to `environment/.env.testing` and configure
DB name and connection info.
- Run `APP_ENV=testing php artisan rzp:migrate` first to run migrations on test
- To refresh the database, run the following command
```
APP_ENV=testing php artisan migrate:reset && php artisan rzp:migrate
```
- Run `phpunit --debug` for tests
