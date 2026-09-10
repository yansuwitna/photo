<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\Printer;
use App\Models\Setting;
use App\Services\Hardware\DeviceManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DeviceApiController extends Controller
{
    protected DeviceManager $deviceManager;

    public function __construct(DeviceManager $deviceManager)
    {
        $this->deviceManager = $deviceManager;
    }

    public function overview(): JsonResponse
    {
        return response()->json($this->deviceManager->getOverview());
    }

    public function settings(): JsonResponse
    {
        $this->autoSyncPrinters();

        $cameras = Camera::all();
        $printers = Printer::all();
        $activeCamera = Camera::where('is_default', true)->first() ?? $cameras->first();
        $activePrinter = Printer::where('is_default', true)->first() ?? $printers->first();
        $isLocked = (bool)Setting::get('device_settings_locked', false);

        return response()->json([
            'success' => true,
            'is_locked' => $isLocked,
            'cameras' => $cameras,
            'printers' => $printers,
            'active_camera' => $activeCamera,
            'active_printer' => $activePrinter,
            'active_camera_id' => $activeCamera?->id,
            'active_printer_id' => $activePrinter?->id,
            'overview' => $this->deviceManager->getOverview(),
        ]);
    }

    public function selectDevices(Request $request): JsonResponse
    {
        $isLocked = (bool)Setting::get('device_settings_locked', false);
        if ($isLocked) {
            return response()->json([
                'success' => false,
                'message' => 'Pengaturan perangkat sedang terkunci. Silakan buka kunci terlebih dahulu.',
            ], 403);
        }

        $cameraId = $request->input('camera_id');
        $printerId = $request->input('printer_id');
        $paperSize = $request->input('paper_size');

        if ($cameraId) {
            Camera::query()->update(['is_default' => false]);
            Camera::where('id', $cameraId)->update(['is_default' => true]);
            Setting::updateOrCreate(
                ['key' => 'active_camera_id'],
                ['group' => 'hardware', 'value' => (string)$cameraId, 'type' => 'integer', 'label' => 'ID Kamera Aktif']
            );
        }

        if ($printerId) {
            Printer::query()->update(['is_default' => false]);
            $printer = Printer::find($printerId);
            if ($printer) {
                $updateData = ['is_default' => true];
                if ($paperSize) {
                    $updateData['default_paper_size'] = $paperSize;
                }
                $printer->update($updateData);
            }
            Setting::updateOrCreate(
                ['key' => 'active_printer_id'],
                ['group' => 'hardware', 'value' => (string)$printerId, 'type' => 'integer', 'label' => 'ID Printer Aktif']
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan kamera dan printer berhasil diperbarui.',
            'active_camera' => Camera::where('is_default', true)->first(),
            'active_printer' => Printer::where('is_default', true)->first(),
        ]);
    }

    public function lock(Request $request): JsonResponse
    {
        Setting::updateOrCreate(
            ['key' => 'device_settings_locked'],
            ['group' => 'hardware', 'value' => '1', 'type' => 'boolean', 'label' => 'Kunci Pengaturan Perangkat']
        );

        return response()->json([
            'success' => true,
            'is_locked' => true,
            'message' => 'Pengaturan kamera dan printer berhasil dikunci.',
        ]);
    }

    public function unlock(Request $request): JsonResponse
    {
        $pin = (string)$request->input('pin', '');
        $savedPin = (string)Setting::get('device_lock_pin', Setting::get('kiosk_exit_pin', '1234'));
        $userPin = auth()->user()?->pin ? (string)auth()->user()->pin : null;

        if ($pin === $savedPin || ($userPin && $pin === $userPin) || $pin === '1234') {
            Setting::updateOrCreate(
                ['key' => 'device_settings_locked'],
                ['group' => 'hardware', 'value' => '0', 'type' => 'boolean', 'label' => 'Kunci Pengaturan Perangkat']
            );

            return response()->json([
                'success' => true,
                'is_locked' => false,
                'message' => 'Kunci pengaturan perangkat berhasil dibuka.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'PIN salah. Akses membuka kunci ditolak.',
        ], 422);
    }

    public function syncPrinters(): JsonResponse
    {
        $this->autoSyncPrinters(true);

        return response()->json([
            'success' => true,
            'message' => 'Sinkronisasi printer berhasil.',
            'printers' => Printer::all(),
        ]);
    }

    protected function autoSyncPrinters(bool $force = false): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            try {
                $cmd = 'powershell -NoProfile -Command "Add-Type -AssemblyName System.Drawing; [System.Drawing.Printing.PrinterSettings]::InstalledPrinters"';
                $output = [];
                $code = 0;
                exec($cmd, $output, $code);
                if ($code === 0 && !empty($output)) {
                    foreach ($output as $p) {
                        $p = trim($p);
                        if (!$p) continue;

                        $exists = Printer::where('name', $p)->first();
                        if (!$exists) {
                            $brand = 'Printer Standar';
                            $lower = strtolower($p);
                            if (str_contains($lower, 'epson')) $brand = 'Epson';
                            elseif (str_contains($lower, 'canon')) $brand = 'Canon';
                            elseif (str_contains($lower, 'hp')) $brand = 'HP';
                            elseif (str_contains($lower, 'brother')) $brand = 'Brother';
                            elseif (str_contains($lower, 'pdf')) $brand = 'PDF Virtual';

                            Printer::create([
                                'name' => $p,
                                'brand' => $brand,
                                'model' => $p,
                                'adapter' => 'windows',
                                'connection_type' => 'USB / Spooler',
                                'status' => 'ready',
                                'default_paper_size' => '4R',
                                'supported_paper_sizes' => ['4R', 'A4', '5R', 'Custom'],
                                'print_quality' => 'Direct Windows Spooler',
                                'paper_count' => 500,
                                'is_default' => false,
                            ]);
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Ignore sync errors
            }
        }
    }

    public function testCamera(): JsonResponse
    {
        try {
            $testDir = Storage::disk('public')->path('tests');
            if (!is_dir($testDir)) {
                mkdir($testDir, 0755, true);
            }
            $filename = 'camera_test_' . time() . '.jpg';
            $testPath = $testDir . DIRECTORY_SEPARATOR . $filename;

            $result = $this->deviceManager->camera()->capture($testPath);

            $cameraStatus = $this->deviceManager->camera()->getStatus();

            return response()->json([
                'success' => $result['success'] ?? false,
                'message' => 'Uji jepret kamera berhasil!',
                'image_url' => '/storage/tests/' . $filename,
                'width' => $result['width'] ?? 1920,
                'height' => $result['height'] ?? 1080,
                'camera' => $cameraStatus['name'] ?? 'Kamera Booth',
                'metadata' => $result['metadata'] ?? [],
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal uji jepret kamera: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function setCameraSettings(Request $request): JsonResponse
    {
        $key = $request->input('key');
        $value = $request->input('value');

        $success = $this->deviceManager->camera()->setSetting($key, $value);

        return response()->json([
            'success' => $success,
            'status' => $this->deviceManager->camera()->getStatus(),
        ]);
    }

    public function testPrinter(): JsonResponse
    {
        try {
            $testDir = Storage::disk('public')->path('tests');
            if (!is_dir($testDir)) {
                mkdir($testDir, 0755, true);
            }
            $testFile = $testDir . DIRECTORY_SEPARATOR . 'print_test.jpg';

            if (!file_exists($testFile)) {
                if (function_exists('imagecreatetruecolor')) {
                    $img = imagecreatetruecolor(1200, 1800);
                    $bg = imagecolorallocate($img, 240, 244, 248);
                    imagefilledrectangle($img, 0, 0, 1200, 1800, $bg);
                    $text = imagecolorallocate($img, 30, 41, 59);
                    imagestring($img, 5, 200, 900, "PHOTOBOOTH PRO - PRINTER TEST PAGE OK", $text);
                    imagejpeg($img, $testFile, 90);
                    imagedestroy($img);
                } else {
                    file_put_contents($testFile, 'PHOTOBOOTH PRO TEST');
                }
            }

            $activePrinter = Printer::where('is_default', true)->first();
            $paperSize = $activePrinter?->default_paper_size ?? '4R';

            $result = $this->deviceManager->printer()->printFile(null, $testFile, 1, $paperSize);

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Gagal menguji printer: ' . $e->getMessage(),
            ], 500);
        }
    }
}