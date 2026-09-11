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

    public function startNewSession(?int $templateId = null, ?int $eventId = null, ?string $boothId = 'STAND-01'): BoothSession
    {
        $event = $eventId ? Event::find($eventId) : Event::where('is_active', true)->first();
        $template = $templateId ? Template::find($templateId) : (Template::where('is_active', true)->where('is_default', true)->first() ?: Template::where('is_active', true)->first() ?: Template::first());

        $sessionCode = 'PB-' . date('Ymd') . '-' . strtoupper(Str::random(4));
        $digitalCode = strtoupper(Str::random(8));
        $resolvedBooth = $boothId ?: 'STAND-01';
        $printerManager = new PrinterManager();
        $boothPrinter = $printerManager->getPrinterForBooth($resolvedBooth);

        $session = BoothSession::create([
            'session_code' => $sessionCode,
            'event_id' => $event?->id,
            'template_id' => $template?->id,
            'printer_id' => $boothPrinter?->id,
            'booth_id' => $resolvedBooth,
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

    public function captureSlot(BoothSession $session, ?int $specificSlot = null, $imageData = null): array
    {
        $slot = $specificSlot ?? ($session->photos_captured_count + 1);
        $eventSlug = $session->event ? $session->event->slug : 'default';

        $filename = $this->storage->generatePhotoFilename($session->id, $slot);
        $relPath = "events/{$eventSlug}/sessions/{$session->id}/originals/{$filename}";
        $fullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($relPath);

        // Pastikan folder tujuan ada
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Cek jika gambar dikirim langsung dari kamera HP/Tablet (Standalone Mobile Mode)
        if (!empty($imageData)) {
            if (is_string($imageData) && str_contains($imageData, 'base64,')) {
                $parts = explode('base64,', $imageData);
                $binary = base64_decode(end($parts));
                file_put_contents($fullPath, $binary);
            } elseif ($imageData instanceof \Illuminate\Http\UploadedFile) {
                $imageData->move($dir, basename($fullPath));
            } else {
                file_put_contents($fullPath, (string)$imageData);
            }

            $imgSize = @getimagesize($fullPath);
            $captureResult = [
                'success' => true,
                'file_path' => $fullPath,
                'width' => $imgSize ? $imgSize[0] : 1920,
                'height' => $imgSize ? $imgSize[1] : 1080,
                'metadata' => [
                    'source' => 'mobile_device_camera',
                    'timestamp' => now()->toIso8601String(),
                ],
            ];
        } else {
            // Capture melalui Camera Manager (DSLR / Mirrorless / Mock HAL)
            $captureResult = $this->camera->capture($fullPath);
            if (!$captureResult['success']) {
                return $captureResult;
            }
        }

        // Thumbnail
        $thumbFilename = "thumb_" . $filename;
        $relThumb = "events/{$eventSlug}/sessions/{$session->id}/thumbnails/{$thumbFilename}";
        $thumbFullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($relThumb);
        $this->storage->createThumbnail($fullPath, $thumbFullPath);

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
            'session' => $session->fresh()->load(['event', 'template', 'photos', 'finalPhotos']),
        ];
    }

    public function retakePhoto(BoothSession $session, int $slotIndex, $imageData = null): array
    {
        $photo = $session->photos()->where('slot_index', $slotIndex)->first();
        if ($photo) {
            $photo->increment('retake_count');
        }

        return $this->captureSlot($session, $slotIndex, $imageData);
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

    public function printFinalPhoto(BoothSession $session, int $copies = 1, ?string $requestedPaperSize = null): array
    {
        if (!$session->final_photo_path) {
            $composeRes = $this->composeTemplate($session);
            if (!$composeRes['success']) return $composeRes;
        }

        $session->update(['print_status' => 'printing', 'print_copies' => $copies]);

        $finalRelPath = $session->final_photo_path;
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($finalRelPath)) {
            $fullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($finalRelPath);
        } else {
            $fullPath = \Illuminate\Support\Facades\Storage::path($finalRelPath);
        }

        $printerManager = new PrinterManager();
        $paperSize = $requestedPaperSize 
            ?: $printerManager->getActivePaperSize($session->template?->paper_size, $session->booth_id);

        $printRes = $printerManager->printFile($session->id, $fullPath, $copies, $paperSize, $session->booth_id);

        if ($printRes['success']) {
            $status = ($printRes['status'] ?? '') === 'pending' ? 'printing' : 'printed';
            $session->update(['print_status' => $status]);
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