<?php

namespace App\Services\Hardware;

use App\Models\Printer;
use App\Models\PrintJob;
use App\Models\DeviceLog;
use App\Models\Setting;
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

    public function getActivePrinter(): ?Printer
    {
        $activeId = Setting::get('active_printer_id');
        if ($activeId) {
            $printer = Printer::find($activeId);
            if ($printer) {
                return $printer;
            }
        }

        return Printer::where('is_default', true)->first() ?? Printer::first();
    }

    public function resolveAdapter(?string $type = null): PrinterInterface
    {
        $this->printerModel = $this->getActivePrinter();

        if (!$type) {
            $type = $this->printerModel ? $this->printerModel->adapter : 'mock';
        }

        $this->adapter = match (strtolower($type)) {
            'windows' => new WindowsPrinterAdapter($this->printerModel?->name),
            'dyesub' => new DyeSubPrinterAdapter(),
            'thermal' => new ThermalPrinterAdapter(),
            default => new MockPrinter(),
        };

        return $this->adapter;
    }

    public function getPrinterModel(): ?Printer
    {
        return $this->printerModel ?? $this->getActivePrinter();
    }

    public function getActivePaperSize(?string $fallback = null): string
    {
        $settingSize = Setting::get('active_printer_paper_size');
        if (!empty($settingSize)) {
            return $settingSize;
        }

        $printer = $this->getPrinterModel();
        if ($printer && !empty($printer->default_paper_size)) {
            return $printer->default_paper_size;
        }

        return $fallback ?: '4R';
    }

    public function getAdapter(): PrinterInterface
    {
        return $this->adapter ?? $this->resolveAdapter();
    }

    public function getStatus(): array
    {
        $status = $this->getAdapter()->getStatus();
        $model = $this->getPrinterModel();
        if ($model) {
            $status['name'] = $model->name;
            $status['brand'] = $model->brand;
            $status['paper_size'] = $this->getActivePaperSize();
        }
        return $status;
    }

    public function printFile(?string $sessionId, string $filePath, int $copies = 1, ?string $paperSize = null): array
    {
        $resolvedPaperSize = $paperSize ?: $this->getActivePaperSize();
        // Validasi apakah session_id ada di database
        $validSessionId = null;
        if (!empty($sessionId) && \App\Models\BoothSession::where('id', $sessionId)->exists()) {
            $validSessionId = $sessionId;
        }

        // Buat record PrintJob (nullable session_id didukung untuk test print)
        $job = null;
        try {
            $job = PrintJob::create([
                'session_id' => $validSessionId,
                'printer_id' => $this->printerModel?->id,
                'copies' => $copies,
                'paper_size' => $resolvedPaperSize,
                'status' => 'printing',
                'progress' => 20,
                'started_at' => now(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Gagal menyimpan PrintJob: " . $e->getMessage());
        }

        $logMessage = $validSessionId
            ? "Mencetak sesi {$validSessionId}, {$copies} salinan ({$resolvedPaperSize})"
            : "Mencetak uji coba printer (Test Print), {$copies} salinan ({$resolvedPaperSize})";

        try {
            DeviceLog::create([
                'device_type' => 'printer',
                'device_id' => (string)($this->printerModel?->id ?? 'default'),
                'event' => 'printer.printing',
                'message' => $logMessage,
                'severity' => 'info',
                'payload' => ['job_id' => $job?->id, 'copies' => $copies, 'paper_size' => $resolvedPaperSize],
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Gagal menyimpan DeviceLog: " . $e->getMessage());
        }

        // Jika mode Web Print Station diaktifkan ATAU printer terdaftar sebagai remote agent
        $isMock = $this->printerModel?->adapter === 'mock';
        $webStationSetting = filter_var(Setting::get('web_print_station_enabled', true), FILTER_VALIDATE_BOOLEAN);

        $isWebStation = (!$isMock && $webStationSetting)
            || str_contains(strtolower($this->printerModel?->connection_type ?? ''), 'web')
            || str_contains(strtolower($this->printerModel?->name ?? ''), 'web')
            || str_contains(strtolower($this->printerModel?->name ?? ''), 'browser');

        $isRemoteAgent = $isWebStation
            || str_contains(strtolower($this->printerModel?->connection_type ?? ''), 'pc') 
            || (PHP_OS_FAMILY !== 'Windows' && $this->printerModel?->adapter === 'windows');

        if ($isRemoteAgent) {
            if ($job) {
                $job->update(['status' => 'pending', 'progress' => 10]);
            }
            $targetDesc = $isWebStation ? "Web Print Station (Browser Vue)" : "remote agent di PC lokal ({$this->printerModel?->name})";
            return [
                'success' => true,
                'status' => 'pending',
                'message' => "Pekerjaan cetak dikirim ke antrean {$targetDesc}.",
                'printer' => $this->printerModel?->name,
                'print_job_id' => $job?->id,
                'copies' => $copies,
                'paper_size' => $resolvedPaperSize,
            ];
        }

        $result = $this->getAdapter()->print($filePath, $copies, $resolvedPaperSize);

        if ($result['success']) {
            if ($job) {
                $job->update([
                    'status' => 'completed',
                    'progress' => 100,
                    'completed_at' => now(),
                ]);
            }

            try {
                DeviceLog::create([
                    'device_type' => 'printer',
                    'device_id' => (string)($this->printerModel?->id ?? 'default'),
                    'event' => 'printer.completed',
                    'message' => "Pencetakan berhasil" . ($job ? " untuk job #{$job->id}" : ""),
                    'severity' => 'info',
                    'payload' => $result,
                ]);
            } catch (\Throwable $e) {}
        } else {
            if ($job) {
                $job->update([
                    'status' => 'failed',
                    'error_message' => $result['message'] ?? 'Gagal mencetak',
                ]);
            }

            try {
                DeviceLog::create([
                    'device_type' => 'printer',
                    'device_id' => (string)($this->printerModel?->id ?? 'default'),
                    'event' => 'printer.error',
                    'message' => "Pencetakan gagal: " . ($result['message'] ?? 'Error printer'),
                    'severity' => 'error',
                    'payload' => $result,
                ]);
            } catch (\Throwable $e) {}
        }

        $result['print_job_id'] = $job?->id;
        return $result;
    }
}