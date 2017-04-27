<?php

namespace App\Http\Controllers;

use App\Auth;
use Redirect;

class AuthController extends Controller
{
    protected $authService;

    public function __construct()
    {
        $this->authService = new Auth\Service();
    }

    public function getRoot()
    {
        $response['message'] = 'Welcome to Auth Service!';
        return redirect()->to('http://www.gmail.com');

        return response()->json($response);
    }

    public function getAuthorize()
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

