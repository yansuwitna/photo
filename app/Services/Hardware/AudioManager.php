<?php

namespace App\Services\Hardware;

use App\Services\Hardware\Contracts\AudioInterface;

class AudioManager implements AudioInterface
{
    protected int $volume = 85;

    public function playSound(string $soundType): array
    {
        $supportedSounds = [
            'countdown' => 'Beep countdown',
            'shutter' => 'Camera shutter click',
            'smile' => 'Voice: "SMILE!"',
            'ready' => 'Voice: "Siapkan posisi Anda"',
            'captured' => 'Voice: "Foto berhasil diambil"',
            'success' => 'Celebration chime',
            'error' => 'Warning alert tone',
            'print_start' => 'Print spooling sound',
            'print_done' => 'Print finished chime',
        ];

        return [
            'success' => true,
            'type' => $soundType,
            'label' => $supportedSounds[$soundType] ?? $soundType,
            'volume' => $this->volume,
        ];
    }

    public function setVolume(int $percentage): bool
    {
        $this->volume = max(0, min(100, $percentage));
        return true;
    }

    public function getStatus(): array
    {
        return [
            'status' => 'ready',
            'device' => 'System Audio / Speaker',
            'volume' => $this->volume,
            'supported_sounds' => [
                'countdown', 'shutter', 'smile', 'ready', 'captured', 'success', 'error', 'print_start', 'print_done'
            ],
        ];
    }
}