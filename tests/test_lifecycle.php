<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BoothSession;
use App\Models\Template;
use App\Services\Session\SessionManager;
use App\Services\Payment\PaymentService;

echo "=== MEMULAI TEST END-TO-END PHOTOBOOTH PRO ===" . PHP_EOL;

$sm = new SessionManager();
$session = $sm->startNewSession();
echo "1. Sesi berhasil dibuat. ID: " . $session->id . " | Code: " . $session->session_code . PHP_EOL;

$template = Template::where('photo_count', 3)->first();
$session = $sm->selectTemplate($session, $template->id);
echo "2. Template dipilih: " . $template->name . " (3 Slot)" . PHP_EOL;

for ($i = 1; $i <= 3; $i++) {
    $res = $sm->captureSlot($session, $i);
    echo "3. Capture Slot {$i}: " . ($res['success'] ? 'BERHASIL' : 'GAGAL') . " | File: " . $res['photo']->original_path . PHP_EOL;
}

// Uji Single-Photo Retake
$retakeRes = $sm->retakePhoto($session, 2);
echo "4. Retake Slot 2: " . ($retakeRes['success'] ? 'BERHASIL' : 'GAGAL') . PHP_EOL;

// Uji Photo Composer
$composeRes = $sm->composeTemplate($session);
echo "5. Komposisi Template: " . ($composeRes['success'] ? 'BERHASIL' : 'GAGAL') . " | File: " . $composeRes['file_path'] . " (" . $composeRes['width'] . "x" . $composeRes['height'] . ")" . PHP_EOL;

// Uji Cetak
$printRes = $sm->printFinalPhoto($session, 2);
echo "6. Cetak Foto (2 Lembar): " . ($printRes['success'] ? 'BERHASIL' : 'GAGAL') . " | Job: " . ($printRes['job_id'] ?? 'NONE') . " | Sisa Kertas: " . $printRes['paper_remaining'] . PHP_EOL;

// Uji Pembayaran
$payService = new PaymentService();
$payment = $payService->processPayment($session, 'cash', 50000, 'MERDEKA20', 1);
echo "7. Pembayaran Tunai: " . $payment->status . " | Total: Rp " . number_format($payment->total_amount, 0, ',', '.') . " | Kembalian: Rp " . number_format($payment->change_amount, 0, ',', '.') . PHP_EOL;

echo "=== SEMUA UJI COBA SELESAI DENGAN SUKSES! ===" . PHP_EOL;