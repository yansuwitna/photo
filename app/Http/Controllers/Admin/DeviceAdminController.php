<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\Printer;
use App\Models\Device;
use Inertia\Inertia;
use Inertia\Response;

class DeviceAdminController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Devices/Index', [
            'cameras' => Camera::all(),
            'printers' => Printer::all(),
            'devices' => Device::all(),
        ]);
    }

    public function compatibility(): Response
    {
        return Inertia::render('Admin/Devices/Compatibility');
    }
}