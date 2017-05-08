<?php

namespace App\Http\Controllers;

use App\Models\Auth;
use Redirect;
use Request;
use Requests;
use Razorpay\OAuth;

class AuthController extends Controller
{
    protected $authService;

    public function __construct()
    {
        $this->authService = new Auth\Service();
    }

    public function getRoot()
    {
        $response['message'] = 'Welcome to Razorpay Auth Service!';

        return response()->json($response);
    }

    public function getAuthorize()
    {
        $input = Request::all();
        //TODO: validate input

        //TODO: Pass the data to view in hidden format so that accept/reject request has the request input

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

        $data = $this->authService->getAccessToken($input);

        return response()->json($data);
    }

    public function getTokenData($token)
    {
        //TODO: Add and move stuff to config files
        $options = array('auth'=> array('rzp_api', 'RANDOM_DASH_PASSWORD'));

        // Move to service
        $response = Requests::get(
            'http://dashboard.razorpay.dev/user/'.$token.'/detail',
            array(),
            $options
        );

        // Maybe do this better?
        $body = json_decode($response->body, true);

        return response()->json($body);
    }
}

