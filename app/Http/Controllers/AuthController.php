<?php

namespace App\Http\Controllers;

use Trace;
use Redirect;
use Request;
use App\Models\Auth;
use App\Constants\TraceCode;

class AuthController extends Controller
{
    protected $authService;

    public function __construct()
    {
        $this->authService = new Auth\Service();
    }

    public function getRoot()
    {
        Trace::info(TraceCode::API_REQUEST, []);

        $response['message'] = 'Welcome to Razorpay Auth!';

        return response()->json($response);
    }

    public function getAuthorize()
    {
        $input = Request::all();

        (new Auth\Validator)->validateInput('auth_code', $input);

        Trace::info(TraceCode::AUTH_AUTHORIZE_AUTH_CODE_REQUEST, $input);

        return view('authorize')->with('input', $input);
    }

    public function postAuthorize()
    {
        $input = Request::all();

        $authCode = $this->authService->postAuthCode($input);

        return response()->json($authCode->getHeaders()['Location'][0]);
    }

    public function postAccessToken()
    {
        $input = Request::all();

        $response = $this->authService->generateAccessToken($input);

        return response()->json($response);
    }

    public function getTokenData($token)
    {
        $response = (new Auth\Service)->getTokenData($token);

        return response()->json($response);
    }
}

