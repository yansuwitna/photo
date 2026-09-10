<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Frame\FrameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FrameApiController extends Controller
{
    protected FrameService $frameService;

    public function __construct(FrameService $frameService)
    {
        $this->frameService = $frameService;
    }

    public function index(): JsonResponse
    {
        $frames = $this->frameService->getAllFrames();
        return response()->json([
            'success' => true,
            'frames' => $frames,
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|image|mimes:png,jpg,jpeg|max:10240', // Max 10MB
            'name' => 'nullable|string|max:100',
        ]);

        try {
            $file = $request->file('file');
            $customName = $request->input('name');

            $frame = $this->frameService->storeCustomFrame($file, $customName);

            return response()->json([
                'success' => true,
                'message' => 'Bingkai kustom berhasil diunggah.',
                'frame' => $frame,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunggah bingkai: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function delete(Request $request): JsonResponse
    {
        $path = $request->input('path');
        if (!$path) {
            return response()->json(['success' => false, 'message' => 'Path tidak boleh kosong.'], 400);
        }

        $deleted = $this->frameService->deleteFrame($path);

        return response()->json([
            'success' => $deleted,
            'message' => $deleted ? 'Bingkai berhasil dihapus.' : 'Bingkai tidak dapat dihapus atau tidak ditemukan.',
        ]);
    }
}
