<?php

namespace App\Services\Hardware\Adapters\Camera;

use App\Services\Hardware\Contracts\CameraInterface;

class WebcamAdapter implements CameraInterface
{
    protected string $name = 'USB Full HD Webcam';
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
    public function getBattery(): int { return 100; /* USB Powered */ }
    public function getStorageStatus(): string { return 'PC Storage Host'; }
    public function setISO(string $iso): bool { return false; /* Not supported */ }
    public function setShutterSpeed(string $shutterSpeed): bool { return false; }
    public function setAperture(string $aperture): bool { return false; }
    public function setWhiteBalance(string $wb): bool { return true; }
    public function setFocusMode(string $mode): bool { return true; }
    public function setCaptureMode(string $mode): bool { return true; }
    public function getCapabilities(): array
    {
        return [
            'live_view' => true,
            'remote_capture' => true,
            'download' => true,
            'iso_control' => false,
            'shutter_control' => false,
            'aperture_control' => false,
            'wb_control' => false,
            'focus_control' => false,
            'battery_read' => false,
            'storage_read' => false,
        ];
    }
}