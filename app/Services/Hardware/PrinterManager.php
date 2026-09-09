<?php

namespace App\Services\Hardware;

use App\Models\Printer;
use App\Models\PrintJob;
use App\Models\DeviceLog;
use App\Services\Hardware\Contracts\PrinterInterface;
use App\Services\Hardware\Adapters\Printer\WindowsPrinterAdapter;
use App\Services\Hardware\Adapters\Printer\DyeSubPrinterAdapter;
use App\Services\Hardware\Adapters\Printer\ThermalPrinterAdapter;
use App\Services\Hardware\Adapters\Printer\MockPrinter;

class PrinterManager
{
    protected ?PrinterInterface $adapter = null;
    protected ?Printer $printerModel = null;

    public function __construct(?string $adapterType = null)
    {
        $this->resolveAdapter($adapterType);
    }

    public function resolveAdapter(?string $type = null): PrinterInterface
    {
        if (!$type) {
            $default = Printer::where('is_default', true)->first() ?? Printer::first();
            $this->printerModel = $default;
            $type = $default ? $default->adapter : 'mock';
        }

        $this->adapter = match (strtolower($type)) {
            'windows' => new WindowsPrinterAdapter(),
            'dyesub' => new DyeSubPrinterAdapter(),
            'thermal' => new ThermalPrinterAdapter(),
            default => new MockPrinter(),
        };

        return $this->adapter;
    }

    public function getAdapter(): PrinterInterface
    {
        return $this->adapter ?? $this->resolveAdapter();
    }

    public function getStatus(): array
    {
        return $this->getAdapter()->getStatus();
    }

    public function printFile(string $sessionId, string $filePath, int $copies = 1, string $paperSize = '4R'): array
    {
        // Buat record PrintJob
        $job = PrintJob::create([
            'session_id' => $sessionId,
            'printer_id' => $this->printerModel?->id,
            'copies' => $copies,
            'paper_size' => $paperSize,
            'status' => 'printing',
            'progress' => 20,
            'started_at' => now(),
        ]);

        DeviceLog::create([
            'device_type' => 'printer',
            'device_id' => (string)($this->printerModel?->id ?? 'default'),
            'event' => 'printer.printing',
            'message' => "Mencetak sesi {$sessionId}, {$copies} salinan ({$paperSize})",
            'severity' => 'info',
            'payload' => ['job_id' => $job->id, 'copies' => $copies, 'paper_size' => $paperSize],
        ]);

        $result = $this->getAdapter()->print($filePath, $copies, $paperSize);

        if ($result['success']) {
            $job->update([
                'status' => 'completed',
                'progress' => 100,
                'completed_at' => now(),
            ]);

            DeviceLog::create([
                'device_type' => 'printer',
                'device_id' => (string)($this->printerModel?->id ?? 'default'),
                'event' => 'printer.completed',
                'message' => "Pencetakan berhasil untuk job #{$job->id}",
                'severity' => 'info',
                'payload' => $result,
            ]);
        } else {
            $job->update([
                'status' => 'failed',
                'error_message' => $result['message'] ?? 'Gagal mencetak',
            ]);

            DeviceLog::create([
                'device_type' => 'printer',
                'device_id' => (string)($this->printerModel?->id ?? 'default'),
                'event' => 'printer.error',
                'message' => "Pencetakan gagal: " . ($result['message'] ?? 'Error printer'),
                'severity' => 'error',
                'payload' => $result,
            ]);
        }

        $result['print_job_id'] = $job->id;
        return $result;
    }
}