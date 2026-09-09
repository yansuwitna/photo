<?php

namespace App\Services\Hardware\Adapters\Camera;

use App\Services\Hardware\Contracts\CameraInterface;

class MockCamera implements CameraInterface
{
    protected string $name = 'Canon EOS R6 (Simulated / Demo)';
    protected string $status = 'ready';
    protected int $battery = 94;
    protected string $storage = '48.2 GB free';
    protected string $iso = '400';
    protected string $shutter = '1/160';
    protected string $aperture = 'f/2.8';
    protected string $wb = 'Auto Daylight';
    protected string $focus = 'AF-Eye Detection';
    protected string $captureMode = 'Single Shot High-Speed';
    protected bool $liveViewActive = false;

    public function connect(): array
    {
        $this->status = 'ready';
        return [
            'success' => true,
            'status' => $this->status,
            'camera' => $this->name,
            'message' => 'Simulasi kamera berhasil terhubung.',
        ];
    }

    public function disconnect(): bool
    {
        $this->status = 'disconnected';
        $this->liveViewActive = false;
        return true;
    }

    public function getStatus(): array
    {
        return [
            'name' => $this->name,
            'status' => $this->status,
            'battery' => $this->battery,
            'storage' => $this->storage,
            'iso' => $this->iso,
            'shutter_speed' => $this->shutter,
            'aperture' => $this->aperture,
            'white_balance' => $this->wb,
            'focus_mode' => $this->focus,
            'live_view' => $this->liveViewActive,
            'capabilities' => $this->getCapabilities(),
        ];
    }

    public function capture(string $destinationPath): array
    {
        // Pastikan direktori tujuan tersedia
        $dir = dirname($destinationPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Buat foto realistis beresolusi tinggi menggunakan GD
        $width = 1920;
        $height = 1280;
        $image = imagecreatetruecolor($width, $height);

        // Latar belakang gradasi foto studio elegan
        $colors = [
            [26, 31, 44],
            [43, 58, 85],
            [108, 92, 231],
            [253, 121, 168],
            [255, 159, 67],
        ];
        $chosenPalette = $colors[array_rand($colors)];

        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / $height;
            $r = (int)($chosenPalette[0] * (1 - $ratio * 0.4));
            $g = (int)($chosenPalette[1] * (1 - $ratio * 0.4));
            $b = (int)($chosenPalette[2] * (1 - $ratio * 0.4));
            $rowColor = imagecolorallocate($image, min(255, $r), min(255, $g), min(255, $b));
            imageline($image, 0, $y, $width, $y, $rowColor);
        }

        // Tambahkan efek aura studio bokeh
        for ($i = 0; $i < 12; $i++) {
            $bx = rand(100, $width - 100);
            $by = rand(100, $height - 100);
            $radius = rand(80, 260);
            $bokehColor = imagecolorallocatealpha($image, 255, 255, 255, rand(105, 120));
            imagefilledellipse($image, $bx, $by, $radius, $radius, $bokehColor);
        }

        // Teks watermark studio demo
        $white = imagecolorallocate($image, 255, 255, 255);
        $amber = imagecolorallocate($image, 245, 158, 11);
        $font = 5; // Built-in GD font

        $label = "PHOTOBOOTH PRO - STUDIO CAPTURE";
        $dateStr = date('Y-m-d H:i:s');
        $paramsStr = "ISO {$this->iso} | {$this->shutter}s | {$this->aperture} | {$this->focus}";

        imagestring($image, $font, 50, $height - 120, $label, $amber);
        imagestring($image, $font, 50, $height - 90, "Waktu Pengambilan: " . $dateStr, $white);
        imagestring($image, $font, 50, $height - 60, "Pengaturan: " . $paramsStr, $white);

        imagejpeg($image, $destinationPath, 92);
        imagedestroy($image);

        // Simulasi penurunan baterai sedikit setelah pemotretan
        if ($this->battery > 10) {
            $this->battery -= 1;
        }

        return [
            'success' => true,
            'file_path' => $destinationPath,
            'file_size' => file_exists($destinationPath) ? filesize($destinationPath) : 0,
            'width' => $width,
            'height' => $height,
            'metadata' => [
                'camera' => $this->name,
                'iso' => $this->iso,
                'shutter' => $this->shutter,
                'aperture' => $this->aperture,
                'timestamp' => now()->toIso8601String(),
            ]
        ];
    }

    public function startLiveView(): array
    {
        $this->liveViewActive = true;
        return [
            'success' => true,
            'active' => true,
            'fps' => 30,
            'resolution' => '1280x720',
            'stream_url' => '/api/camera/liveview-stream',
            'message' => 'Live view aktif.',
        ];
    }

    public function stopLiveView(): bool
    {
        $this->liveViewActive = false;
        return true;
    }

    public function downloadPhoto(string $source, string $target): string
    {
        if (file_exists($source)) {
            copy($source, $target);
        }
        return $target;
    }

    public function getBattery(): int
    {
        return $this->battery;
    }

    public function getStorageStatus(): string
    {
        return $this->storage;
    }

    public function setISO(string $iso): bool
    {
        $this->iso = $iso;
        return true;
    }

    public function setShutterSpeed(string $shutterSpeed): bool
    {
        $this->shutter = $shutterSpeed;
        return true;
    }

    public function setAperture(string $aperture): bool
    {
        $this->aperture = $aperture;
        return true;
    }

    public function setWhiteBalance(string $wb): bool
    {
        $this->wb = $wb;
        return true;
    }

    public function setFocusMode(string $mode): bool
    {
        $this->focus = $mode;
        return true;
    }

    public function setCaptureMode(string $mode): bool
    {
        $this->captureMode = $mode;
        return true;
    }

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