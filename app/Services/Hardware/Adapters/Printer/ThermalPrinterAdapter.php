<?php

namespace App\Services\Hardware\Adapters\Printer;

use App\Services\Hardware\Contracts\PrinterInterface;

class ThermalPrinterAdapter implements PrinterInterface
{
    public function getPrinters(): array { return [['name' => 'POS-80 Thermal', 'status' => 'ready']]; }
    public function connect(string $printerName): bool { return true; }
    public function getStatus(): array { return ['name' => 'POS-80 Thermal', 'status' => 'ready']; }
    public function print(string $filePath, int $copies = 1, string $paperSize = '80mm'): array
    {
        return (new MockPrinter())->print($filePath, $copies, '80mm');
    }
    public function cancelPrint(string $jobId): bool { return true; }
    public function getQueue(): array { return []; }
    public function getPaperStatus(): array { return ['count' => 100, 'status' => 'Roll Normal']; }
    public function getErrorStatus(): ?string { return null; }
}