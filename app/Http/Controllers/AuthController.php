<?php

namespace App\Http\Controllers;

use App\Exception\BadRequestException;
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

        try
        {
            $data = $this->authService->getAuthorizeViewData($input);

            $data['query_params'] = request()->getQueryString();

            return view('authorize')->with('data', $data);
        }
        catch (\Exception $e)
        {
            return $this->renderAuthorizeError($e);
        }
    }

    public function postAuthorize()
    {
        $input = Request::all();

        $authCode = $this->authService->postAuthCode($input);

        return response()->redirectTo($authCode);
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

    protected function renderAuthorizeError(\Exception $e)
    {
        $message = 'A server error occurred while serving this connection request';

        if (($e instanceof BadRequestException) or
            ($e instanceof BadRequestException))
        {
            $message = $e->getMessage();
        }

        $error = [
            'message' => $message
        ];

        return view('authorize_error')->with('error', $error);
    }
}

