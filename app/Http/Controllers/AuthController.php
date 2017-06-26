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

        $data = $this->authService->getAuthorizeViewData($input);

        $data['query_params'] = request()->getQueryString();

        return view('authorize')->with('data', $data);
    }

    public function postAuthorize()
    {
        $input = Request::all();

        $authCode = $this->authService->postAuthCode($input);

        return response()->redirectTo($authCode->getHeaders()['Location'][0]);
    }

    public function postAccessToken()
    {
        $input = Request::all();

        $response = $this->authService->generateAccessToken($input);

        return response()->json($response);
    }

    public function getLoggedIn()
    {
        return view('logged_in');
    }
}

