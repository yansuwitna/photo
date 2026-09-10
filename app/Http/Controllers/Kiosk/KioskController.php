<?php

namespace App\Http\Controllers\Kiosk;

use App\Http\Controllers\Controller;
use App\Models\BoothSession;
use App\Models\Template;
use App\Models\Event;
use Inertia\Inertia;
use Inertia\Response;

class KioskController extends Controller
{
    public function index(): Response
    {
        $activeEvent = Event::where('is_active', true)->first();
        return Inertia::render('Kiosk/Index', [
            'active_event' => $activeEvent,
        ]);
    }

    public function templateSelect(string $sessionId): Response
    {
        $session = BoothSession::with(['event', 'template.elements'])->findOrFail($sessionId);
        $templates = Template::where('is_active', true)->with('elements')->get();

        return Inertia::render('Kiosk/TemplateSelect', [
            'session' => $session,
            'templates' => $templates,
        ]);
    }

    public function camera(string $sessionId): Response
    {
        $session = BoothSession::with(['event', 'template.elements', 'photos', 'camera', 'printer'])->findOrFail($sessionId);
        $template = $session->template ?: Template::with('elements')->first();
        if ($template) {
            $template->loadMissing('elements');
        }
        $templates = Template::where('is_active', true)->with('elements')->get();

        return Inertia::render('Kiosk/Camera', [
            'session' => $session,
            'template' => $template,
            'templates' => $templates,
        ]);
    }

    public function success(string $sessionId): Response
    {
        $session = BoothSession::with(['event', 'template'])->findOrFail($sessionId);

        return Inertia::render('Kiosk/Success', [
            'session' => $session,
        ]);
    }
}