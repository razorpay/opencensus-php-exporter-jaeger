<?php

namespace App\Providers;

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

        $this->app->singleton('raven', function ($app) {
            $ravenMock = env('APP_RAVEN_MOCK', false);

            if ($ravenMock === true)
            {
                return new \App\Services\Mock\Raven();
            }

            return new \App\Services\Raven();
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
