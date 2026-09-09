<?php

namespace App\Services\Hardware\Contracts;

interface AudioInterface
{
    public function playSound(string $soundType): array;
    public function setVolume(int $percentage): bool;
    public function getStatus(): array;
}