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
        $activeCameraId = Setting::get('active_camera_id');
        $activePrinterId = Setting::get('active_printer_id');
        $activeCamera = ($activeCameraId ? Camera::find($activeCameraId) : null) ?? Camera::where('is_default', true)->first() ?? $cameras->first();
        $activePrinter = ($activePrinterId ? Printer::find($activePrinterId) : null) ?? Printer::where('is_default', true)->first() ?? $printers->first();
        $activePaperSize = Setting::get('active_printer_paper_size', $activePrinter?->default_paper_size ?? '4R');
        $isLocked = (bool)Setting::get('device_settings_locked', false);

        return Inertia::render('Admin/Devices/Index', [
            'cameras' => $cameras,
            'printers' => $printers,
            'activeCamera' => $activeCamera,
            'activePrinter' => $activePrinter,
            'activePaperSize' => $activePaperSize,
            'isLocked' => $isLocked,
            'devices' => Device::all(),
        ]);
    }

    public function compatibility(): Response
    {
        return Inertia::render('Admin/Devices/Compatibility');
    }
}