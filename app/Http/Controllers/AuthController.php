<?php

namespace App\Http\Controllers;

use Request;
use App\Models\Auth;
use App\Exception\BadRequestException;
use Razorpay\OAuth\Scope\ScopeConstants;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Razorpay\OAuth\Exception\BadRequestException as OAuthBadRequestException;

class AuthController extends Controller
{
    public const AUTHORIZE_DEFAULT_VIEW = 'authorize';

    public const AUTHORIZE_PG_VIEW = 'authorize_pg';

    public const AUTHORIZE_MULTI_TOKEN_VIEW = 'authorize_multi_token';

    public const AUTHORIZE_ERROR_VIEW = 'authorize_error';

    protected const SCOPE_TO_VIEW_MAP = [
        ScopeConstants::READ_ONLY               => self::AUTHORIZE_PG_VIEW,
        ScopeConstants::READ_WRITE              => self::AUTHORIZE_PG_VIEW,
        ScopeConstants::RX_READ_ONLY            => self::AUTHORIZE_DEFAULT_VIEW,
        ScopeConstants::RX_READ_WRITE           => self::AUTHORIZE_DEFAULT_VIEW,
        ScopeConstants::TALLY_READ_ONLY         => self::AUTHORIZE_DEFAULT_VIEW,
        ScopeConstants::TALLY_READ_WRITE        => self::AUTHORIZE_DEFAULT_VIEW,
        ScopeConstants::APPLE_WATCH_READ_WRITE  => self::AUTHORIZE_DEFAULT_VIEW,
        ScopeConstants::X_MOBILE_APP            => self::AUTHORIZE_DEFAULT_VIEW,
        ScopeConstants::X_MOBILE_APP_2_FA_TOKEN => self::AUTHORIZE_DEFAULT_VIEW,
    ];

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

            $view = $this->getViewForScope(array_keys($data['scopes']));

            $data['query_params'] = request()->getQueryString();

            return view($view)->with('data', $data);
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

    public function postTallyAuthorize()
    {
        $input = Request::all();

        $input['permission'] = true;

        $response = $this->service()->validateTallyUserAndSendOtp($input);

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

    public function createTallyToken()
    {
        $input = Request::all();

        $response = $this->service()->generateTallyAccessToken($input);

        return response()->json($response);
    }

    public function getAuthorizeMultiToken()
    {
        $input = Request::all();

        try
        {
            $data = $this->service()->getAuthorizeMultiTokenViewData($input);

            $data['query_params'] = request()->getQueryString();

            return view(self::AUTHORIZE_MULTI_TOKEN_VIEW)->with('data', $data);
        }
        catch (\Throwable $e)
        {
            app(ExceptionHandler::class)->traceException($e);

            return $this->renderAuthorizeError($e);
        }
    }

    public function postAuthorizeMultiToken()
    {
        $input = Request::all();

        $input['permission'] = true;

        $authCode = $this->service()->postAuthCodeMultiToken($input);

        return response()->redirectTo($authCode);
    }

    public function deleteAuthorizeMultiToken()
    {
        $input = Request::all();

        $input['permission'] = false;

        $authCode = $this->service()->postAuthCodeMultiToken($input);

        return response()->redirectTo($authCode);
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
        return view(self::AUTHORIZE_ERROR_VIEW)->with('error', $error);
    }

    protected function service()
    {
        return new Auth\Service();
    }

    /**
     * Returns the Auth screen template to be used as the view corresponding to the requested scopes.
     * If the requested scope is not mapped to any view then the default view is returned.
     *
     * Currently, we return the template corresponding to the first scope requested by the client.
     *
     * @param array $scopes
     *
     * @return string
     */
    protected function getViewForScope(array $scopes): string
    {
        if (array_key_exists($scopes[0], self::SCOPE_TO_VIEW_MAP) === true)
        {
            return self::SCOPE_TO_VIEW_MAP[$scopes[0]];
        }

        return self::AUTHORIZE_DEFAULT_VIEW;
    }
}

