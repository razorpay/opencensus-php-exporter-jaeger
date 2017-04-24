<?php

namespace App\Http\Controllers;

use \Razorpay\OAuth as OAuth;

class AuthController extends Controller
{
    protected $authService;

    public function __construct()
    {
        $this->authService = new OAuth\Service();
    }

    public function getRoot()
    {
        $response['message'] = 'Welcome to Auth Service!';

        return response()->json($response);
    }

    public function authorize()
    {
        $input = Request::all();

        $data = $this->authService->getAuthCode($input);

        return response()->json($data);
    }

    public function getAccessToken()
    {
        $input = Request::all();

        $data = $this->authService->getAccessToken($input);

        return response()->json($data);
    }
}
