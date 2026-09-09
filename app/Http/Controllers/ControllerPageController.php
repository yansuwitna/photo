<?php

namespace App\Http\Controllers;

use App\Models\BoothSession;
use App\Models\Template;
use App\Models\Payment;
use App\Models\PrintJob;
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

        return Inertia::render('Controller/Index', [
            'activeSession' => $activeSession,
            'templates' => $templates,
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

        return response()->json([
            'active_session' => $activeSession,
        ]);
    }
}