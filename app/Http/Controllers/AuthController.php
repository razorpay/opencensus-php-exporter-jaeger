<?php

namespace App\Http\Controllers;

use Razorpay\OAuth;

class AuthController extends Controller
{
    public function __construct()
    {
        //
    }

    public function getRoot()
    {
        $response['message'] = config('trace.channel');

        return response()->json($response);
    }

    public function createToken()
    {
        $clientService = new OAuth\Client\Service;

        $data = $clientService->create([]);

        return response()->json($data);
    }
}
