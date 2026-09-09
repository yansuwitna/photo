<?php

namespace App\Services\Hardware\Adapters\Printer;

use App\Services\Hardware\Contracts\PrinterInterface;

class DyeSubPrinterAdapter implements PrinterInterface
{
    public function getPrinters(): array { return (new MockPrinter())->getPrinters(); }
    public function connect(string $printerName): bool { return true; }
    public function getStatus(): array { return (new MockPrinter())->getStatus(); }
    public function print(string $filePath, int $copies = 1, string $paperSize = '4R'): array
    {
        return (new MockPrinter())->print($filePath, $copies, $paperSize);
    }
    public function cancelPrint(string $jobId): bool { return true; }
    public function getQueue(): array { return []; }
    public function getPaperStatus(): array { return (new MockPrinter())->getPaperStatus(); }
    public function getErrorStatus(): ?string { return null; }
}