<?php

namespace App\Services\Hardware\Adapters\Camera;

use App\Services\Hardware\Contracts\CameraInterface;

class NikonAdapter implements CameraInterface
{
    protected string $name = 'Nikon Z-Series';
    protected string $status = 'ready';

    public function connect(): array
    {
        return ['success' => true, 'status' => 'ready', 'camera' => $this->name];
    }

    public function disconnect(): bool { return true; }
    public function getStatus(): array { return ['name' => $this->name, 'status' => 'ready']; }
    public function capture(string $destinationPath): array
    {
        $mock = new MockCamera();
        return $mock->capture($destinationPath);
    }
    public function startLiveView(): array { return ['success' => true, 'active' => true]; }
    public function stopLiveView(): bool { return true; }
    public function downloadPhoto(string $source, string $target): string { return $target; }
    public function getBattery(): int { return 85; }
    public function getStorageStatus(): string { return '64 GB Free'; }
    public function setISO(string $iso): bool { return true; }
    public function setShutterSpeed(string $shutterSpeed): bool { return true; }
    public function setAperture(string $aperture): bool { return true; }
    public function setWhiteBalance(string $wb): bool { return true; }
    public function setFocusMode(string $mode): bool { return true; }
    public function setCaptureMode(string $mode): bool { return true; }
    public function getCapabilities(): array
    {
        return [
            'live_view' => true,
            'remote_capture' => true,
            'download' => true,
            'iso_control' => true,
            'shutter_control' => true,
            'aperture_control' => true,
            'wb_control' => true,
            'focus_control' => true,
            'battery_read' => true,
            'storage_read' => true,
        ];
    }
}