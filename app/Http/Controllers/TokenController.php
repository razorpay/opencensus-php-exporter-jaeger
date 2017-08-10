<?php

namespace App\Http\Controllers;

use Request;
use Razorpay\OAuth\Token;

class TokenController extends Controller
{
    public function __construct()
    {
        $this->service = new Token\Service;
    }

    public function getAll()
    {
        $input = Request::all();

        $tokens = $this->service->getAllTokens($input);

        return response()->json($tokens);
    }

    public function get(string $id)
    {
        $input = Request::all();

        $token = $this->service->getToken($id, $input);

        return response()->json($token);
    }

    public function revoke(string $id)
    {
        $input = Request::all();

        $this->service->revokeToken($id, $input);

        return response()->json([]);
    }
}
