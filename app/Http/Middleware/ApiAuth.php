<?php

namespace App\Http\Middleware;

use Closure;

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
