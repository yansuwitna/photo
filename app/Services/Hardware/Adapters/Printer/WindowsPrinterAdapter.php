<?php

namespace App\Services\Hardware\Adapters\Printer;

use App\Services\Hardware\Contracts\PrinterInterface;

class WindowsPrinterAdapter implements PrinterInterface
{
    protected string $name = 'Windows Default Printer';
    protected ?string $printerName = null;

    public function __construct(?string $printerName = null)
    {
        if ($printerName) {
            $this->printerName = $printerName;
            $this->name = $printerName;
        }
    }

    public function getPrinters(): array
    {
        $printers = [];
        if (PHP_OS_FAMILY === 'Windows') {
            try {
                $cmd = 'powershell -NoProfile -Command "Add-Type -AssemblyName System.Drawing; [System.Drawing.Printing.PrinterSettings]::InstalledPrinters"';
                $output = [];
                $code = 0;
                exec($cmd, $output, $code);
                if ($code === 0 && !empty($output)) {
                    foreach ($output as $p) {
                        $p = trim($p);
                        if ($p) {
                            $printers[] = [
                                'name' => $p,
                                'brand' => $this->detectBrand($p),
                                'adapter' => 'windows',
                                'status' => 'ready',
                                'type' => 'regular_printer',
                            ];
                        }
                    }
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Gagal query printer Windows: " . $e->getMessage());
            }
        }

        if (empty($printers)) {
            $mock = new MockPrinter();
            return $mock->getPrinters();
        }

        return $printers;
    }

    protected function detectBrand(string $name): string
    {
        $lower = strtolower($name);
        if (str_contains($lower, 'epson')) return 'Epson';
        if (str_contains($lower, 'canon')) return 'Canon';
        if (str_contains($lower, 'hp') || str_contains($lower, 'hewlett')) return 'HP';
        if (str_contains($lower, 'brother')) return 'Brother';
        if (str_contains($lower, 'dnp')) return 'DNP';
        if (str_contains($lower, 'fujifilm')) return 'Fujifilm';
        if (str_contains($lower, 'pdf')) return 'PDF Virtual';
        return 'Printer Standar';
    }

    public function connect(string $printerName): bool
    {
        $this->printerName = $printerName;
        $this->name = $printerName;
        return true;
    }

    public function getStatus(): array
    {
        return [
            'name' => $this->name,
            'status' => 'ready',
            'paper_remaining' => 500,
            'paper_status' => 'Normal',
            'adapter' => 'windows',
            'type' => 'Printer Biasa (Windows Spooler)',
        ];
    }

    public function print(string $filePath, int $copies = 1, string $paperSize = '4R'): array
    {
        if (PHP_OS_FAMILY === 'Windows' && file_exists($filePath)) {
            $scriptPath = base_path('scripts/print_windows.ps1');
            if (file_exists($scriptPath)) {
                $printerArg = !empty($this->printerName) ? (' -PrinterName ' . escapeshellarg($this->printerName)) : '';
                $cmd = sprintf(
                    'powershell -NoProfile -ExecutionPolicy Bypass -File %s -ImagePath %s%s -Copies %d -PaperSize %s',
                    escapeshellarg($scriptPath),
                    escapeshellarg($filePath),
                    $printerArg,
                    max(1, $copies),
                    escapeshellarg($paperSize)
                );

                $output = [];
                $returnCode = 0;
                exec($cmd, $output, $returnCode);
                $rawResult = implode("\n", $output);
                $json = json_decode($rawResult, true);

                if (is_array($json)) {
                    return [
                        'success' => (bool)($json['success'] ?? false),
                        'message' => $json['message'] ?? (($json['success'] ?? false) ? "Dokumen dikirim ke printer {$this->name}." : "Gagal mencetak ke printer {$this->name}."),
                        'printer' => $json['printer'] ?? $this->name,
                        'job_id' => 'WIN-' . time(),
                        'copies' => $copies,
                        'paper_size' => $json['paper_size'] ?? $paperSize,
                        'pdf_path' => $json['pdf_path'] ?? null,
                    ];
                }

                \Illuminate\Support\Facades\Log::error("PowerShell Print Output Error: " . $rawResult);
                return [
                    'success' => false,
                    'message' => "Gagal berkomunikasi dengan spooler printer: " . ($rawResult ?: 'Tidak ada respon dari Windows.'),
                    'printer' => $this->name,
                ];
            }
        }

        // Fallback untuk server pengembang non-Windows (misal Linux Docker / CI)
        if (PHP_OS_FAMILY !== 'Windows') {
            $mock = new MockPrinter();
            return $mock->print($filePath, $copies, $paperSize);
        }

        return [
            'success' => false,
            'message' => "File cetak tidak ditemukan atau script cetak tidak tersedia: {$filePath}",
            'printer' => $this->name,
        ];
    }

    public function cancelPrint(string $jobId): bool { return true; }
    public function getQueue(): array { return []; }
    public function getPaperStatus(): array { return ['count' => 500, 'status' => 'Normal']; }
    public function getErrorStatus(): ?string { return null; }
}