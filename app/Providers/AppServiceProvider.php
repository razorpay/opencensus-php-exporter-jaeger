<?php

namespace App\Providers;

use App\Exception\Handler;
use App\Services\Mock;
use App\Services\EdgeService;

use App\Services\RazorX\RazorXClient;
use App\Http\Middleware\EventTracker;
use Illuminate\Support\ServiceProvider;
use App\Services\Segment\SegmentAnalyticsClient;

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
            //======== not required as of now. uncomment if RazorxMock client is required==============
            //$razorxMock = env('RAZORX_MOCK', false);
            //if ($razorxMock === true)
            //{
            //    return new Mock\RazorXClient();
            //}
            //======== not required as of now. uncomment if RazorxMock client is required==============
            return new RazorXClient();
        });

        $this->app->singleton('segment-analytics', function ($app) {
            return new SegmentAnalyticsClient();
        });

        $this->registerValidatorResolver();

        $this->app->singleton(EventTracker::class);

        //$path = $this->app->getConfigurationPath('jaeger');

        if (__DIR__ . '/../../config/jaeger.php') {
            $this->app->make('config')->set('jaeger', require __DIR__ . '/../../config/jaeger.php');
        }
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
