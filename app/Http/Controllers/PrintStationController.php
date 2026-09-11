<?php

namespace App\Http\Controllers;

use App\Models\BoothSession;
use App\Models\DeviceLog;
use App\Models\Printer;
use App\Models\PrintJob;
use App\Models\Setting;
use App\Services\Hardware\Adapters\Printer\WindowsPrinterAdapter;
use App\Services\Hardware\PrinterManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $activePrinter = $printerManager->getPrinterForBooth($selectedBooth);
        $activePaperSize = $printerManager->getActivePaperSize(null, $selectedBooth);
        $printers = Printer::all();

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
            'printers' => $printers,
            'web_station_enabled' => true,
            'selected_booth' => $selectedBooth,
            'available_booths' => $availableBooths,
            'server_os' => PHP_OS_FAMILY,
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
        $activePrinter = $printerManager->getPrinterForBooth($booth);
        $activePaperSize = $printerManager->getActivePaperSize(null, $booth);
        $printers = Printer::all();

        return response()->json([
            'success' => true,
            'selected_booth' => $booth ?: 'all',
            'pending_jobs' => $pendingJobs,
            'recent_jobs' => $recentJobs,
            'active_printer' => $activePrinter,
            'active_paper_size' => $activePaperSize,
            'printers' => $printers,
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

    public function selectPrinter(Request $request): JsonResponse
    {
        $boothId = $request->input('booth_id', 'STAND-01');
        $printerId = (int)$request->input('printer_id');
        $paperSize = $request->input('paper_size');

        $printer = Printer::findOrFail($printerId);

        if ($boothId && $boothId !== 'all') {
            Setting::updateOrCreate(
                ['key' => "booth_printer_{$boothId}"],
                ['group' => 'hardware', 'value' => (string)$printerId, 'type' => 'integer', 'label' => "Printer untuk {$boothId}"]
            );
            if ($paperSize) {
                Setting::updateOrCreate(
                    ['key' => "booth_paper_size_{$boothId}"],
                    ['group' => 'hardware', 'value' => (string)$paperSize, 'type' => 'string', 'label' => "Ukuran Kertas {$boothId}"]
                );
            }
        } else {
            Setting::updateOrCreate(
                ['key' => 'active_printer_id'],
                ['group' => 'hardware', 'value' => (string)$printerId, 'type' => 'integer', 'label' => 'ID Printer Aktif']
            );
            if ($paperSize) {
                Setting::updateOrCreate(
                    ['key' => 'active_printer_paper_size'],
                    ['group' => 'hardware', 'value' => (string)$paperSize, 'type' => 'string', 'label' => 'Ukuran Kertas Printer Aktif']
                );
            }
        }

        $printerManager = new PrinterManager();
        $activePrinter = $printerManager->getPrinterForBooth($boothId);
        $activePaperSize = $printerManager->getActivePaperSize(null, $boothId);

        return response()->json([
            'success' => true,
            'message' => "Printer untuk {$boothId} berhasil diatur ke '{$printer->name}'.",
            'active_printer' => $activePrinter,
            'active_paper_size' => $activePaperSize,
            'booth_id' => $boothId,
        ]);
    }

    public function syncPrinters(): JsonResponse
    {
        try {
            app(\App\Http\Controllers\Api\DeviceApiController::class)->autoSyncPrinters(true);
        } catch (\Throwable $e) {}

        return response()->json([
            'success' => true,
            'message' => 'Daftar printer sistem operasi berhasil diperbarui.',
            'printers' => Printer::all(),
        ]);
    }

    public function testPrint(Request $request): JsonResponse
    {
        $copies = (int)$request->input('copies', 1);
        $boothId = $request->input('booth_id', 'STAND-01');
        $printerManager = new PrinterManager();
        $printer = $printerManager->getPrinterForBooth($boothId);
        $paperSize = $request->input('paper_size') ?: $printerManager->getActivePaperSize(null, $boothId);

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

    /**
     * Eksekusi cetak langsung ke spooler printer Windows tanpa membuka dialog Web Print browser.
     */
    public function printDirect(Request $request, int $jobId): JsonResponse
    {
        $job = PrintJob::with(['session', 'printer'])->findOrFail($jobId);

        $session = $job->session;
        $finalPath = $session?->final_photo_path;
        if (!$finalPath && file_exists(storage_path('app/public/tests/print_station_test.png'))) {
            $finalPath = 'tests/print_station_test.png';
        }

        if (!$finalPath) {
            $job->update([
                'status' => 'failed',
                'error_message' => 'Path file foto tidak ditemukan untuk dicetak.',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Path file foto tidak ditemukan untuk dicetak.',
            ], 404);
        }

        // Resolusi file fisik foto di server
        $fullPath = null;
        $cleanPath = ltrim(preg_replace('/^(public\/|\/storage\/|storage\/)/', '', $finalPath), '/');

        if (Storage::disk('public')->exists($cleanPath)) {
            $fullPath = Storage::disk('public')->path($cleanPath);
        } elseif (file_exists($finalPath)) {
            $fullPath = $finalPath;
        } elseif (file_exists(storage_path('app/' . $finalPath))) {
            $fullPath = storage_path('app/' . $finalPath);
        } elseif (file_exists(public_path($finalPath))) {
            $fullPath = public_path($finalPath);
        }

        if (!$fullPath || !file_exists($fullPath)) {
            $job->update([
                'status' => 'failed',
                'error_message' => "File fisik foto tidak ditemukan di server: {$finalPath}",
            ]);
            return response()->json([
                'success' => false,
                'message' => "File fisik foto tidak ditemukan di server: {$finalPath}",
            ], 404);
        }

        $printerManager = new PrinterManager();
        $targetBooth = $request->input('booth_id') ?: ($job->booth_id ?: ($session?->booth_id ?: 'STAND-01'));

        $printer = null;
        if ($request->filled('printer_id')) {
            $printer = Printer::find($request->input('printer_id'));
        }
        if (!$printer && $job->printer) {
            $printer = $job->printer;
        }
        if (!$printer) {
            $printer = $printerManager->getPrinterForBooth($targetBooth);
        }

        $copies = max(1, (int)($request->input('copies') ?: ($job->copies ?: 1)));
        $paperSize = $request->input('paper_size') ?: ($job->paper_size ?: $printerManager->getActivePaperSize(null, $targetBooth));

        $job->update([
            'status' => 'printing',
            'progress' => 40,
            'started_at' => now(),
            'printer_id' => $printer?->id ?? $job->printer_id,
        ]);

        $adapter = new WindowsPrinterAdapter($printer?->name);
        $result = $adapter->print($fullPath, $copies, $paperSize);

        if (!empty($result['success'])) {
            $job->update([
                'status' => 'completed',
                'progress' => 100,
                'error_message' => null,
                'completed_at' => now(),
            ]);

            if ($session) {
                $session->update([
                    'print_status' => 'printed',
                    'printer_id' => $printer?->id ?? $session->printer_id,
                ]);
            }

            try {
                DeviceLog::create([
                    'device_type' => 'printer',
                    'device_id' => (string)($printer?->id ?? 'windows'),
                    'event' => 'printer.direct_printed',
                    'message' => "Cetak langsung berhasil untuk Job #{$job->id} ke printer " . ($printer?->name ?? 'Default Windows'),
                    'severity' => 'info',
                    'payload' => $result,
                ]);
            } catch (\Throwable $e) {}

            return response()->json([
                'success' => true,
                'message' => $result['message'] ?? ("Pencetakan langsung ke printer " . ($printer?->name ?? 'Windows') . " berhasil!"),
                'printer' => $printer?->name,
                'copies' => $copies,
                'paper_size' => $paperSize,
                'job' => $job,
            ]);
        } else {
            $job->update([
                'status' => 'failed',
                'error_message' => $result['message'] ?? 'Gagal mencetak langsung ke printer Windows.',
            ]);

            if ($session) {
                $session->update([
                    'print_status' => 'failed',
                    'error_message' => $result['message'] ?? 'Gagal mencetak langsung ke printer Windows.',
                ]);
            }

            try {
                DeviceLog::create([
                    'device_type' => 'printer',
                    'device_id' => (string)($printer?->id ?? 'windows'),
                    'event' => 'printer.direct_failed',
                    'message' => "Cetak langsung gagal untuk Job #{$job->id}: " . ($result['message'] ?? 'Unknown error'),
                    'severity' => 'error',
                    'payload' => $result,
                ]);
            } catch (\Throwable $e) {}

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Gagal mencetak langsung ke printer Windows.',
                'printer' => $printer?->name,
                'job' => $job,
            ], 500);
        }
    }
}
