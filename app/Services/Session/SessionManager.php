<?php

namespace App\Services\Session;

use App\Models\BoothSession;
use App\Models\SessionPhoto;
use App\Models\FinalPhoto;
use App\Models\Template;
use App\Models\Event;
use App\Models\Camera;
use App\Models\Printer;
use App\Services\Hardware\CameraManager;
use App\Services\Hardware\PrinterManager;
use App\Services\Storage\StorageManager;
use App\Services\Composer\PhotoComposer;
use Illuminate\Support\Str;

class SessionManager
{
    protected StorageManager $storage;
    protected CameraManager $camera;
    protected PrinterManager $printer;
    protected PhotoComposer $composer;

    public function __construct()
    {
        $this->storage = new StorageManager();
        $this->camera = new CameraManager();
        $this->printer = new PrinterManager();
        $this->composer = new PhotoComposer();
    }

    public function startNewSession(?int $templateId = null, ?int $eventId = null): BoothSession
    {
        $event = $eventId ? Event::find($eventId) : Event::where('is_active', true)->first();
        $template = $templateId ? Template::find($templateId) : (Template::where('is_default', true)->first() ?: Template::first());

        $sessionCode = 'PB-' . date('Ymd') . '-' . strtoupper(Str::random(4));
        $digitalCode = strtoupper(Str::random(8));

        $session = BoothSession::create([
            'session_code' => $sessionCode,
            'event_id' => $event?->id,
            'template_id' => $template?->id,
            'status' => 'init',
            'current_step' => 'template',
            'total_photos_required' => $template ? $template->photo_count : 3,
            'photos_captured_count' => 0,
            'digital_code' => $digitalCode,
            'qr_code_url' => url("/download/{$digitalCode}"),
            'payment_status' => 'unpaid',
            'print_status' => 'none',
            'start_time' => now(),
        ]);

        $eventSlug = $event ? $event->slug : 'default';
        $this->storage->ensureSessionDirectories($eventSlug, $session->id);

        return $session;
    }

    public function selectTemplate(BoothSession $session, int $templateId): BoothSession
    {
        $template = Template::findOrFail($templateId);
        $session->update([
            'template_id' => $template->id,
            'total_photos_required' => $template->photo_count,
            'current_step' => 'ready',
            'status' => 'template_selected',
        ]);
        return $session->fresh();
    }

    public function captureSlot(BoothSession $session, ?int $specificSlot = null): array
    {
        $slot = $specificSlot ?? ($session->photos_captured_count + 1);
        $eventSlug = $session->event ? $session->event->slug : 'default';

        $filename = $this->storage->generatePhotoFilename($session->id, $slot);
        $relPath = "events/{$eventSlug}/sessions/{$session->id}/originals/{$filename}";
        $fullPath = \Illuminate\Support\Facades\Storage::path($relPath);

        // Capture melalui Camera Manager
        $captureResult = $this->camera->capture($fullPath);
        if (!$captureResult['success']) {
            return $captureResult;
        }

        // Thumbnail
        $thumbFilename = "thumb_" . $filename;
        $relThumb = "events/{$eventSlug}/sessions/{$session->id}/thumbnails/{$thumbFilename}";
        $this->storage->createThumbnail($fullPath, \Illuminate\Support\Facades\Storage::path($relThumb));

        // Simpan / update ke database
        $sessionPhoto = SessionPhoto::updateOrCreate(
            ['session_id' => $session->id, 'slot_index' => $slot],
            [
                'original_path' => $relPath,
                'thumbnail_path' => $relThumb,
                'width' => $captureResult['width'] ?? 1920,
                'height' => $captureResult['height'] ?? 1080,
                'is_accepted' => true,
                'camera_metadata' => $captureResult['metadata'] ?? [],
            ]
        );

        $capturedCount = $session->photos()->where('is_accepted', true)->count();
        $isComplete = $capturedCount >= $session->total_photos_required;

        $session->update([
            'photos_captured_count' => $capturedCount,
            'status' => $isComplete ? 'reviewing' : 'capturing',
            'current_step' => $isComplete ? 'review' : 'capturing',
        ]);

        return [
            'success' => true,
            'photo' => $sessionPhoto,
            'slot_index' => $slot,
            'total_photos' => $session->total_photos_required,
            'captured_count' => $capturedCount,
            'is_complete' => $isComplete,
            'session' => $session->fresh(),
        ];
    }

    public function retakePhoto(BoothSession $session, int $slotIndex): array
    {
        $photo = $session->photos()->where('slot_index', $slotIndex)->first();
        if ($photo) {
            $photo->increment('retake_count');
        }

        return $this->captureSlot($session, $slotIndex);
    }

    public function composeTemplate(BoothSession $session): array
    {
        $session->update(['status' => 'composing', 'current_step' => 'composing']);

        $result = $this->composer->renderSession($session);

        if ($result['success']) {
            $finalPhoto = FinalPhoto::create([
                'session_id' => $session->id,
                'file_path' => $result['file_path'],
                'thumbnail_path' => $result['thumbnail_path'],
                'width' => $result['width'],
                'height' => $result['height'],
                'file_size' => $result['file_size'],
            ]);

            $session->update([
                'status' => 'ready_to_print',
                'current_step' => 'final_preview',
                'final_photo_path' => $result['file_path'],
                'final_thumbnail_path' => $result['thumbnail_path'],
            ]);

            $result['final_photo_id'] = $finalPhoto->id;
        }

        return $result;
    }

    public function printFinalPhoto(BoothSession $session, int $copies = 1): array
    {
        if (!$session->final_photo_path) {
            $composeRes = $this->composeTemplate($session);
            if (!$composeRes['success']) return $composeRes;
        }

        $session->update(['print_status' => 'printing', 'print_copies' => $copies]);

        $fullPath = \Illuminate\Support\Facades\Storage::path($session->final_photo_path);
        $paperSize = $session->template ? $session->template->paper_size : '4R';

        $printRes = $this->printer->printFile($session->id, $fullPath, $copies, $paperSize);

        if ($printRes['success']) {
            $session->update(['print_status' => 'printed']);
        } else {
            $session->update(['print_status' => 'failed', 'error_message' => $printRes['message'] ?? 'Gagal cetak']);
        }

        return $printRes;
    }

    public function finishSession(BoothSession $session): BoothSession
    {
        $session->update([
            'status' => 'completed',
            'current_step' => 'completed',
            'end_time' => now(),
        ]);
        return $session->fresh();
    }
}