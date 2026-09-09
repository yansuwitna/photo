<?php

namespace App\Services\Hardware\Adapters\Printer;

use App\Services\Hardware\Contracts\PrinterInterface;

class MockPrinter implements PrinterInterface
{
    protected string $name = 'DNP DS-RX1HS (Simulated Photo Printer)';
    protected string $status = 'ready';
    protected int $paperCount = 380;
    protected array $queue = [];

    public function getPrinters(): array
    {
        return [
            ['name' => 'DNP DS-RX1HS (Simulated)', 'status' => 'ready', 'type' => 'DyeSub'],
            ['name' => 'Epson SureLab D1070', 'status' => 'ready', 'type' => 'Photo'],
            ['name' => 'Citizen CY-02', 'status' => 'ready', 'type' => 'DyeSub'],
            ['name' => 'POS-80 Thermal', 'status' => 'ready', 'type' => 'Thermal'],
        ];
    }

    public function connect(string $printerName): bool
    {
        $this->status = 'ready';
        return true;
    }

    public function getStatus(): array
    {
        return [
            'name' => $this->name,
            'status' => $this->status,
            'paper_remaining' => $this->paperCount,
            'paper_status' => $this->paperCount > 20 ? 'OK' : ($this->paperCount > 0 ? 'LOW' : 'EMPTY'),
            'queue_count' => count($this->queue),
            'supported_sizes' => ['4R', '5R', '6R', 'Strip 2x6', 'A4'],
        ];
    }

    public function print(string $filePath, int $copies = 1, string $paperSize = '4R'): array
    {
        if ($this->paperCount < $copies) {
            $this->status = 'paper_empty';
            return [
                'success' => false,
                'status' => 'error',
                'message' => 'Kertas foto habis. Harap isi ulang kertas printer.',
            ];
        }

        $jobId = 'JOB-' . strtoupper(uniqid());
        $this->paperCount -= $copies;
        $this->status = 'ready';

        return [
            'success' => true,
            'job_id' => $jobId,
            'status' => 'completed',
            'copies' => $copies,
            'paper_size' => $paperSize,
            'paper_remaining' => $this->paperCount,
            'message' => 'Foto berhasil dikirim ke printer dan selesai dicetak.',
        ];
    }

    public function cancelPrint(string $jobId): bool
    {
        return true;
    }

    public function getQueue(): array
    {
        return $this->queue;
    }

    public function getPaperStatus(): array
    {
        return [
            'count' => $this->paperCount,
            'status' => $this->paperCount > 20 ? 'Normal' : 'Kertas Menipis',
        ];
    }

    public function getErrorStatus(): ?string
    {
        return null;
    }
}