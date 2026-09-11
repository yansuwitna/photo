<?php

namespace App\Services\Storage;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorageManager
{
    /**
     * Dapatkan path direktori untuk suatu sesi sesuai spesifikasi:
     * storage/events/{event-slug}/sessions/{SESSION-ID}/{type}/
     */
    public function getSessionPath(string $eventSlug, string $sessionId, string $subfolder = ''): string
    {
        $base = "events/{$eventSlug}/sessions/{$sessionId}";
        return $subfolder ? "{$base}/{$subfolder}" : $base;
    }

    public function getDiskPath(string $eventSlug, string $sessionId, string $subfolder = ''): string
    {
        $relative = $this->getSessionPath($eventSlug, $sessionId, $subfolder);
        return Storage::path($relative);
    }

    public function ensureSessionDirectories(string $eventSlug, string $sessionId): array
    {
        $folders = ['originals', 'edited', 'final', 'thumbnails'];
        $paths = [];

        foreach ($folders as $f) {
            $rel = $this->getSessionPath($eventSlug, $sessionId, $f);
            if (!Storage::exists($rel)) {
                Storage::makeDirectory($rel);
            }
            $paths[$f] = Storage::path($rel);
        }

        return $paths;
    }

    public function generatePhotoFilename(string $sessionId, int $index, string $ext = 'jpg'): string
    {
        $padded = str_pad((string)$index, 3, '0', STR_PAD_LEFT);
        $shortId = substr(strtoupper(str_replace('-', '', $sessionId)), 0, 8);
        return "{$shortId}_{$padded}.{$ext}";
    }

    public function generateFinalFilename(string $sessionId, string $ext = 'jpg'): string
    {
        $shortId = substr(strtoupper(str_replace('-', '', $sessionId)), 0, 8);
        return "{$shortId}_FINAL.{$ext}";
    }

    public function createThumbnail(string $sourceFile, string $targetFile, int $thumbWidth = 400): bool
    {
        try {
            if (!file_exists($sourceFile)) return false;

            $dir = dirname($targetFile);
            \Illuminate\Support\Facades\File::ensureDirectoryExists($dir, 0775, true);

            // Periksa ekstensi GD apakah aktif
            if (!extension_loaded('gd') || !function_exists('imagecreatetruecolor')) {
                // Fallback copy file jika GD tidak tersedia di server
                @copy($sourceFile, $targetFile);
                return true;
            }

            $imgInfo = @getimagesize($sourceFile);
            if (!$imgInfo || empty($imgInfo[0]) || empty($imgInfo[1])) {
                @copy($sourceFile, $targetFile);
                return true;
            }

            $width = $imgInfo[0];
            $height = $imgInfo[1];
            $mime = $imgInfo['mime'] ?? '';

            $thumbHeight = (int)($height * ($thumbWidth / max(1, $width)));
            $thumb = imagecreatetruecolor($thumbWidth, max(1, $thumbHeight));

            $source = null;
            if (str_contains($mime, 'png') && function_exists('imagecreatefrompng')) {
                $source = @imagecreatefrompng($sourceFile);
            } elseif (function_exists('imagecreatefromjpeg')) {
                $source = @imagecreatefromjpeg($sourceFile);
            }

            if (!$source && function_exists('imagecreatefromstring')) {
                $raw = @file_get_contents($sourceFile);
                if ($raw) {
                    $source = @imagecreatefromstring($raw);
                }
            }

            if (!$source) {
                @copy($sourceFile, $targetFile);
                if ($thumb) imagedestroy($thumb);
                return true;
            }

            imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);
            imagejpeg($thumb, $targetFile, 85);

            imagedestroy($thumb);
            imagedestroy($source);

            return true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("StorageManager createThumbnail error: " . $e->getMessage());
            @copy($sourceFile, $targetFile);
            return true;
        }
    }
}