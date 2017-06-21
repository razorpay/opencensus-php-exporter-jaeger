<?php

require_once __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    realpath(__DIR__.'/../')
);

/*
 |-------------------------------------------------------------------------
 | Load Environment Configuration
 |-------------------------------------------------------------------------
 */

require __DIR__ . '/environment.php';

/*
 |-------------------------------------------------------------------------
 | Define the paths required by the app
 |-------------------------------------------------------------------------
 */

$app->instance('path.config', $app->getConfigurationPath());

$app->instance('path.storage', app()->basePath() . DIRECTORY_SEPARATOR . 'storage');

//
// Use Facades for now, will attempt to drop this later
//
$app->withFacades();

$app->withEloquent();

if (class_exists('Redirect') === false)
{
	class_alias('Laravel\Lumen\Http\Redirector', 'Redirect');
}

if (class_exists('Trace') === false)
{
    class_alias(Razorpay\Trace\Facades\Trace::class, 'Trace');
}

//
// App and Request facades are required by the Trace facade :(
//
if (class_exists('Request') === false)
{
    class_alias(Illuminate\Support\Facades\Request::class, 'Request');
}

if (class_exists('App') === false)
{
    class_alias('Illuminate\Support\Facades\App', 'App');
}

if (class_exists('Crypt') === false)
{
    class_alias('Illuminate\Support\Facades\Crypt', 'Crypt');
}

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exception\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

// $app->middleware([
//    App\Http\Middleware\ExampleMiddleware::class
// ]);

// $app->routeMiddleware([
//     'auth' => App\Http\Middleware\Authenticate::class,
// ]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

$app->register(App\Providers\AppServiceProvider::class);
// $app->register(App\Providers\AuthServiceProvider::class);
// $app->register(App\Providers\EventServiceProvider::class);

$app->register(\Razorpay\Trace\TraceServiceProviderLaravel5::class);

$app->register(\Razorpay\OAuth\OAuthServiceProvider::class);

$app->configure('trace');

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->group(['namespace' => 'App\Http\Controllers'], function ($app) {
    require __DIR__ . '/../routes/web.php';
});

return $app;
