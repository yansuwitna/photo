<?php

namespace App\Http\Controllers;

use App\Models\BoothSession;
use App\Models\Template;
use App\Models\Payment;
use App\Models\PrintJob;
use App\Models\Camera;
use App\Models\Printer;
use App\Models\Setting;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;

class ControllerPageController extends Controller
{
    public function index(): Response
    {
        $activeSession = BoothSession::with(['event', 'template', 'photos'])
            ->whereIn('status', ['init', 'template_selected', 'capturing', 'reviewing', 'composing', 'ready_to_print'])
            ->latest()
            ->first();

        $templates = Template::where('is_active', true)->get();

        $todaySessions = BoothSession::whereDate('created_at', today())->count();
        $todayPrints = PrintJob::whereDate('created_at', today())->where('status', 'completed')->sum('copies');
        $todayRevenue = Payment::whereDate('created_at', today())->where('status', 'paid')->sum('total_amount');

        $cameras = Camera::all();
        $printers = Printer::all();
        $activeCameraId = Setting::get('active_camera_id');
        $activePrinterId = Setting::get('active_printer_id');
        $activeCamera = ($activeCameraId ? Camera::find($activeCameraId) : null) ?? Camera::where('is_default', true)->first() ?? $cameras->first();
        $activePrinter = ($activePrinterId ? Printer::find($activePrinterId) : null) ?? Printer::where('is_default', true)->first() ?? $printers->first();
        $activePaperSize = Setting::get('active_printer_paper_size', $activePrinter?->default_paper_size ?? '4R');
        $isLocked = (bool)Setting::get('device_settings_locked', false);

        return Inertia::render('Controller/Index', [
            'activeSession' => $activeSession,
            'templates' => $templates,
            'cameras' => $cameras,
            'printers' => $printers,
            'activeCamera' => $activeCamera,
            'activePrinter' => $activePrinter,
            'activePaperSize' => $activePaperSize,
            'isLocked' => $isLocked,
            'todayStats' => [
                'total_sessions' => $todaySessions,
                'total_prints' => (int)$todayPrints,
                'revenue' => (float)$todayRevenue,
            ]
        ]);
    }

    public function status(): JsonResponse
    {
        $activeSession = BoothSession::with(['event', 'template', 'photos'])
            ->whereIn('status', ['init', 'template_selected', 'capturing', 'reviewing', 'composing', 'ready_to_print'])
            ->latest()
            ->first();

        $activeCameraId = Setting::get('active_camera_id');
        $activePrinterId = Setting::get('active_printer_id');
        $activeCamera = ($activeCameraId ? Camera::find($activeCameraId) : null) ?? Camera::where('is_default', true)->first();
        $activePrinter = ($activePrinterId ? Printer::find($activePrinterId) : null) ?? Printer::where('is_default', true)->first();
        $activePaperSize = Setting::get('active_printer_paper_size', $activePrinter?->default_paper_size ?? '4R');
        $isLocked = (bool)Setting::get('device_settings_locked', false);

        return response()->json([
            'active_session' => $activeSession,
            'active_camera' => $activeCamera,
            'active_printer' => $activePrinter,
            'active_paper_size' => $activePaperSize,
            'is_locked' => $isLocked,
        ]);
    }
}