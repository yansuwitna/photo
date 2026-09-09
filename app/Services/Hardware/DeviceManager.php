<?php

namespace App\Services\Hardware;

use App\Models\Camera;
use App\Models\Printer;
use App\Models\Device;

class DeviceManager
{
    protected CameraManager $cameraManager;
    protected PrinterManager $printerManager;
    protected AudioManager $audioManager;

    public function __construct()
    {
        $this->cameraManager = new CameraManager();
        $this->printerManager = new PrinterManager();
        $this->audioManager = new AudioManager();
    }

    public function camera(): CameraManager
    {
        return $this->cameraManager;
    }

    public function printer(): PrinterManager
    {
        return $this->printerManager;
    }

    public function audio(): AudioManager
    {
        return $this->audioManager;
    }

    public function getOverview(): array
    {
        return [
            'camera' => $this->cameraManager->getStatus(),
            'printer' => $this->printerManager->getStatus(),
            'audio' => $this->audioManager->getStatus(),
            'display' => [
                'status' => 'connected',
                'resolution' => '1920x1080 Full HD',
                'touchscreen' => true,
                'kiosk_mode' => true,
            ],
            'system' => [
                'os' => PHP_OS_FAMILY,
                'php' => PHP_VERSION,
                'storage' => disk_free_space('/') !== false ? round(disk_free_space('/') / (1024 * 1024 * 1024), 1) . ' GB' : '50+ GB',
                'timestamp' => now()->toIso8601String(),
            ]
        ];
    }
}