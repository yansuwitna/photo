<?php

namespace App\Http\Controllers;

use App\Models\BoothSession;
use Inertia\Inertia;
use Inertia\Response;

class DownloadController extends Controller
{
    public function show(string $digitalCode): Response
    {
        $session = BoothSession::with(['event', 'template', 'finalPhotos'])
            ->where('digital_code', $digitalCode)
            ->firstOrFail();

        return Inertia::render('Download/Index', [
            'session' => $session,
        ]);
    }
}