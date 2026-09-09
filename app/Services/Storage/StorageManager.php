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
        if (!file_exists($sourceFile)) return false;

        $dir = dirname($targetFile);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        list($width, $height) = getimagesize($sourceFile);
        if ($width <= 0 || $height <= 0) return false;

        $thumbHeight = (int)($height * ($thumbWidth / $width));
        $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);

        $source = imagecreatefromjpeg($sourceFile) ?: imagecreatefrompng($sourceFile);
        if (!$source) return false;

        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);
        imagejpeg($thumb, $targetFile, 85);

        imagedestroy($thumb);
        imagedestroy($source);

        return true;
    }
}