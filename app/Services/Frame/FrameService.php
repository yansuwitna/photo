<?php

namespace App\Services\Frame;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FrameService
{
    protected string $storageDisk = 'public';
    protected string $presetsDir = 'frames/presets';
    protected string $uploadsDir = 'frames/uploads';

    public function __construct()
    {
        $this->ensureDirectories();
    }

    protected function ensureDirectories(): void
    {
        if (!Storage::disk($this->storageDisk)->exists($this->presetsDir)) {
            Storage::disk($this->storageDisk)->makeDirectory($this->presetsDir);
        }
        if (!Storage::disk($this->storageDisk)->exists($this->uploadsDir)) {
            Storage::disk($this->storageDisk)->makeDirectory($this->uploadsDir);
        }
    }

    /**
     * Dapatkan daftar semua bingkai (preset bawaan + bingkai kustom yang diunggah)
     */
    public function getAllFrames(): array
    {
        $this->generatePresetFramesIfMissing();

        $frames = [];

        // 1. Preset Bawaan
        $presetFiles = Storage::disk($this->storageDisk)->files($this->presetsDir);
        $presetMetadata = [
            'gold-luxury.png' => [
                'name' => 'Wedding Luxury Gold',
                'category' => 'wedding',
                'description' => 'Bingkai emas mewah dengan aksen sudut ornamen klasik elegan.',
                'color' => '#D4AF37',
            ],
            'korean-minimal.png' => [
                'name' => 'Korean Studio Minimal',
                'category' => 'studio',
                'description' => 'Gaya self-photo studio Korea dengan garis tipis estetik & tipografi modern.',
                'color' => '#0f172a',
            ],
            'cyber-neon.png' => [
                'name' => 'Cyber Neon Party',
                'category' => 'party',
                'description' => 'Border neon menyala warna cyan & magenta untuk konser dan festival.',
                'color' => '#06b6d4',
            ],
            'vintage-film.png' => [
                'name' => 'Vintage 35mm Film',
                'category' => 'retro',
                'description' => 'Gaya analog roll film 35mm dengan lubang sprocket dan frame number klasik.',
                'color' => '#334155',
            ],
            'birthday-confetti.png' => [
                'name' => 'Celebration Confetti',
                'category' => 'birthday',
                'description' => 'Bingkai pesta ceria berhias confetti berkilau untuk ulang tahun dan perayaan.',
                'color' => '#f59e0b',
            ],
            'floral-pastel.png' => [
                'name' => 'Romantic Rose Floral',
                'category' => 'wedding',
                'description' => 'Bingkai nuansa rose-gold romantis dengan aksen dedaunan pastel.',
                'color' => '#f43f5e',
            ],
        ];

        foreach ($presetFiles as $path) {
            $filename = basename($path);
            if (!str_ends_with(strtolower($filename), '.png')) continue;

            $meta = $presetMetadata[$filename] ?? [
                'name' => ucwords(str_replace(['-', '_', '.png'], [' ', ' ', ''], $filename)),
                'category' => 'general',
                'description' => 'Bingkai kustom photobooth.',
                'color' => '#64748b',
            ];

            $frames[] = [
                'id' => 'preset_' . md5($filename),
                'filename' => $filename,
                'path' => $path,
                'url' => '/storage/' . $path,
                'is_preset' => true,
                'name' => $meta['name'],
                'category' => $meta['category'],
                'description' => $meta['description'],
                'theme_color' => $meta['color'],
            ];
        }

        // 2. Upload Kustom dari Pengguna
        $uploadFiles = Storage::disk($this->storageDisk)->files($this->uploadsDir);
        foreach ($uploadFiles as $path) {
            $filename = basename($path);
            if (!preg_match('/\.(png|jpe?g)$/i', $filename)) continue;

            $frames[] = [
                'id' => 'upload_' . md5($filename),
                'filename' => $filename,
                'path' => $path,
                'url' => '/storage/' . $path,
                'is_preset' => false,
                'name' => ucwords(preg_replace('/^[a-f0-9]{8}_/i', '', str_replace(['-', '_', '.png', '.jpg', '.jpeg'], [' ', ' ', '', '', ''], $filename))),
                'category' => 'custom',
                'description' => 'Bingkai kustom yang diunggah pengguna.',
                'theme_color' => '#a855f7',
            ];
        }

        return $frames;
    }

    /**
     * Simpan file bingkai yang diunggah pengguna
     */
    public function storeCustomFrame(UploadedFile $file, ?string $customName = null): array
    {
        $this->ensureDirectories();

        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['png', 'jpg', 'jpeg'])) {
            throw new \InvalidArgumentException("Format file tidak didukung. Harap unggah file PNG atau JPG.");
        }

        $cleanName = $customName 
            ? Str::slug($customName) 
            : Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        
        $randomPrefix = substr(Str::uuid()->toString(), 0, 8);
        $filename = "{$randomPrefix}_{$cleanName}.{$ext}";
        $relPath = "{$this->uploadsDir}/{$filename}";

        $file->storeAs($this->uploadsDir, $filename, $this->storageDisk);

        list($width, $height) = @getimagesize(Storage::disk($this->storageDisk)->path($relPath)) ?: [1200, 1800];

        return [
            'id' => 'upload_' . md5($filename),
            'filename' => $filename,
            'path' => $relPath,
            'url' => '/storage/' . $relPath,
            'width' => $width,
            'height' => $height,
            'name' => $customName ?: $file->getClientOriginalName(),
            'is_preset' => false,
            'category' => 'custom',
        ];
    }

    /**
     * Hapus bingkai kustom
     */
    public function deleteFrame(string $path): bool
    {
        if (str_starts_with($path, $this->uploadsDir) && Storage::disk($this->storageDisk)->exists($path)) {
            return Storage::disk($this->storageDisk)->delete($path);
        }
        return false;
    }

    /**
     * Generate preset frames beresolusi tinggi (1200x1800 px) jika belum tersedia
     */
    public function generatePresetFramesIfMissing(): void
    {
        $presets = [
            'gold-luxury.png' => 'createGoldLuxuryFrame',
            'korean-minimal.png' => 'createKoreanMinimalFrame',
            'cyber-neon.png' => 'createCyberNeonFrame',
            'vintage-film.png' => 'createVintageFilmFrame',
            'birthday-confetti.png' => 'createBirthdayConfettiFrame',
            'floral-pastel.png' => 'createFloralPastelFrame',
        ];

        foreach ($presets as $filename => $method) {
            $relPath = "{$this->presetsDir}/{$filename}";
            $fullPath = Storage::disk($this->storageDisk)->path($relPath);

            if (!file_exists($fullPath) || filesize($fullPath) < 1000) {
                $dir = dirname($fullPath);
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $this->$method($fullPath);
            }
        }
    }

    // =========================================================================
    // GENERATOR BINGKAI PRESET HIGH RESOLUTION (1200 x 1800 px, 300 DPI)
    // =========================================================================

    protected function initCanvas(int $w = 1200, int $h = 1800)
    {
        $img = imagecreatetruecolor($w, $h);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefilledrectangle($img, 0, 0, $w, $h, $transparent);
        imagealphablending($img, true);
        return [$img, $w, $h];
    }

    /**
     * 1. Preset: Wedding Luxury Gold
     */
    protected function createGoldLuxuryFrame(string $savePath): void
    {
        [$img, $w, $h] = $this->initCanvas();

        $goldPrimary = imagecolorallocate($img, 212, 175, 55);
        $goldLight   = imagecolorallocate($img, 245, 224, 120);
        $goldDark    = imagecolorallocate($img, 150, 115, 30);
        $goldAccent  = imagecolorallocatealpha($img, 212, 175, 55, 40);

        imagesetthickness($img, 8);
        imagerectangle($img, 30, 30, $w - 30, $h - 30, $goldPrimary);

        imagesetthickness($img, 3);
        imagerectangle($img, 46, 46, $w - 46, $h - 46, $goldLight);

        imagesetthickness($img, 1);
        imagerectangle($img, 56, 56, $w - 56, $h - 56, $goldDark);

        $corners = [
            [56, 56, 1, 1],
            [$w - 56, 56, -1, 1],
            [56, $h - 56, 1, -1],
            [$w - 56, $h - 56, -1, -1]
        ];

        imagesetthickness($img, 4);
        foreach ($corners as [$cx, $cy, $dx, $dy]) {
            imageline($img, $cx, $cy, $cx + (90 * $dx), $cy, $goldPrimary);
            imageline($img, $cx, $cy, $cx, $cy + (90 * $dy), $goldPrimary);

            imageline($img, $cx + (20 * $dx), $cy + (20 * $dy), $cx + (70 * $dx), $cy + (20 * $dy), $goldLight);
            imageline($img, $cx + (20 * $dx), $cy + (20 * $dy), $cx + (20 * $dx), $cy + (70 * $dy), $goldLight);

            $points = [
                $cx + (35 * $dx), $cy + (10 * $dy),
                $cx + (45 * $dx), $cy + (20 * $dy),
                $cx + (35 * $dx), $cy + (30 * $dy),
                $cx + (25 * $dx), $cy + (20 * $dy),
            ];
            imagefilledpolygon($img, $points, $goldLight);
        }

        $topY = 40;
        $midX = $w / 2;
        imagefilledellipse($img, $midX, $topY, 18, 18, $goldLight);
        imagefilledellipse($img, $midX - 35, $topY, 10, 10, $goldPrimary);
        imagefilledellipse($img, $midX + 35, $topY, 10, 10, $goldPrimary);
        imageline($img, $midX - 160, $topY, $midX - 50, $topY, $goldPrimary);
        imageline($img, $midX + 50, $topY, $midX + 160, $topY, $goldPrimary);

        $botY = $h - 44;
        imagefilledrectangle($img, $midX - 180, $botY - 14, $midX + 180, $botY + 14, $goldAccent);
        imagerectangle($img, $midX - 180, $botY - 14, $midX + 180, $botY + 14, $goldPrimary);
        imagestring($img, 4, $midX - 125, $botY - 7, "SPECIAL EDITION MEMORIES", $goldLight);

        imagepng($img, $savePath, 8);
        imagedestroy($img);
    }

    /**
     * 2. Preset: Korean Studio Minimal
     */
    protected function createKoreanMinimalFrame(string $savePath): void
    {
        [$img, $w, $h] = $this->initCanvas();

        $black = imagecolorallocate($img, 24, 24, 27);
        $gray  = imagecolorallocate($img, 113, 113, 122);
        $light = imagecolorallocate($img, 228, 228, 231);

        imagesetthickness($img, 4);
        imagerectangle($img, 36, 36, $w - 36, $h - 36, $black);

        imagesetthickness($img, 1);
        imagerectangle($img, 44, 44, $w - 44, $h - 44, $light);

        $crosses = [
            [70, 70],
            [$w - 70, 70],
            [70, $h - 70],
            [$w - 70, $h - 70]
        ];
        imagesetthickness($img, 2);
        foreach ($crosses as [$x, $y]) {
            imageline($img, $x - 12, $y, $x + 12, $y, $gray);
            imageline($img, $x, $y - 12, $x, $y + 12, $gray);
        }

        imagestring($img, 5, 80, 52, "PHOTOBOOTH . SELF PHOTO STUDIO", $black);
        imagestring($img, 3, $w - 190, 55, "REC [ 300 DPI ]", $gray);

        $midX = $w / 2;
        imagestring($img, 4, $midX - 140, $h - 68, "LIVE YOUR BEST MOMENT . 2026", $black);
        imageline($img, $midX - 180, $h - 78, $midX + 180, $h - 78, $light);

        imagepng($img, $savePath, 8);
        imagedestroy($img);
    }

    /**
     * 3. Preset: Cyber Neon Party
     */
    protected function createCyberNeonFrame(string $savePath): void
    {
        [$img, $w, $h] = $this->initCanvas();

        $cyan = imagecolorallocate($img, 0, 240, 255);
        $cyanGlow = imagecolorallocatealpha($img, 0, 240, 255, 75);
        $magenta = imagecolorallocate($img, 255, 0, 128);
        $magentaGlow = imagecolorallocatealpha($img, 255, 0, 128, 75);
        $purple = imagecolorallocate($img, 168, 85, 247);

        imagesetthickness($img, 12);
        imagerectangle($img, 28, 28, $w - 28, $h - 28, $cyanGlow);

        imagesetthickness($img, 4);
        imagerectangle($img, 32, 32, $w - 32, $h - 32, $cyan);

        imagesetthickness($img, 8);
        imagerectangle($img, 44, 44, $w - 44, $h - 44, $magentaGlow);

        imagesetthickness($img, 3);
        imagerectangle($img, 48, 48, $w - 48, $h - 48, $magenta);

        $brackets = [
            [28, 28, 1, 1],
            [$w - 28, 28, -1, 1],
            [28, $h - 28, 1, -1],
            [$w - 28, $h - 28, -1, -1]
        ];
        imagesetthickness($img, 6);
        foreach ($brackets as [$bx, $by, $dx, $dy]) {
            imagefilledrectangle($img, $bx, $by, $bx + (60 * $dx), $by + (12 * $dy), $cyan);
            imagefilledrectangle($img, $bx, $by, $bx + (12 * $dx), $by + (60 * $dy), $cyan);
            imageline($img, $bx + (80 * $dx), $by, $bx + (120 * $dx), $by, $magenta);
            imageline($img, $bx, $by + (80 * $dy), $bx, $by + (120 * $dy), $magenta);
        }

        imagesetthickness($img, 2);
        for ($i = 0; $i < 6; $i++) {
            $y = 300 + ($i * 20);
            imageline($img, 15, $y, 30, $y, $cyan);
            imageline($img, $w - 30, $y, $w - 15, $y, $magenta);
        }

        $midX = $w / 2;
        imagefilledrectangle($img, $midX - 160, 22, $midX + 160, 48, $purple);
        imagestring($img, 4, $midX - 130, 27, "// CYBER BOOTH PROTOCOL //", imagecolorallocate($img, 255, 255, 255));

        imagefilledrectangle($img, $midX - 140, $h - 52, $midX + 140, $h - 26, $purple);
        imagestring($img, 4, $midX - 110, $h - 46, "NIGHT PARTY - LIVE MODE", imagecolorallocate($img, 255, 255, 255));

        imagepng($img, $savePath, 8);
        imagedestroy($img);
    }

    /**
     * 4. Preset: Vintage 35mm Film
     */
    protected function createVintageFilmFrame(string $savePath): void
    {
        [$img, $w, $h] = $this->initCanvas();

        $filmBorder = imagecolorallocate($img, 18, 18, 18);
        $filmHole   = imagecolorallocatealpha($img, 0, 0, 0, 127);
        $redStamp   = imagecolorallocate($img, 225, 29, 72);
        $whiteText  = imagecolorallocate($img, 241, 245, 249);

        $stripW = 75;

        imagefilledrectangle($img, 0, 0, $stripW, $h, $filmBorder);
        imagefilledrectangle($img, $w - $stripW, 0, $w, $h, $filmBorder);

        imagefilledrectangle($img, 0, 0, $w, 55, $filmBorder);
        imagefilledrectangle($img, 0, $h - 55, $w, $h, $filmBorder);

        $holeW = 34;
        $holeH = 46;
        $spacing = 80;

        for ($y = 40; $y < $h - 40; $y += $spacing) {
            imagefilledrectangle($img, 20, $y, 20 + $holeW, $y + $holeH, $filmHole);
            imagerectangle($img, 20, $y, 20 + $holeW, $y + $holeH, imagecolorallocate($img, 60, 60, 60));

            imagefilledrectangle($img, $w - 20 - $holeW, $y, $w - 20, $y + $holeH, $filmHole);
            imagerectangle($img, $w - 20 - $holeW, $y, $w - 20, $y + $holeH, imagecolorallocate($img, 60, 60, 60));
        }

        imagestring($img, 3, 22, 10, "SAFETY", $redStamp);
        imagestring($img, 3, 22, 25, "FILM", $whiteText);

        imagestring($img, 3, $w - 68, 10, "KODAK", $redStamp);
        imagestring($img, 3, $w - 65, 25, "400", $whiteText);

        $midX = $w / 2;
        imagestring($img, 5, $midX - 110, 18, "35mm ROLL PHOTOBOOTH", $whiteText);
        imagestring($img, 3, $midX - 60, $h - 38, "FRAME # 24A", $redStamp);

        imagepng($img, $savePath, 8);
        imagedestroy($img);
    }

    /**
     * 5. Preset: Celebration Confetti
     */
    protected function createBirthdayConfettiFrame(string $savePath): void
    {
        [$img, $w, $h] = $this->initCanvas();

        $gold   = imagecolorallocate($img, 245, 158, 11);
        $coral  = imagecolorallocate($img, 244, 63, 94);
        $teal   = imagecolorallocate($img, 20, 184, 166);
        $violet = imagecolorallocate($img, 139, 92, 246);
        $yellow = imagecolorallocate($img, 253, 224, 71);

        $colors = [$gold, $coral, $teal, $violet, $yellow];

        imagesetthickness($img, 6);
        imagerectangle($img, 32, 32, $w - 32, $h - 32, $gold);
        imagesetthickness($img, 2);
        imagerectangle($img, 44, 44, $w - 44, $h - 44, $coral);

        mt_srand(42);
        for ($i = 0; $i < 180; $i++) {
            $edge = mt_rand(0, 3);
            $c = $colors[mt_rand(0, count($colors) - 1)];
            $size = mt_rand(6, 18);

            if ($edge === 0) {
                $x = mt_rand(20, $w - 20);
                $y = mt_rand(20, 100);
            } elseif ($edge === 1) {
                $x = mt_rand(20, $w - 20);
                $y = mt_rand($h - 100, $h - 20);
            } elseif ($edge === 2) {
                $x = mt_rand(20, 80);
                $y = mt_rand(20, $h - 20);
            } else {
                $x = mt_rand($w - 80, $w - 20);
                $y = mt_rand(20, $h - 20);
            }

            if ($i % 3 === 0) {
                imagefilledellipse($img, $x, $y, $size, $size, $c);
            } elseif ($i % 3 === 1) {
                $pts = [$x, $y - $size, $x + ($size/2), $y, $x, $y + $size, $x - ($size/2), $y];
                imagefilledpolygon($img, $pts, $c);
            } else {
                imagesetthickness($img, 4);
                imageline($img, $x, $y, $x + 12, $y + 10, $c);
            }
        }

        $midX = $w / 2;
        imagefilledrectangle($img, $midX - 160, 24, $midX + 160, 56, $gold);
        imagerectangle($img, $midX - 160, 24, $midX + 160, 56, $coral);
        imagestring($img, 5, $midX - 85, 31, "HAPPY CELEBRATION!", imagecolorallocate($img, 15, 23, 42));

        imagefilledrectangle($img, $midX - 150, $h - 56, $midX + 150, $h - 24, $coral);
        imagestring($img, 4, $midX - 115, $h - 48, "BEST MOMENTS TOGETHER", imagecolorallocate($img, 255, 255, 255));

        imagepng($img, $savePath, 8);
        imagedestroy($img);
    }

    /**
     * 6. Preset: Romantic Pastel Floral
     */
    protected function createFloralPastelFrame(string $savePath): void
    {
        [$img, $w, $h] = $this->initCanvas();

        $roseGold = imagecolorallocate($img, 219, 140, 154);
        $pastelPink = imagecolorallocate($img, 253, 232, 236);
        $sageGreen = imagecolorallocate($img, 163, 190, 167);
        $darkBerry = imagecolorallocate($img, 136, 19, 55);

        imagesetthickness($img, 4);
        imagerectangle($img, 36, 36, $w - 36, $h - 36, $roseGold);

        imagesetthickness($img, 1);
        imagerectangle($img, 48, 48, $w - 48, $h - 48, $pastelPink);

        $corners = [
            [54, 54, 1, 1],
            [$w - 54, 54, -1, 1],
            [54, $h - 54, 1, -1],
            [$w - 54, $h - 54, -1, -1],
        ];

        foreach ($corners as [$cx, $cy, $dx, $dy]) {
            imagesetthickness($img, 3);
            imageline($img, $cx, $cy, $cx + (80 * $dx), $cy + (80 * $dy), $sageGreen);

            for ($k = 1; $k <= 4; $k++) {
                $lx = $cx + ($k * 18 * $dx);
                $ly = $cy + ($k * 18 * $dy);

                imagefilledellipse($img, $lx - (12 * $dx), $ly + (6 * $dy), 18, 10, $sageGreen);
                imagefilledellipse($img, $lx + (6 * $dx), $ly - (12 * $dy), 10, 18, $sageGreen);
                imagefilledellipse($img, $lx, $ly, 10, 10, $roseGold);
            }
        }

        $midX = $w / 2;
        imagefilledellipse($img, $midX, 42, 14, 14, $roseGold);
        imagefilledellipse($img, $midX - 25, 42, 8, 8, $sageGreen);
        imagefilledellipse($img, $midX + 25, 42, 8, 8, $sageGreen);

        imagestring($img, 4, $midX - 110, $h - 50, "LOVE & HAPPINESS ALWAYS", $darkBerry);

        imagepng($img, $savePath, 8);
        imagedestroy($img);
    }
}
