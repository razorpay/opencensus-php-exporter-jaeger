<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;

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
        if (($request->header('PHP_AUTH_USER', false) !== 'rzp') or
            ($request->header('PHP_AUTH_PW') !== env('APP_API_SECRET')))
        {
            return response('Unauthorized.', 401);
        }

        return $next($request);
    }
}
