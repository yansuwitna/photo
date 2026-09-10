<?php

namespace App\Http\Controllers;

use App\Models\BoothSession;
use App\Models\Printer;
use App\Models\PrintJob;
use App\Models\Setting;
use App\Services\Hardware\PrinterManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PrintStationController extends Controller
{
    public function index(Request $request): Response
    {
        // Otomatis aktifkan flag web print station jika diakses
        Setting::set('web_print_station_enabled', '1', 'hardware');

        $selectedBooth = $request->query('booth', 'STAND-01');

        $printerManager = new PrinterManager();
        $activePrinter = $printerManager->getPrinterModel();
        $activePaperSize = $printerManager->getActivePaperSize();

        $todayJobsQuery = PrintJob::whereDate('created_at', today());
        $todayCompletedQuery = PrintJob::whereDate('created_at', today())->where('status', 'completed');

        if ($selectedBooth && $selectedBooth !== 'all') {
            $todayJobsQuery->where(function($q) use ($selectedBooth) {
                $q->where('booth_id', $selectedBooth)->orWhereNull('booth_id');
            });
            $todayCompletedQuery->where(function($q) use ($selectedBooth) {
                $q->where('booth_id', $selectedBooth)->orWhereNull('booth_id');
            });
        }

        $todayJobsCount = $todayJobsQuery->count();
        $todayCompletedCount = $todayCompletedQuery->sum('copies');

        $availableBooths = collect(['STAND-01', 'STAND-02'])
            ->merge(BoothSession::whereNotNull('booth_id')->distinct()->pluck('booth_id'))
            ->merge(PrintJob::whereNotNull('booth_id')->distinct()->pluck('booth_id'))
            ->unique()
            ->values()
            ->toArray();

        return Inertia::render('PrintStation/Index', [
            'active_printer' => $activePrinter,
            'active_paper_size' => $activePaperSize,
            'web_station_enabled' => true,
            'selected_booth' => $selectedBooth,
            'available_booths' => $availableBooths,
            'stats' => [
                'today_jobs' => $todayJobsCount,
                'today_completed' => (int)$todayCompletedCount,
            ],
        ]);
    }

    public function jobs(Request $request): JsonResponse
    {
        $booth = $request->query('booth');

        $pendingQuery = PrintJob::with(['session.template', 'session.event', 'printer'])
            ->whereIn('status', ['pending', 'queued']);

        $recentQuery = PrintJob::with(['session.template', 'printer'])
            ->whereIn('status', ['completed', 'printing', 'failed']);

        if ($booth && $booth !== 'all') {
            $pendingQuery->where(function($q) use ($booth) {
                $q->where('booth_id', $booth)->orWhereNull('booth_id');
            });
            $recentQuery->where(function($q) use ($booth) {
                $q->where('booth_id', $booth)->orWhereNull('booth_id');
            });
        }

        $pendingJobs = $pendingQuery
            ->orderBy('id')
            ->get()
            ->map(function ($j) {
                $session = $j->session;
                $finalPath = $session?->final_photo_path;
                if (!$finalPath && file_exists(storage_path('app/public/tests/print_station_test.png'))) {
                    $finalPath = 'tests/print_station_test.png';
                }
                $fileUrl = $finalPath ? '/storage/' . ltrim(str_replace('public/', '', $finalPath), '/') : null;
                return [
                    'id' => $j->id,
                    'session_id' => $j->session_id,
                    'session_code' => $session?->session_code ?? "JOB-{$j->id}",
                    'event_name' => $session?->event?->name ?? 'Photobooth Event',
                    'booth_id' => $j->booth_id ?: ($session?->booth_id ?: 'STAND-01'),
                    'copies' => $j->copies ?: 1,
                    'paper_size' => $j->paper_size ?: '4R',
                    'printer_name' => $j->printer?->name ?? 'Default Local Printer',
                    'file_url' => $fileUrl,
                    'status' => $j->status,
                    'created_at' => $j->created_at?->format('H:i:s'),
                ];
            });

        $recentJobs = $recentQuery
            ->orderByDesc('id')
            ->take(15)
            ->get()
            ->map(function ($j) {
                $session = $j->session;
                $finalPath = $session?->final_photo_path;
                if (!$finalPath && file_exists(storage_path('app/public/tests/print_station_test.png'))) {
                    $finalPath = 'tests/print_station_test.png';
                }
                $fileUrl = $finalPath ? '/storage/' . ltrim(str_replace('public/', '', $finalPath), '/') : null;
                return [
                    'id' => $j->id,
                    'session_id' => $j->session_id,
                    'session_code' => $session?->session_code ?? "JOB-{$j->id}",
                    'booth_id' => $j->booth_id ?: ($session?->booth_id ?: 'STAND-01'),
                    'copies' => $j->copies ?: 1,
                    'paper_size' => $j->paper_size ?: '4R',
                    'printer_name' => $j->printer?->name ?? 'Default Local Printer',
                    'file_url' => $fileUrl,
                    'status' => $j->status,
                    'error_message' => $j->error_message,
                    'created_at' => $j->created_at?->format('H:i:s'),
                    'completed_at' => $j->completed_at?->format('H:i:s'),
                ];
            });

        $printerManager = new PrinterManager();
        $activePrinter = $printerManager->getPrinterModel();
        $activePaperSize = $printerManager->getActivePaperSize();

        return response()->json([
            'success' => true,
            'selected_booth' => $booth ?: 'all',
            'pending_jobs' => $pendingJobs,
            'recent_jobs' => $recentJobs,
            'active_printer' => $activePrinter,
            'active_paper_size' => $activePaperSize,
            'web_station_enabled' => (bool)Setting::get('web_print_station_enabled', true),
        ]);
    }

    public function toggle(Request $request): JsonResponse
    {
        $enabled = $request->boolean('enabled');
        Setting::set('web_print_station_enabled', $enabled ? '1' : '0', 'hardware');

        return response()->json([
            'success' => true,
            'enabled' => $enabled,
            'message' => $enabled 
                ? 'Web Print Station diaktifkan. Semua cetakan dari Kiosk akan masuk ke browser PC ini.' 
                : 'Web Print Station dinonaktifkan.',
        ]);
    }

    public function testPrint(Request $request): JsonResponse
    {
        $copies = (int)$request->input('copies', 1);
        $paperSize = $request->input('paper_size') ?: (new PrinterManager())->getActivePaperSize();
        $printer = (new PrinterManager())->getPrinterModel();

        // Pastikan dummy image test tersedia
        $testRelPath = 'tests/print_station_test.png';
        $fullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($testRelPath);
        if (!file_exists($fullPath)) {
            $dir = dirname($fullPath);
            if (!is_dir($dir)) mkdir($dir, 0755, true);

            $im = imagecreatetruecolor(1200, 1800);
            $bg = imagecolorallocate($im, 15, 23, 42); // slate-900
            $gold = imagecolorallocate($im, 245, 158, 11); // amber-500
            $cyan = imagecolorallocate($im, 6, 182, 212); // cyan-500
            $white = imagecolorallocate($im, 255, 255, 255);
            $gray = imagecolorallocate($im, 148, 163, 184);

            imagefilledrectangle($im, 0, 0, 1200, 1800, $bg);
            imagefilledrectangle($im, 40, 40, 1160, 1760, imagecolorallocate($im, 30, 41, 59));
            imagerectangle($im, 40, 40, 1160, 1760, $gold);

            imagestring($im, 5, 380, 750, "PHOTOBOOTH PRO - TEST PRINT", $gold);
            imagestring($im, 5, 420, 850, "WEB PRINT STATION (VUE 3)", $cyan);
            imagestring($im, 4, 380, 950, "Printer: " . ($printer?->name ?? 'Default Windows Printer'), $white);
            imagestring($im, 4, 460, 1000, "Ukuran Kertas: " . $paperSize, $white);
            imagestring($im, 4, 430, 1050, "Waktu: " . date('Y-m-d H:i:s'), $gray);

            imagepng($im, $fullPath);
            imagedestroy($im);
        }

        $boothId = $request->input('booth_id', 'STAND-01');

        // Cari sesi dummy atau sesi terakhir untuk relasi
        $session = BoothSession::latest()->first();

        $job = PrintJob::create([
            'session_id' => $session?->id,
            'printer_id' => $printer?->id,
            'booth_id' => $boothId,
            'copies' => $copies,
            'paper_size' => $paperSize,
            'status' => 'pending',
            'progress' => 0,
            'started_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Uji cetak berhasil ditambahkan ke antrean Web Print Station!',
            'job_id' => $job->id,
        ]);
    }

    public function reprint(int $jobId): JsonResponse
    {
        $job = PrintJob::findOrFail($jobId);
        $job->update([
            'status' => 'pending',
            'progress' => 0,
            'error_message' => null,
            'completed_at' => null,
            'started_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Job #{$jobId} dimasukkan kembali ke antrean cetak.",
            'job' => $job,
        ]);
    }
}
