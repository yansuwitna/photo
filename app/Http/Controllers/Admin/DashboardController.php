<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BoothSession;
use App\Models\Template;
use App\Models\Event;
use App\Models\PrintJob;
use App\Models\Payment;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $todaySessions = BoothSession::whereDate('created_at', today())->count();
        $todayPrints = PrintJob::whereDate('created_at', today())->where('status', 'completed')->sum('copies');
        $todayRevenue = Payment::whereDate('created_at', today())->where('status', 'paid')->sum('total_amount');
        $activeTemplates = Template::where('is_active', true)->count();

        $recentSessions = BoothSession::with(['template', 'event'])
            ->latest()
            ->take(10)
            ->get();

        $activeEvent = Event::where('is_active', true)->first();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'today_sessions' => $todaySessions,
                'today_prints' => (int)$todayPrints,
                'today_revenue' => (float)$todayRevenue,
                'active_templates_count' => $activeTemplates,
            ],
            'recentSessions' => $recentSessions,
            'activeEvent' => $activeEvent,
        ]);
    }
}