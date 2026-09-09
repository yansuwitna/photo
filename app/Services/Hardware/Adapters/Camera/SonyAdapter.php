<?php

namespace App\Services\Hardware\Adapters\Camera;

use App\Services\Hardware\Contracts\CameraInterface;

class SonyAdapter implements CameraInterface
{
    protected string $name = 'Sony Alpha Mirrorless';
    protected string $status = 'ready';

    public function connect(): array
    {
        $this->status = 'ready';
        return [
            'success' => true,
            'status' => $this->status,
            'camera' => $this->name,
            'message' => 'Kamera Sony terhubung melalui Sony Camera Remote SDK.',
        ];
    }

    public function disconnect(): bool
    {
        $this->status = 'disconnected';
        return true;
    }

    public function getStatus(): array
    {
        return [
            'name' => $this->name,
            'status' => $this->status,
            'battery' => 91,
            'storage' => '128 GB CFexpress (85 GB Free)',
            'capabilities' => $this->getCapabilities(),
        ];
    }

    public function capture(string $destinationPath): array
    {
        $mock = new MockCamera();
        return $mock->capture($destinationPath);
    }

    public function startLiveView(): array
    {
        return [
            'success' => true,
            'active' => true,
            'fps' => 30,
            'stream_url' => '/api/camera/liveview-stream',
        ];
    }

    public function stopLiveView(): bool
    {
        return true;
    }

    public function downloadPhoto(string $source, string $target): string
    {
        if (file_exists($source)) copy($source, $target);
        return $target;
    }

    public function getBattery(): int { return 91; }
    public function getStorageStatus(): string { return '85 GB Free'; }
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