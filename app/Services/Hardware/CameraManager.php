<?php

namespace App\Services\Hardware;

use App\Models\Camera;
use App\Models\DeviceLog;
use App\Services\Hardware\Contracts\CameraInterface;
use App\Services\Hardware\Adapters\Camera\CanonAdapter;
use App\Services\Hardware\Adapters\Camera\SonyAdapter;
use App\Services\Hardware\Adapters\Camera\NikonAdapter;
use App\Services\Hardware\Adapters\Camera\WebcamAdapter;
use App\Services\Hardware\Adapters\Camera\MockCamera;

class CameraManager
{
    protected ?CameraInterface $adapter = null;
    protected ?Camera $cameraModel = null;

    public function __construct(?string $adapterType = null)
    {
        $this->resolveAdapter($adapterType);
    }

    public function resolveAdapter(?string $type = null): CameraInterface
    {
        if (!$type) {
            $defaultCamera = Camera::where('is_default', true)->first() ?? Camera::first();
            $this->cameraModel = $defaultCamera;
            $type = $defaultCamera ? $defaultCamera->adapter : 'mock';
        }

        $this->adapter = match (strtolower($type)) {
            'canon' => new CanonAdapter(),
            'sony' => new SonyAdapter(),
            'nikon' => new NikonAdapter(),
            'webcam' => new WebcamAdapter(),
            default => new MockCamera(),
        };

        return $this->adapter;
    }

    public function getAdapter(): CameraInterface
    {
        return $this->adapter ?? $this->resolveAdapter();
    }

    public function getStatus(): array
    {
        return $this->getAdapter()->getStatus();
    }

    public function capture(string $destinationPath): array
    {
        DeviceLog::create([
            'device_type' => 'camera',
            'device_id' => $this->cameraModel?->id ? (string)$this->cameraModel->id : 'default',
            'event' => 'camera.capture',
            'message' => 'Mengambil foto pada path: ' . basename($destinationPath),
            'severity' => 'info',
            'payload' => ['destination' => $destinationPath],
        ]);

        $result = $this->getAdapter()->capture($destinationPath);

        if ($result['success']) {
            DeviceLog::create([
                'device_type' => 'camera',
                'device_id' => $this->cameraModel?->id ? (string)$this->cameraModel->id : 'default',
                'event' => 'camera.photo_captured',
                'message' => 'Foto berhasil diambil (' . ($result['width'] ?? 0) . 'x' . ($result['height'] ?? 0) . ')',
                'severity' => 'info',
                'payload' => $result,
            ]);
        }

        return $result;
    }

    public function startLiveView(): array
    {
        return $this->getAdapter()->startLiveView();
    }

    public function stopLiveView(): bool
    {
        return $this->getAdapter()->stopLiveView();
    }

    public function setSetting(string $key, string $value): bool
    {
        $adapter = $this->getAdapter();
        return match ($key) {
            'iso' => $adapter->setISO($value),
            'shutter_speed' => $adapter->setShutterSpeed($value),
            'aperture' => $adapter->setAperture($value),
            'white_balance' => $adapter->setWhiteBalance($value),
            'focus_mode' => $adapter->setFocusMode($value),
            'capture_mode' => $adapter->setCaptureMode($value),
            default => false,
        };
    }
}