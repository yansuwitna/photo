<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\Printer;
use App\Models\Device;
use App\Models\Setting;
use Inertia\Inertia;
use Inertia\Response;

class DeviceAdminController extends Controller
{
    public function index(): Response
    {
        $cameras = Camera::all();
        $printers = Printer::all();
        $activeCamera = Camera::where('is_default', true)->first() ?? $cameras->first();
        $activePrinter = Printer::where('is_default', true)->first() ?? $printers->first();
        $isLocked = (bool)Setting::get('device_settings_locked', false);

        return Inertia::render('Admin/Devices/Index', [
            'cameras' => $cameras,
            'printers' => $printers,
            'activeCamera' => $activeCamera,
            'activePrinter' => $activePrinter,
            'isLocked' => $isLocked,
            'devices' => Device::all(),
        ]);
    }

    public function compatibility(): Response
    {
        return Inertia::render('Admin/Devices/Compatibility');
    }
}