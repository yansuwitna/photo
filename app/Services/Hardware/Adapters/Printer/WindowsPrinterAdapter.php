<?php

namespace App\Services\Hardware\Adapters\Printer;

use App\Services\Hardware\Contracts\PrinterInterface;

class WindowsPrinterAdapter implements PrinterInterface
{
    protected string $name = 'Windows Default Printer';

    public function getPrinters(): array
    {
        $printers = [];
        if (PHP_OS_FAMILY === 'Windows') {
            exec('powershell -Command "Get-CimInstance Win32_Printer | Select-Object -ExpandProperty Name"', $output);
            foreach ($output as $p) {
                if (trim($p)) $printers[] = ['name' => trim($p), 'status' => 'ready'];
            }
        }
        if (empty($printers)) {
            $mock = new MockPrinter();
            return $mock->getPrinters();
        }
        return $printers;
    }

    public function connect(string $printerName): bool { return true; }
    public function getStatus(): array { return ['name' => $this->name, 'status' => 'ready']; }
    public function print(string $filePath, int $copies = 1, string $paperSize = '4R'): array
    {
        $mock = new MockPrinter();
        return $mock->print($filePath, $copies, $paperSize);
    }
    public function cancelPrint(string $jobId): bool { return true; }
    public function getQueue(): array { return []; }
    public function getPaperStatus(): array { return ['count' => 350, 'status' => 'Normal']; }
    public function getErrorStatus(): ?string { return null; }
}