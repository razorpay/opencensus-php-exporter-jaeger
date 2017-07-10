<?php

namespace App\Http\Controllers;

use Request;

use App\Models\Auth;
use App\Exception\BadRequestException;
use Razorpay\OAuth\Exception\BadRequestException as OAuthBadRequestException;

class AuthController extends Controller
{
    protected $authService;

    public function __construct()
    {
        $this->authService = new Auth\Service();
    }

    public function getRoot()
    {
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
        catch (\Throwable $e)
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

    protected function renderAuthorizeError(\Throwable $e)
    {
        $message = 'A server error occurred while serving this request';

        //
        // If the exception is an instance of the following,
        // the exception message is public!
        // For all other exceptions, we send a generic error message
        //
        if (($e instanceof BadRequestException) or
            ($e instanceof OAuthBadRequestException))
        {
            $message = $e->getMessage();
        }

        $error = [
            'message' => $message
        ];

        // TODO: Think of displaying the request_id on the error page. Will help resolving issues.
        return view('authorize_error')->with('error', $error);
    }
}

