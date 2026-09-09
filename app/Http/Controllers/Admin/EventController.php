<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    public function index(): Response
    {
        $events = Event::latest()->get();
        return Inertia::render('Admin/Events/Index', [
            'events' => $events,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_date' => 'nullable|date',
            'location' => 'nullable|string',
            'default_price' => 'required|numeric|min:0',
            'extra_print_price' => 'required|numeric|min:0',
            'watermark_text' => 'nullable|string',
            'countdown_seconds' => 'required|integer|min:3|max:15',
        ]);

        $validated['slug'] = Str::slug($validated['name']) . '-' . rand(100, 999);
        $event = Event::create($validated);

        return response()->json(['success' => true, 'event' => $event]);
    }

    public function activate(int $id): JsonResponse
    {
        Event::query()->update(['is_active' => false]);
        $event = Event::findOrFail($id);
        $event->update(['is_active' => true]);

        return response()->json(['success' => true]);
    }
}