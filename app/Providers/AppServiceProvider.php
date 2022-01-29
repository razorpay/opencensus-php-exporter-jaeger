<?php

namespace App\Providers;

use App\Services\Mock;
use App\Exception\Handler;
use App\Services\EdgeService;

use App\Services\RazorX\RazorXClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('Illuminate\Contracts\Routing\ResponseFactory', function ($app) {
            return new \Illuminate\Routing\ResponseFactory(
                $app['Illuminate\Contracts\View\Factory'],
                $app['Illuminate\Routing\Redirector']
            );
        });

        $this->app->bind('exception.handler', function()
        {
            return new Handler();
        });

        $this->app->singleton('edge', function($app)
        {
            $edgeMock = env('EDGE_MOCK', false);

            if ($edgeMock === true)
            {
                return new Mock\EdgeService($app);
            }

            return new EdgeService($app);
        });

        $this->app->singleton('raven', function ($app) {
            $ravenMock = env('APP_RAVEN_MOCK', false);

            if ($ravenMock === true)
            {
                return new \App\Services\Mock\Raven();
            }

            return new \App\Services\Raven();
        });

        $this->app->singleton('razorx', function ($app) {
            $razorxMock = env('RAZORX_MOCK', false);

            if ($razorxMock === true)
            {
                return new Mock\RazorXClient();
            }

            return new RazorXClient();
        });

        $this->registerValidatorResolver();
    }

    protected function registerValidatorResolver()
    {
        $this->app['validator']->resolver(function($translator, $data, $rules, $messages, $customAttributes)
        {
            return new \Razorpay\Spine\Validation\LaravelValidatorEx(
                $translator, $data, $rules, $messages, $customAttributes
            );
        });
    }
}
