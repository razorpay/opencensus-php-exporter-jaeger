<?php

namespace App\Http\Middleware;

use App\Constants\TraceCode;
use Closure;
use Trace;
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
        if($request->input(Entity::CLIENT_ID)!=null &&$request->input(Entity::CLIENT_ID)!=null)
        {
            try
            {
                $client = (new Client\Repository)->getClientEntity(
                    $request->input(Entity::CLIENT_ID),
                    "",
                    $request->input('client_secret'),
                    true
                );
                return $next($request);
            }
            catch (\Exception $ex)
            {
                $tracePayload = [
                    'code' => $ex->getCode(),
                    'message' => $ex->getMessage(),
                ];

                Trace::info(TraceCode::INVALID_CLIENT_CREDENTIALS, $tracePayload);

                throw $ex;
            }
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
