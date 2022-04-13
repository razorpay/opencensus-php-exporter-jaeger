<?php

namespace App\Http\Middleware;

use Closure;
use Razorpay\OAuth\Client;
use Razorpay\OAuth\Token\Entity;


class ApiAuth {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        print_r("bro");

        print_r($request->input(Entity::CLIENT_ID));
        if($request->input(Entity::CLIENT_ID)!=nullOrEmptyString() &&$request->input(Entity::CLIENT_ID)!=nullOrEmptyString())
        {
            try
            {
                $client = (new Client\Repository)->getClientEntity(
                    $request->input(Entity::CLIENT_ID),
                    "",
                    $request->input('client_secret'),
                    true
                );
            }
            catch (\Exception $ex)
            {
                throw $ex;
            }
            return $next($request);

        }

        $username = $request->header('PHP_AUTH_USER', false);
        $password = $request->header('PHP_AUTH_PW');

        if ($this->isAuthenticated($username, $password) === false)
        {
            return response(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }

    public function isAuthenticated($username, $password)
    {
        if (($username === 'rzp') and
            ($password === env('APP_API_SECRET')))
        {
            return true;
        }

        $passwordKey = "AUTH_FOR_" . strtoupper($username);
        $passwordFromEnv = env($passwordKey);
        if (($passwordFromEnv !== null) and
            ($password === $passwordFromEnv))
        {
            return true;
        }

        return false;
    }
}
