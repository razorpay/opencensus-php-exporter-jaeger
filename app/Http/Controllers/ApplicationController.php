<?php

namespace App\Http\Controllers;

use Trace;
use Request;
use App\Constants\TraceCode;
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

        Trace::info(TraceCode::CREATE_APPLICATION_REQUEST, $input);

        $app = $this->service->create($input);

        return response()->json($app);
    }

    public function get(string $id)
    {
        $input = Request::all();

        Trace::info(TraceCode::GET_APPLICATION_REQUEST, compact('id', 'input'));

        $app = $this->service->fetch($id, $input);

        return response()->json($app);
    }

    public function getMultiple()
    {
        $input = Request::all();

        Trace::info(TraceCode::GET_APPLICATIONS_REQUEST, $input);

        $apps = $this->service->fetchMultiple($input);

        return response()->json($apps);
    }

    public function delete(string $id)
    {
        $input = Request::all();

        Trace::info(TraceCode::DELETE_APPLICATION_REQUEST, compact('id', 'input'));

        $this->service->delete($id, $input);

        return response()->json([]);
    }

    public function update(string $id)
    {
        $input = Request::all();

        Trace::info(TraceCode::UPDATE_APPLICATION_REQUEST, compact('id', 'input'));

        $app = $this->service->update($id, $input);

        return response()->json($app);
    }

//    TODO: Revert this after aggregator to reseller migration is complete (PLAT-33)
    public function restore() {
        $input = Request::all();

        Trace::info(TraceCode::RESTORE_APPLICATION_REQUEST, $input);

        $this->service->restoreAndDeleteMultiple($input);

        return response()->json([]);
    }

    public function getSubmerchantApplications()
    {
        $input = Request::all();

        Trace::info(TraceCode::GET_SUBMERCHANT_APPLICATIONS_REQUEST, $input);

        $apps = $this->service->fetchSubmerchantApplications($input);

        return response()->json($apps);
    }
}
