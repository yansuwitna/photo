<?php

function createIcon(int $size, string $file): void {
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);

    $bg = imagecolorallocate($img, 15, 23, 42); // slate-900
    imagefill($img, 0, 0, $bg);

    $gold = imagecolorallocate($img, 245, 158, 11); // amber-500
    $purple = imagecolorallocate($img, 168, 85, 247); // purple-500
    $white = imagecolorallocate($img, 255, 255, 255);

    // Rings
    imagefilledellipse($img, (int)($size / 2), (int)($size / 2), (int)($size * 0.85), (int)($size * 0.85), $purple);
    imagefilledellipse($img, (int)($size / 2), (int)($size / 2), (int)($size * 0.78), (int)($size * 0.78), $bg);
    imagefilledellipse($img, (int)($size / 2), (int)($size / 2), (int)($size * 0.65), (int)($size * 0.65), $gold);
    imagefilledellipse($img, (int)($size / 2), (int)($size / 2), (int)($size * 0.55), (int)($size * 0.55), $bg);

    // Camera lens aperture center
    imagefilledellipse($img, (int)($size / 2), (int)($size / 2), (int)($size * 0.35), (int)($size * 0.35), $purple);

    // Lens reflection dot
    imagefilledellipse($img, (int)($size * 0.45), (int)($size * 0.45), (int)($size * 0.08), (int)($size * 0.08), $white);

    imagepng($img, $file);
    imagedestroy($img);
}

createIcon(192, __DIR__ . '/../public/icon-192.png');
createIcon(512, __DIR__ . '/../public/icon-512.png');
echo "PWA Icons generated successfully!\n";
