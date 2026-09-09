<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BoothSession;
use Inertia\Inertia;
use Inertia\Response;

class GalleryController extends Controller
{
    public function index(): Response
    {
        $sessions = BoothSession::with(['event', 'template', 'finalPhotos', 'photos'])
            ->whereNotNull('final_photo_path')
            ->latest()
            ->paginate(30);

        return Inertia::render('Admin/Gallery/Index', [
            'sessions' => $sessions->items(),
        ]);
    }
}