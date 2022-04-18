<?php

namespace App\Http\Middleware;

use App\Constants\TraceCode;
use App\Exception\InvalidArgumentException;
use Closure;
use Trace;
use Razorpay\OAuth\Client;
use Razorpay\OAuth\Token\Entity;

class BasicAuth
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (($request->input(Entity::CLIENT_ID) != null) && ($request->input('client_secret') != null))
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
        else
        {
            throw new InvalidArgumentException(TraceCode::MISSING_CLIENT_CREDENTIALS);
        }
    }
}
