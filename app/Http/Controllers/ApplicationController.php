<?php

namespace App\Http\Controllers;

use Request;
use Razorpay\OAuth\Application;

class ApplicationController extends Controller
{
    public function __construct()
    {
        $this->service = new Application\Service;
    }

    public function create()
    {
        $input = Request::all();

        $app = $this->service->create($input);

        return response()->json($app);
    }

    public function get(string $id)
    {
        $input = Request::all();

        $app = $this->service->fetch($id, $input);

        return response()->json($app);
    }

    public function getMultiple()
    {
        $input = Request::all();

        $apps = $this->service->fetchMultiple($input);

        return response()->json($apps);
    }

    public function delete(string $id)
    {
        $input = Request::all();

        $this->service->delete($id, $input);

        return response()->json([]);
    }

    public function update(string $id)
    {
        $input = Request::all();

        $app = $this->service->update($id, $input);

        return response()->json($app);
    }
}
