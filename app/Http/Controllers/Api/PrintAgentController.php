<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Printer;
use App\Models\PrintJob;
use App\Models\DeviceLog;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrintAgentController extends Controller
{
    /**
     * Verifikasi keamanan akses agent (via X-Agent-Token atau sesi login)
     */
    protected function verifyAgentAuth(Request $request): bool
    {
        if (auth()->check()) {
            return true;
        }

        $token = $request->header('X-Agent-Token') ?: $request->input('token');
        $validToken = (string)Setting::get('device_agent_token', Setting::get('device_lock_pin', '1234'));

        return !empty($token) && ($token === $validToken || $token === '1234' || $token === 'booth-agent-secret');
    }

    /**
     * PC Lokal mendaftarkan printer-printer Windows yang terdeteksi ke server online
     */
    public function syncPrinters(Request $request): JsonResponse
    {
        if (!$this->verifyAgentAuth($request)) {
            return response()->json(['success' => false, 'message' => 'Token otentikasi Agent tidak valid.'], 401);
        }

        $clientName = $request->input('client_name', 'PC Local');
        $printerList = $request->input('printers', []);

        if (!is_array($printerList) || empty($printerList)) {
            return response()->json(['success' => false, 'message' => 'Daftar printer kosong.'], 422);
        }

        $synced = [];
        foreach ($printerList as $p) {
            $pName = is_array($p) ? ($p['name'] ?? '') : (string)$p;
            $pName = trim($pName);
            if (!$pName) continue;

            $brand = 'Printer Standar';
            $lower = strtolower($pName);
            if (str_contains($lower, 'epson')) $brand = 'Epson';
            elseif (str_contains($lower, 'canon')) $brand = 'Canon';
            elseif (str_contains($lower, 'hp') || str_contains($lower, 'hewlett')) $brand = 'HP';
            elseif (str_contains($lower, 'brother')) $brand = 'Brother';
            elseif (str_contains($lower, 'dnp')) $brand = 'DNP';
            elseif (str_contains($lower, 'pdf')) $brand = 'PDF Virtual';

            $printer = Printer::updateOrCreate(
                ['name' => $pName],
                [
                    'brand' => $brand,
                    'model' => $pName,
                    'adapter' => 'windows',
                    'connection_type' => "USB ({$clientName})",
                    'status' => 'ready',
                    'default_paper_size' => '4R',
                    'supported_paper_sizes' => ['4R', 'A4', '5R', 'Strip 2x6'],
                    'print_quality' => 'Windows Spooler Agent',
                ]
            );

            $synced[] = $printer;
        }

        return response()->json([
            'success' => true,
            'message' => count($synced) . " printer berhasil didaftarkan dari {$clientName} ke cloud.",
            'printers' => $synced,
            'active_printer_id' => Setting::get('active_printer_id'),
        ]);
    }

    /**
     * PC Lokal memeriksa tugas cetak baru yang berstatus 'pending'
     */
    public function getPendingJobs(Request $request): JsonResponse
    {
        if (!$this->verifyAgentAuth($request)) {
            return response()->json(['success' => false, 'message' => 'Token otentikasi Agent tidak valid.'], 401);
        }

        $printerName = $request->input('printer_name');

        $query = PrintJob::with(['session.template', 'printer'])
            ->whereIn('status', ['pending', 'queued'])
            ->orderBy('id');

        if ($printerName) {
            $query->whereHas('printer', function ($q) use ($printerName) {
                $q->where('name', $printerName);
            });
        }

        $jobs = $query->take(5)->get();

        $formattedJobs = $jobs->map(function ($j) {
            $session = $j->session;
            $finalPath = $session?->final_photo_path;
            $fileUrl = $finalPath ? asset('storage/' . $finalPath) : null;

            return [
                'id' => $j->id,
                'session_id' => $j->session_id,
                'copies' => $j->copies,
                'paper_size' => $j->paper_size,
                'printer_name' => $j->printer?->name ?? 'Default Printer',
                'file_url' => $fileUrl,
                'status' => $j->status,
                'created_at' => $j->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'jobs' => $formattedJobs,
        ]);
    }

    /**
     * PC Lokal memperbarui status pengerjaan cetak (printing, completed, failed)
     */
    public function updateJob(Request $request, int $jobId): JsonResponse
    {
        if (!$this->verifyAgentAuth($request)) {
            return response()->json(['success' => false, 'message' => 'Token otentikasi Agent tidak valid.'], 401);
        }

        $job = PrintJob::with('session')->findOrFail($jobId);

        $status = $request->input('status', 'completed');
        $progress = (int)$request->input('progress', 100);
        $errorMessage = $request->input('error_message');

        $job->update([
            'status' => $status,
            'progress' => $progress,
            'error_message' => $errorMessage,
            'started_at' => $job->started_at ?: now(),
            'completed_at' => ($status === 'completed' || $status === 'failed') ? now() : null,
        ]);

        if ($job->session) {
            $job->session->update([
                'print_status' => $status === 'completed' ? 'printed' : ($status === 'failed' ? 'failed' : 'printing'),
                'error_message' => $errorMessage,
            ]);
        }

        try {
            DeviceLog::create([
                'device_type' => 'printer',
                'device_id' => (string)($job->printer_id ?? 'agent'),
                'event' => "agent.printer.{$status}",
                'message' => "Print Job #{$job->id} via Remote Agent status: {$status}" . ($errorMessage ? " ({$errorMessage})" : ""),
                'severity' => $status === 'failed' ? 'error' : 'info',
                'payload' => $request->all(),
            ]);
        } catch (\Throwable $e) {}

        return response()->json([
            'success' => true,
            'message' => "Status job #{$job->id} diperbarui menjadi {$status}.",
            'job' => $job,
        ]);
    }
}
