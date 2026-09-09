<?php

namespace App\Services\Hardware\Contracts;

interface CameraInterface
{
    public function connect(): array;
    public function disconnect(): bool;
    public function getStatus(): array;
    public function capture(string $destinationPath): array;
    public function startLiveView(): array;
    public function stopLiveView(): bool;
    public function downloadPhoto(string $source, string $target): string;
    public function getBattery(): int;
    public function getStorageStatus(): string;
    public function setISO(string $iso): bool;
    public function setShutterSpeed(string $shutterSpeed): bool;
    public function setAperture(string $aperture): bool;
    public function setWhiteBalance(string $wb): bool;
    public function setFocusMode(string $mode): bool;
    public function setCaptureMode(string $mode): bool;
    public function getCapabilities(): array;
}