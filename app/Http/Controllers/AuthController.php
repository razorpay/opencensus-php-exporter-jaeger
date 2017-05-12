<?php

namespace App\Http\Controllers;

use Trace;
use Razorpay\OAuth;

use App\Constants\TraceCode;

class AuthController extends Controller
{
    public function __construct()
    {
        //
    }

    public function getRoot()
    {
        Trace::info(TraceCode::API_REQUEST, []);

        $response['message'] = 'Welcome to Razorpay Auth!';

        return response()->json($response);
    }

    public function createToken()
    {
        Trace::info(TraceCode::AUTH_AUTHORIZE_AUTH_CODE_REQUEST, ['test' => 'test']);

        $clientService = new OAuth\Client\Service;

        $data = $clientService->create([]);

        return response()->json($data);
    }
}
