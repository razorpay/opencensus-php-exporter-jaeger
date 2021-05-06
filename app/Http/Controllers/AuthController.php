<?php

namespace App\Http\Controllers;

use Request;
use Illuminate\Contracts\Debug\ExceptionHandler;

use App\Models\Auth;
use App\Exception\BadRequestException;
use Razorpay\OAuth\Exception\BadRequestException as OAuthBadRequestException;

use Trace;
use App\Constants\TraceCode;

class AuthController extends Controller
{
    protected $authService;

    public function getRoot()
    {
        $response['message'] = 'Welcome to Razorpay Auth!';

        return response()->json($response);
    }

    public function getStatus()
    {
        try
        {
            if (app('db')->connection('auth')->getPdo())
            {
                $response = [
                    'DB' => 'Ok',
                ];

                return response()->json($response);
            }
        }
        catch (\Throwable $t)
        {
            return response()->json(['error' => 'DB error'], 500);
        }
    }

    public function getAuthorize()
    {
        $input = Request::all();

        try
        {
            $data = $this->service()->getAuthorizeViewData($input);

            $data['query_params'] = request()->getQueryString();

            return view('authorize')->with('data', $data);
        }
        catch (\Throwable $e)
        {
            app(ExceptionHandler::class)->traceException($e);

            return $this->renderAuthorizeError($e);
        }
    }

    public function postAuthorize()
    {
        $input = Request::all();

        $input['permission'] = true;

        $authCode = $this->service()->postAuthCode($input);

        return response()->redirectTo($authCode);
    }

    public function postNativeAuthorize()
    {
        $input = Request::all();

        $input['permission'] = true;

        $response = $this->service()->validateNativeAuthUserAndSendOtp($input);

        return response()->json($response);
    }

    public function deleteAuthorize()
    {
        $input = Request::all();

        $input['permission'] = false;

        $authCode = $this->service()->postAuthCode($input);

        return response()->redirectTo($authCode);
    }

    public function postAccessToken()
    {
        $input = Request::all();

        $response = $this->service()->generateAccessToken($input);

        return response()->json($response);
    }

    public function createPartnerToken()
    {
        $input = Request::all();

        $response = $this->service()->postAuthCodeAndGenerateAccessToken($input);

        return response()->json($response);
    }

    public function createNativeToken()
    {
        $input = Request::all();

        $response = $this->service()->generateNativeAuthAccessToken($input);

        return response()->json($response);
    }

    protected function renderAuthorizeError(\Throwable $e)
    {
        $message = 'A server error occurred while serving this request.';

        //
        // For local dev, print all errors
        // For beta/prod, If the exception is an instance of the following,
        // the exception message is public!
        // For all other exceptions, we send a generic error message
        //
        if ((env('APP_DEBUG') === true) or
            (($e instanceof BadRequestException) or
            ($e instanceof OAuthBadRequestException)))
        {
            $message = $e->getMessage();
        }

        $error = [
            'message' => $message
        ];

        // TODO: Think of displaying the request_id on the error page. Will help resolving issues.
        return view('authorize_error')->with('error', $error);
    }

    protected function service()
    {
        return new Auth\Service();
    }
}

