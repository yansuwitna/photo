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
     * Render template bersama foto-foto sesi menjadi 1 gambar final beresolusi tinggi (300 DPI)
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

        // Isi warna latar belakang (custom sesi jika diubah user, atau bawaan template)
        $bgHex = ($session->metadata['custom_background_color'] ?? null) ?: ($template->background_color ?: '#ffffff');
        $bgColor = $this->hexToColor($canvas, $bgHex);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $bgColor);

        // Jika ada background image
        $bgImg = $template->background_image;
        if ($bgImg) {
            $bgImgPath = $this->resolveStoragePath($bgImg);
            if ($bgImgPath && file_exists($bgImgPath)) {
                $this->overlayImageOnCanvas($canvas, $bgImgPath, 0, 0, $width, $height);
            }
        }

        // Ambil elemen-elemen template
        $elements = $template->elements()->orderBy('z_index')->get();

        // Fallback jika elements di database kosong
        if ($elements->isEmpty()) {
            $elements = $this->generateFallbackElements($template);
        }

        $photos = $session->photos()->where('is_accepted', true)->orderBy('slot_index')->get()->keyBy('slot_index');

        foreach ($elements as $el) {
            $elX = (int)round(($el->x / 100) * $width);
            $elY = (int)round(($el->y / 100) * $height);
            $elW = (int)round(($el->width / 100) * $width);
            $elH = (int)round(($el->height / 100) * $height);

            if ($el->type === 'photo_slot') {
                $slotIdx = (int)($el->slot_index ?: 1);
                $photo = $photos->get($slotIdx);

                $photoPath = null;
                if ($photo) {
                    $photoPath = $this->resolveStoragePath($photo->original_path);
                }

                if ($photoPath && file_exists($photoPath)) {
                    $this->drawPhotoSlot($canvas, $photoPath, $elX, $elY, $elW, $elH, $el, $width);
                } else {
                    $this->drawEmptyPhotoSlot($canvas, $elX, $elY, $elW, $elH, $el, $width);
                }
            } elseif ($el->type === 'text') {
                $this->drawTextElement($canvas, $el, $elX, $elY, $elW, $elH, $session, $width);
            } elseif ($el->type === 'qr_code') {
                $this->drawQrCodeElement($canvas, $el, $elX, $elY, $elW, $elH, $session);
            } elseif ($el->type === 'sticker' || $el->type === 'image') {
                $contentPath = $this->resolveStoragePath($el->content);
                if ($contentPath && file_exists($contentPath)) {
                    $this->overlayImageOnCanvas($canvas, $contentPath, $elX, $elY, $elW, $elH);
                }
            }
        }

        // Jika ada bingkai overlay (dari metadata sesi kustom atau bawaan template)
        $overlayImage = ($session->metadata['custom_overlay_image'] ?? null) ?: $template->overlay_image;
        if ($overlayImage) {
            $overlayPath = $this->resolveStoragePath($overlayImage);
            if ($overlayPath && file_exists($overlayPath)) {
                $this->overlayImageOnCanvas($canvas, $overlayPath, 0, 0, $width, $height);
            }
        }

        // Simpan hasil render
        $eventSlug = $session->event ? $session->event->slug : 'default';
        $finalRelDir = "events/{$eventSlug}/sessions/{$session->id}/final";
        Storage::disk('public')->makeDirectory($finalRelDir);

        $thumbRelDir = "events/{$eventSlug}/sessions/{$session->id}/thumbnails";
        Storage::disk('public')->makeDirectory($thumbRelDir);

        $shortId = substr(strtoupper(str_replace('-', '', $session->id)), 0, 8);
        $finalFilename = "{$shortId}_FINAL.jpg";
        $finalRelPath = "{$finalRelDir}/{$finalFilename}";
        $finalFullPath = Storage::disk('public')->path($finalRelPath);

        imagejpeg($canvas, $finalFullPath, 95);

        // Thumbnail
        $thumbFilename = "{$shortId}_FINAL_THUMB.jpg";
        $thumbRelPath = "{$thumbRelDir}/{$thumbFilename}";
        $thumbFullPath = Storage::disk('public')->path($thumbRelPath);

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
            'file_size' => file_exists($finalFullPath) ? filesize($finalFullPath) : 0,
        ];
    }

    /**
     * Gambar foto ke dalam slot dengan border radius (rounded corner) dan border yang presisi
     */
    protected function drawPhotoSlot($canvas, string $photoPath, int $x, int $y, int $w, int $h, $element, int $canvasWidth): void
    {
        list($srcW, $srcH, $type) = @getimagesize($photoPath) ?: [0, 0, 0];
        if ($srcW <= 0 || $srcH <= 0) return;

        $srcImg = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($photoPath),
            IMAGETYPE_PNG => imagecreatefrompng($photoPath),
            default => null,
        };

        if (!$srcImg) return;

        // Skala radius dan border sesuai resolusi canvas (referensi builder: 380px)
        $scale = $canvasWidth / 380.0;
        $rawRadius = (float)($element->border_radius ?? 0);
        $radius = 0;
        if ($rawRadius > 0) {
            $radius = $rawRadius <= 30 ? (int)round($rawRadius * $scale) : (int)round($rawRadius);
            $radius = min($radius, (int)($w / 2), (int)($h / 2));
        }

        $rawBw = (float)($element->border_width ?? 0);
        $borderWidth = 0;
        if ($rawBw > 0) {
            $borderWidth = $rawBw <= 15 ? max(1, (int)round($rawBw * $scale)) : (int)round($rawBw);
        }
        $borderColorHex = $element->border_color ?: null;

        // Crop center-cover logic (sama persis dengan CSS object-cover di frontend)
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

        // 1. Buat kanvas slot transparan mandiri
        $slotCanvas = imagecreatetruecolor($w, $h);
        imagealphablending($slotCanvas, false);
        imagesavealpha($slotCanvas, true);
        $transparent = imagecolorallocatealpha($slotCanvas, 0, 0, 0, 127);
        imagefilledrectangle($slotCanvas, 0, 0, $w, $h, $transparent);

        // 2. Resample foto sumber ke kanvas slot
        imagealphablending($slotCanvas, true);
        imagecopyresampled($slotCanvas, $srcImg, 0, 0, $cropX, $cropY, $w, $h, $cropW, $cropH);
        imagedestroy($srcImg);

        // 3. Potong sudut melengkung (Border Radius Clipping dengan Anti-Aliasing)
        if ($radius > 0) {
            $this->applyBorderRadiusMask($slotCanvas, $w, $h, $radius, $transparent);
        }

        // 4. Gambar border jika ditentukan
        if ($borderWidth > 0 && $borderColorHex) {
            $this->drawSlotBorder($slotCanvas, $w, $h, $radius, $borderWidth, $borderColorHex);
        }

        // 5. Tempelkan kanvas slot yang sudah bulat dan berbingkai ke kanvas utama
        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);
        imagecopy($canvas, $slotCanvas, $x, $y, 0, 0, $w, $h);
        imagedestroy($slotCanvas);
    }

    /**
     * Gambar slot kosong ber-radius jika belum ada foto
     */
    protected function drawEmptyPhotoSlot($canvas, int $x, int $y, int $w, int $h, $element, int $canvasWidth): void
    {
        $scale = $canvasWidth / 380.0;
        $rawRadius = (float)($element->border_radius ?? 0);
        $radius = $rawRadius > 0 ? (int)round($rawRadius * $scale) : 0;
        $radius = min($radius, (int)($w / 2), (int)($h / 2));

        $slotCanvas = imagecreatetruecolor($w, $h);
        imagealphablending($slotCanvas, false);
        imagesavealpha($slotCanvas, true);
        $transparent = imagecolorallocatealpha($slotCanvas, 0, 0, 0, 127);
        imagefilledrectangle($slotCanvas, 0, 0, $w, $h, $transparent);

        imagealphablending($slotCanvas, true);
        $phBg = imagecolorallocate($slotCanvas, 241, 245, 249);
        imagefilledrectangle($slotCanvas, 0, 0, $w, $h, $phBg);

        if ($radius > 0) {
            $this->applyBorderRadiusMask($slotCanvas, $w, $h, $radius, $transparent);
        }

        $rawBw = (float)($element->border_width ?? 1);
        $borderWidth = max(1, (int)round($rawBw * $scale));
        $this->drawSlotBorder($slotCanvas, $w, $h, $radius, $borderWidth, $element->border_color ?: '#cbd5e1');

        $gray = imagecolorallocate($slotCanvas, 148, 163, 184);
        $label = "SLOT " . ($element->slot_index ?: 1);
        imagestring($slotCanvas, 4, (int)($w / 2) - 30, (int)($h / 2) - 8, $label, $gray);

        imagealphablending($canvas, true);
        imagecopy($canvas, $slotCanvas, $x, $y, 0, 0, $w, $h);
        imagedestroy($slotCanvas);
    }

    /**
     * Memotong sudut gambar agar melengkung presisi (Border Radius) dengan anti-aliasing halus
     */
    protected function applyBorderRadiusMask($slotCanvas, int $w, int $h, int $radius, int $transparent): void
    {
        imagealphablending($slotCanvas, false);

        for ($py = 0; $py < $radius; $py++) {
            for ($px = 0; $px < $radius; $px++) {
                $dx = $radius - $px;
                $dy = $radius - $py;
                $dist = sqrt($dx * $dx + $dy * $dy);

                if ($dist > $radius - 0.5) {
                    $corners = [
                        [$px, $py],                     // Top-Left
                        [$w - 1 - $px, $py],             // Top-Right
                        [$px, $h - 1 - $py],             // Bottom-Left
                        [$w - 1 - $px, $h - 1 - $py],     // Bottom-Right
                    ];

                    if ($dist >= $radius + 0.5) {
                        // Di luar radius: buat transparan sepenuhnya
                        foreach ($corners as [$cx, $cy]) {
                            imagesetpixel($slotCanvas, $cx, $cy, $transparent);
                        }
                    } else {
                        // Anti-aliasing halus di tepian lengkung
                        $factor = $dist - ($radius - 0.5); // 0.0 s/d 1.0
                        foreach ($corners as [$cx, $cy]) {
                            $rgba = imagecolorat($slotCanvas, $cx, $cy);
                            $r = ($rgba >> 16) & 0xFF;
                            $g = ($rgba >> 8) & 0xFF;
                            $b = $rgba & 0xFF;
                            $curAlpha = ($rgba >> 24) & 0x7F;
                            $newAlpha = min(127, (int)round($curAlpha + (127 - $curAlpha) * $factor));
                            $col = imagecolorallocatealpha($slotCanvas, $r, $g, $b, $newAlpha);
                            imagesetpixel($slotCanvas, $cx, $cy, $col);
                        }
                    }
                }
            }
        }
    }

    /**
     * Menggambar border melengkung sesuai border-radius
     */
    protected function drawSlotBorder($slotCanvas, int $w, int $h, int $radius, int $borderWidth, string $hexColor): void
    {
        imagealphablending($slotCanvas, true);
        $hex = ltrim($hexColor, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $br = hexdec(substr($hex, 0, 2));
        $bg = hexdec(substr($hex, 2, 2));
        $bb = hexdec(substr($hex, 4, 2));
        $bColor = imagecolorallocate($slotCanvas, $br, $bg, $bb);

        if ($radius <= 0) {
            // Border kotak tajam
            for ($b = 0; $b < $borderWidth; $b++) {
                imagerectangle($slotCanvas, $b, $b, $w - 1 - $b, $h - 1 - $b, $bColor);
            }
            return;
        }

        // Border melengkung (Rounded Rectangle Border)
        for ($b = 0; $b < $borderWidth; $b++) {
            $curR = max(1, $radius - $b);
            $curW = $w - 1 - (2 * $b);
            $curH = $h - 1 - (2 * $b);
            $curX = $b;
            $curY = $b;

            // 4 Garis Lurus
            imageline($slotCanvas, $curX + $curR, $curY, $curX + $curW - $curR, $curY, $bColor);
            imageline($slotCanvas, $curX + $curR, $curY + $curH, $curX + $curW - $curR, $curY + $curH, $bColor);
            imageline($slotCanvas, $curX, $curY + $curR, $curX, $curY + $curH - $curR, $bColor);
            imageline($slotCanvas, $curX + $curW, $curY + $curR, $curX + $curW, $curY + $curH - $curR, $bColor);

            // 4 Lengkungan Sudut (Arcs)
            $diam = $curR * 2;
            imagearc($slotCanvas, $curX + $curR, $curY + $curR, $diam, $diam, 180, 270, $bColor);
            imagearc($slotCanvas, $curX + $curW - $curR, $curY + $curR, $diam, $diam, 270, 360, $bColor);
            imagearc($slotCanvas, $curX + $curW - $curR, $curY + $curH - $curR, $diam, $diam, 0, 90, $bColor);
            imagearc($slotCanvas, $curX + $curR, $curY + $curH - $curR, $diam, $diam, 90, 180, $bColor);
        }
    }

    protected function drawTextElement($canvas, $el, int $x, int $y, int $w, int $h, BoothSession $session, int $canvasWidth): void
    {
        $text = $el->content ?: '';

        // Dynamic tokens replacement
        $today = date('Y.m.d');
        $eventName = $session->event ? $session->event->name : 'PHOTOBOOTH MEMORIES';
        $text = str_replace('{event_name}', $eventName, $text);
        $text = str_replace('{date}', $today, $text);
        $text = str_replace('{session_code}', $session->session_code, $text);

        $color = $this->hexToColor($canvas, $el->font_color ?: '#111827');

        // Gunakan TrueType Font jika tersedia di sistem untuk kualitas teks tajam 300 DPI
        $fontFile = 'C:/Windows/Fonts/arialbd.ttf';
        if (!file_exists($fontFile)) {
            $fontFile = 'C:/Windows/Fonts/arial.ttf';
        }

        $fontSize = (int)round(($el->font_size ?: 24) * ($canvasWidth / 1200));

        if (file_exists($fontFile) && function_exists('imagettfbbox')) {
            $bbox = imagettfbbox($fontSize, 0, $fontFile, $text);
            $textWidth = abs($bbox[2] - $bbox[0]);
            $textHeight = abs($bbox[7] - $bbox[1]);

            $posX = $x + (int)(($w - $textWidth) / 2);
            if ($el->text_align === 'left') $posX = $x + 10;
            if ($el->text_align === 'right') $posX = $x + $w - $textWidth - 10;

            $posY = $y + (int)(($h + $textHeight) / 2);

            imagettftext($canvas, $fontSize, 0, max(5, $posX), $posY, $color, $fontFile, $text);
        } else {
            // Fallback font bawaan
            $font = 5;
            $textLen = strlen($text) * imagefontwidth($font);
            $posX = $x + (int)(($w - $textLen) / 2);
            $posY = $y + (int)(($h - imagefontheight($font)) / 2);
            imagestring($canvas, $font, max($x, $posX), max($y, $posY), $text, $color);
        }
    }

    protected function drawQrCodeElement($canvas, $el, int $x, int $y, int $w, int $h, BoothSession $session): void
    {
        $url = $session->qr_code_url ?: url("/download/{$session->digital_code}");

        $qrBg = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, $x, $y, $x + $w, $y + $h, $qrBg);

        try {
            $renderer = new ImageRenderer(
                new RendererStyle($w, 1),
                new SvgImageBackEnd()
            );
            $writer = new Writer($renderer);
            $svgString = $writer->writeString($url);

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
        if (!file_exists($imagePath)) return;
        list($srcW, $srcH, $type) = @getimagesize($imagePath) ?: [0, 0, 0];
        if ($srcW <= 0 || $srcH <= 0) return;

        $src = match ($type) {
            IMAGETYPE_PNG => imagecreatefrompng($imagePath),
            IMAGETYPE_JPEG => imagecreatefromjpeg($imagePath),
            default => null,
        };
        if ($src) {
            imagealphablending($canvas, true);
            imagesavealpha($canvas, true);
            if ($type === IMAGETYPE_PNG) {
                imagealphablending($src, true);
                imagesavealpha($src, true);
            }
            imagecopyresampled($canvas, $src, $x, $y, 0, 0, $w, $h, $srcW, $srcH);
            imagedestroy($src);
        }
    }

    protected function resolveStoragePath(?string $path): ?string
    {
        if (!$path) return null;
        if (file_exists($path)) return $path;

        $publicPath = Storage::disk('public')->path($path);
        if (file_exists($publicPath)) return $publicPath;

        $defaultPath = Storage::path($path);
        if (file_exists($defaultPath)) return $defaultPath;

        $symlinkPath = public_path('storage/' . ltrim($path, '/'));
        if (file_exists($symlinkPath)) return $symlinkPath;

        return null;
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

    protected function generateFallbackElements(Template $template)
    {
        $count = $template->photo_count ?: 4;
        $items = collect();

        for ($i = 1; $i <= $count; $i++) {
            $items->push((object)[
                'type' => 'photo_slot',
                'slot_index' => $i,
                'x' => 10,
                'y' => 8 + (($i - 1) * 21),
                'width' => 80,
                'height' => 19.5,
                'border_radius' => 8,
                'border_width' => 2,
                'border_color' => '#cbd5e1',
                'z_index' => 1,
            ]);
        }

        return $items;
    }
}