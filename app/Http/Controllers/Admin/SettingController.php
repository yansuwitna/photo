<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Settings/Index', [
            'settings' => Setting::all(),
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        $settings = $request->input('settings', []);
        foreach ($settings as $key => $val) {
            Setting::where('key', $key)->update(['value' => (string)$val]);
        }

        return response()->json(['success' => true]);
    }
}