<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BoothSession;
use App\Models\Payment;
use App\Models\PrintJob;
use App\Models\Template;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(): Response
    {
        $totalSessions = BoothSession::count();
        $totalPrints = (int)PrintJob::where('status', 'completed')->sum('copies');
        $totalRevenue = (float)Payment::where('status', 'paid')->sum('total_amount');

        $popularTemplate = Template::withCount('sessions')->orderByDesc('sessions_count')->first();

        $transactions = Payment::with('session')->latest()->take(50)->get();

        return Inertia::render('Admin/Reports/Index', [
            'stats' => [
                'total_sessions' => $totalSessions,
                'total_prints' => $totalPrints,
                'total_revenue' => $totalRevenue,
                'popular_template' => $popularTemplate ? $popularTemplate->name : 'Classic Strip',
            ],
            'transactions' => $transactions,
        ]);
    }

    public function exportCsv(): StreamedResponse
    {
        $transactions = Payment::with('session')->latest()->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="photobooth_transactions_' . date('Ymd_His') . '.csv"',
        ];

        return response()->stream(function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Ref ID', 'Kode Sesi', 'Metode', 'Subtotal', 'Diskon', 'Total Dibayar', 'Status', 'Tanggal']);

            foreach ($transactions as $t) {
                fputcsv($handle, [
                    $t->reference_number,
                    $t->session?->session_code ?? $t->session_id,
                    $t->method,
                    $t->subtotal,
                    $t->discount_amount,
                    $t->total_amount,
                    $t->status,
                    $t->created_at ? $t->created_at->toDateTimeString() : '',
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }
}