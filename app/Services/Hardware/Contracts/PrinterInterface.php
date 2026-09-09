<?php

namespace App\Services\Hardware\Contracts;

interface PrinterInterface
{
    public function getPrinters(): array;
    public function connect(string $printerName): bool;
    public function getStatus(): array;
    public function print(string $filePath, int $copies = 1, string $paperSize = '4R'): array;
    public function cancelPrint(string $jobId): bool;
    public function getQueue(): array;
    public function getPaperStatus(): array;
    public function getErrorStatus(): ?string;
}