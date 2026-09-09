<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$session = App\Models\BoothSession::latest()->first();
$manager = app(App\Services\Session\SessionManager::class);

// Buat gambar dummy 640x480 simulasi tangkapan kamera HP
$img = imagecreatetruecolor(640, 480);
$gold = imagecolorallocate($img, 245, 158, 11);
imagefill($img, 0, 0, $gold);
ob_start();
imagejpeg($img);
$raw = ob_get_clean();
imagedestroy($img);
$base64 = 'data:image/jpeg;base64,' . base64_encode($raw);

$res = $manager->captureSlot($session, 1, $base64);
if ($res['success'] && $res['photo']->width == 640) {
    echo "TEST MOBILE CAPTURE BASE64: BERHASIL (Resolusi: {$res['photo']->width}x{$res['photo']->height}, File: {$res['photo']->original_path})\n";
} else {
    echo "TEST FAILED\n";
    exit(1);
}
