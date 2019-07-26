<?php

namespace App\Http\Controllers;

use Request;
use App\Models\Admin;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->service = new Admin\Service();
    }

    public function fetchMultipleForAdmin(string $entityType)
    {
        $input = Request::all();

        $apps = $this->service->fetchMultipleForAdmin($entityType, $input);

        return response()->json($apps);
    }

    public function fetchByIdForAdmin(string $entityType, string $entityId)
    {
        $apps = $this->service->fetchByIdForAdmin($entityType, $entityId);

        return response()->json($apps);
    }
}

