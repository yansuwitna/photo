<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

    public function testCamera(): JsonResponse
    {
        $testPath = Storage::path('public/tests/camera_test_' . time() . '.jpg');
        $result = $this->deviceManager->camera()->capture($testPath);

        return response()->json([
            'success' => $result['success'] ?? false,
            'message' => 'Uji jepret kamera berhasil disimpan di: ' . basename($testPath),
            'data' => $result,
        ]);
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
        // Buat file tes jika belum ada
        $testDir = Storage::path('public/tests');
        if (!is_dir($testDir)) mkdir($testDir, 0755, true);
        $testFile = $testDir . '/print_test.jpg';

        if (!file_exists($testFile)) {
            $img = imagecreatetruecolor(1200, 1800);
            $bg = imagecolorallocate($img, 240, 244, 248);
            imagefilledrectangle($img, 0, 0, 1200, 1800, $bg);
            $text = imagecolorallocate($img, 30, 41, 59);
            imagestring($img, 5, 200, 900, "PHOTOBOOTH PRO - PRINTER TEST PAGE OK", $text);
            imagejpeg($img, $testFile, 90);
            imagedestroy($img);
        }

        $result = $this->deviceManager->printer()->printFile('TEST-SESSION', $testFile, 1, '4R');

        return response()->json($result);
    }
}