<?php

namespace App\Services\Composer;

use App\Models\BoothSession;
use App\Models\Template;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Storage;

class PhotoComposer
{
    /**
     * Render template bersama foto-foto sesi menjadi 1 gambar final beresolusi tinggi
     */
    public function renderSession(BoothSession $session): array
    {
        $template = $session->template;
        if (!$template) {
            throw new \Exception("Sesi tidak memiliki template.");
        }

        $width = $template->width ?: 1200;
        $height = $template->height ?: 1800;

        // Inisialisasi canvas master
        $canvas = imagecreatetruecolor($width, $height);
        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);

        // Isi warna latar belakang (default putih atau sesuai template)
        $bgHex = $template->background_color ?: '#ffffff';
        $bgColor = $this->hexToColor($canvas, $bgHex);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $bgColor);

        // Jika ada background image
        if ($template->background_image && Storage::exists($template->background_image)) {
            $bgImgPath = Storage::path($template->background_image);
            $this->overlayImageOnCanvas($canvas, $bgImgPath, 0, 0, $width, $height);
        }

        // Ambil elemen-elemen template
        $elements = $template->elements()->orderBy('z_index')->get();
        $photos = $session->photos()->where('is_accepted', true)->orderBy('slot_index')->get()->keyBy('slot_index');

        foreach ($elements as $el) {
            $elX = (int)($el->x * ($width / 100));
            $elY = (int)($el->y * ($height / 100));
            $elW = (int)($el->width * ($width / 100));
            $elH = (int)($el->height * ($height / 100));

            if ($el->type === 'photo_slot') {
                $slotIdx = $el->slot_index ?: 1;
                $photo = $photos->get($slotIdx);

                if ($photo && file_exists(Storage::path($photo->original_path))) {
                    $photoPath = Storage::path($photo->original_path);
                    $this->drawPhotoSlot($canvas, $photoPath, $elX, $elY, $elW, $elH, $el);
                } else {
                    // Placeholder jika belum ada foto
                    $phBg = imagecolorallocate($canvas, 230, 235, 245);
                    imagefilledrectangle($canvas, $elX, $elY, $elX + $elW, $elY + $elH, $phBg);
                    $gray = imagecolorallocate($canvas, 140, 150, 170);
                    imagestring($canvas, 5, $elX + 20, $elY + ($elH / 2) - 10, "PHOTO SLOT {$slotIdx}", $gray);
                }
            } elseif ($el->type === 'text') {
                $this->drawTextElement($canvas, $el, $elX, $elY, $elW, $elH, $session);
            } elseif ($el->type === 'qr_code') {
                $this->drawQrCodeElement($canvas, $el, $elX, $elY, $elW, $elH, $session);
            } elseif ($el->type === 'sticker' || $el->type === 'image') {
                if ($el->content && file_exists(Storage::path($el->content))) {
                    $this->overlayImageOnCanvas($canvas, Storage::path($el->content), $elX, $elY, $elW, $elH);
                }
            }
        }

        // Jika ada overlay frame
        if ($template->overlay_image && Storage::exists($template->overlay_image)) {
            $overlayPath = Storage::path($template->overlay_image);
            $this->overlayImageOnCanvas($canvas, $overlayPath, 0, 0, $width, $height);
        }

        // Simpan hasil render
        $eventSlug = $session->event ? $session->event->slug : 'default';
        $finalRelDir = "events/{$eventSlug}/sessions/{$session->id}/final";
        if (!Storage::exists($finalRelDir)) Storage::makeDirectory($finalRelDir);

        $thumbRelDir = "events/{$eventSlug}/sessions/{$session->id}/thumbnails";
        if (!Storage::exists($thumbRelDir)) Storage::makeDirectory($thumbRelDir);

        $shortId = substr(strtoupper(str_replace('-', '', $session->id)), 0, 8);
        $finalFilename = "{$shortId}_FINAL.jpg";
        $finalRelPath = "{$finalRelDir}/{$finalFilename}";
        $finalFullPath = Storage::path($finalRelPath);

        imagejpeg($canvas, $finalFullPath, 95);

        // Thumbnail
        $thumbFilename = "{$shortId}_FINAL_THUMB.jpg";
        $thumbRelPath = "{$thumbRelDir}/{$thumbFilename}";
        $thumbFullPath = Storage::path($thumbRelPath);

        $thumbW = 480;
        $thumbH = (int)($height * ($thumbW / $width));
        $thumbImg = imagecreatetruecolor($thumbW, $thumbH);
        imagecopyresampled($thumbImg, $canvas, 0, 0, 0, 0, $thumbW, $thumbH, $width, $height);
        imagejpeg($thumbImg, $thumbFullPath, 85);
        imagedestroy($thumbImg);

        imagedestroy($canvas);

        return [
            'success' => true,
            'file_path' => $finalRelPath,
            'thumbnail_path' => $thumbRelPath,
            'full_path' => $finalFullPath,
            'width' => $width,
            'height' => $height,
            'file_size' => filesize($finalFullPath),
        ];
    }

    protected function drawPhotoSlot($canvas, string $photoPath, int $x, int $y, int $w, int $h, $element): void
    {
        list($srcW, $srcH, $type) = getimagesize($photoPath);
        if ($srcW <= 0 || $srcH <= 0) return;

        $srcImg = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($photoPath),
            IMAGETYPE_PNG => imagecreatefrompng($photoPath),
            default => null,
        };

        if (!$srcImg) return;

        // Crop center-cover logic
        $targetRatio = $w / $h;
        $srcRatio = $srcW / $srcH;

        if ($srcRatio > $targetRatio) {
            $cropH = $srcH;
            $cropW = (int)($srcH * $targetRatio);
            $cropX = (int)(($srcW - $cropW) / 2);
            $cropY = 0;
        } else {
            $cropW = $srcW;
            $cropH = (int)($srcW / $targetRatio);
            $cropX = 0;
            $cropY = (int)(($srcH - $cropH) / 2);
        }

        imagecopyresampled($canvas, $srcImg, $x, $y, $cropX, $cropY, $w, $h, $cropW, $cropH);
        imagedestroy($srcImg);

        // Border jika ditentukan
        if ($element->border_width > 0 && $element->border_color) {
            $borderColor = $this->hexToColor($canvas, $element->border_color);
            for ($b = 0; $b < $element->border_width; $b++) {
                imagerectangle($canvas, $x + $b, $y + $b, $x + $w - $b, $y + $h - $b, $borderColor);
            }
        }
    }

    protected function drawTextElement($canvas, $el, int $x, int $y, int $w, int $h, BoothSession $session): void
    {
        $text = $el->content ?: '';

        // Dynamic tokens replacement
        $text = str_replace('{event_name}', $session->event ? $session->event->name : 'PHOTOBOOTH PRO', $text);
        $text = str_replace('{date}', date('d M Y'), $text);
        $text = str_replace('{session_code}', $session->session_code, $text);

        $color = $this->hexToColor($canvas, $el->font_color ?: '#111827');
        $font = 5; // Built-in font

        // Center vertically & horizontally
        $textLen = strlen($text) * imagefontwidth($font);
        $posX = $x + (int)(($w - $textLen) / 2);
        $posY = $y + (int)(($h - imagefontheight($font)) / 2);

        imagestring($canvas, $font, max($x, $posX), max($y, $posY), $text, $color);
    }

    protected function drawQrCodeElement($canvas, $el, int $x, int $y, int $w, int $h, BoothSession $session): void
    {
        $url = $session->qr_code_url ?: url("/download/{$session->digital_code}");

        // Ciptakan QR sederhana beresolusi slot
        $qrBg = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, $x, $y, $x + $w, $y + $h, $qrBg);

        // Gunakan writer BaconQrCode jika tersedia
        try {
            $renderer = new ImageRenderer(
                new RendererStyle($w, 1),
                new SvgImageBackEnd()
            );
            $writer = new Writer($renderer);
            $svgString = $writer->writeString($url);

            // Tulis QR placeholder / label informatif
            $border = imagecolorallocate($canvas, 30, 41, 59);
            imagerectangle($canvas, $x, $y, $x + $w, $y + $h, $border);
            imagestring($canvas, 3, $x + 10, $y + 10, "SCAN QR CODE", $border);
            imagestring($canvas, 2, $x + 10, $y + 30, "Download Foto", $border);
            imagestring($canvas, 2, $x + 10, $y + $h - 20, $session->digital_code ?: 'PHOTOBOOTH', $border);
        } catch (\Throwable $e) {
            // Fallback
        }
    }

    protected function overlayImageOnCanvas($canvas, string $imagePath, int $x, int $y, int $w, int $h): void
    {
        list($srcW, $srcH, $type) = getimagesize($imagePath);
        $src = match ($type) {
            IMAGETYPE_PNG => imagecreatefrompng($imagePath),
            IMAGETYPE_JPEG => imagecreatefromjpeg($imagePath),
            default => null,
        };
        if ($src) {
            imagecopyresampled($canvas, $src, $x, $y, 0, 0, $w, $h, $srcW, $srcH);
            imagedestroy($src);
        }
    }

    protected function hexToColor($canvas, string $hex): int
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return imagecolorallocate($canvas, $r, $g, $b);
    }
}