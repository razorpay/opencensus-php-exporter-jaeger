<?php

namespace App\Http\Controllers;

use Trace;
use Request;
use App\Constants\Metric;
use App\Constants\TraceCode;
use Razorpay\OAuth\Client;

class ClientController extends Controller
{
    public function __construct()
    {
        $this->service = new Client\Service;
    }

    public function createClients()
    {
        $input = Request::all();

        Trace::info(TraceCode::CREATE_CLIENTS_REQUEST, $input);

        $app = $this->service->create($input);

        return response()->json($app);
    }

    public function delete(string $id)
    {
        $input = Request::all();

        Trace::info(TraceCode::DELETE_CLIENT_REQUEST, compact('id', 'input'));

        $this->service->delete($id, $input);

        return response()->json([]);
    }

    public function refreshClients()
    {
        $input = Request::all();

        Trace::info(TraceCode::REFRESH_CLIENTS_REQUEST, $input);

        try
        {
            $app = $this->service->refreshClients($input);

            app('trace')->count(Metric::REFRESH_CLIENTS_SUCCESS_COUNT);
        }
        catch (\Throwable $e)
        {
            $tracePayload = [
                'class'   => get_class($e),
                'code'    => $e->getCode(),
                'message' => $e->getMessage(),
            ];

            Trace::critical(TraceCode::REFRESH_CLIENTS_REQUEST_FAILURE, $tracePayload);

            app('trace')->count(Metric::REFRESH_CLIENTS_FAILURE_COUNT);

            throw $e;
        }

        return response()->json($app);
    }
}
