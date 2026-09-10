<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Event;
use App\Models\Template;
use App\Models\TemplateElement;
use App\Models\Camera;
use App\Models\Printer;
use App\Models\Device;
use App\Models\Promo;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles
        $adminRole = Role::create([
            'name' => 'Administrator',
            'slug' => 'admin',
            'description' => 'Akses penuh ke semua pengaturan, template, device, dan laporan.',
        ]);

        $operatorRole = Role::create([
            'name' => 'Operator Booth',
            'slug' => 'operator',
            'description' => 'Akses operasional photo booth, kiosk, retake, cetak, dan status perangkat.',
        ]);

        // 2. Users
        User::create([
            'name' => 'Admin Photobooth',
            'email' => 'admin@photobooth.pro',
            'role' => 'admin',
            'role_id' => $adminRole->id,
            'pin' => '1234',
            'phone' => '081234567890',
            'password' => Hash::make('password'),
        ]);

        User::create([
            'name' => 'Operator Studio',
            'email' => 'operator@photobooth.pro',
            'role' => 'operator',
            'role_id' => $operatorRole->id,
            'pin' => '0000',
            'phone' => '081298765432',
            'password' => Hash::make('password'),
        ]);

        // 3. Default Event
        $event = Event::create([
            'name' => 'Wedding Budi & Ayu',
            'slug' => 'wedding-budi-ayu',
            'description' => 'Pesta Pernikahan Grand Ballroom Hotel Mulia',
            'event_date' => '2026-09-10',
            'location' => 'Grand Ballroom, Lt. 3 Hotel Mulia Jakarta',
            'default_price' => 25000,
            'extra_print_price' => 10000,
            'watermark_text' => 'The Wedding of Budi & Ayu - 10.09.2026',
            'countdown_seconds' => 5,
            'is_active' => true,
        ]);

        // 4. Default Templates
        // Template A: 3-Photo Classic Strip (Vertikal Strip)
        $t1 = Template::create([
            'name' => 'Classic 3-Photo Strip',
            'slug' => 'classic-3-photo-strip',
            'description' => 'Format strip vertikal legendaris 3 foto dengan logo event dan QR Code.',
            'category' => 'wedding',
            'photo_count' => 3,
            'width' => 1200,
            'height' => 1800,
            'orientation' => 'portrait',
            'paper_size' => '4R',
            'background_color' => '#ffffff',
            'frame_style' => 'strip',
            'price' => 25000,
            'is_active' => true,
            'is_default' => true,
        ]);

        TemplateElement::create([
            'template_id' => $t1->id,
            'type' => 'text',
            'content' => '{event_name}',
            'x' => 5,
            'y' => 3,
            'width' => 90,
            'height' => 5,
            'font_family' => 'Poppins',
            'font_size' => 32,
            'font_color' => '#1e293b',
            'font_weight' => 'bold',
            'text_align' => 'center',
            'z_index' => 2,
        ]);

        // 3 Photo Slots
        for ($i = 1; $i <= 3; $i++) {
            TemplateElement::create([
                'template_id' => $t1->id,
                'type' => 'photo_slot',
                'slot_index' => $i,
                'label' => "Foto {$i}",
                'x' => 10,
                'y' => 9 + (($i - 1) * 27),
                'width' => 80,
                'height' => 24,
                'border_width' => 2,
                'border_color' => '#cbd5e1',
                'border_radius' => 8,
                'z_index' => 1,
            ]);
        }

        // Date & QR
        TemplateElement::create([
            'template_id' => $t1->id,
            'type' => 'text',
            'content' => '{date}',
            'x' => 10,
            'y' => 92,
            'width' => 50,
            'height' => 4,
            'font_family' => 'Poppins',
            'font_size' => 20,
            'font_color' => '#64748b',
            'font_weight' => 'normal',
            'text_align' => 'left',
            'z_index' => 2,
        ]);

        TemplateElement::create([
            'template_id' => $t1->id,
            'type' => 'qr_code',
            'label' => 'QR Download',
            'x' => 72,
            'y' => 90,
            'width' => 18,
            'height' => 8,
            'z_index' => 3,
        ]);

        // Template B: Minimalist 4-Grid
        $t2 = Template::create([
            'name' => 'Minimalist 4-Grid',
            'slug' => 'minimalist-4-grid',
            'description' => 'Grid 2x2 modern elegan bernuansa minimalis ala studio Korea.',
            'category' => 'birthday',
            'photo_count' => 4,
            'width' => 1200,
            'height' => 1800,
            'orientation' => 'portrait',
            'paper_size' => '4R',
            'background_color' => '#f8fafc',
            'frame_style' => 'grid',
            'price' => 30000,
            'is_active' => true,
            'is_default' => false,
        ]);

        // Header Title
        TemplateElement::create([
            'template_id' => $t2->id,
            'type' => 'text',
            'content' => 'MEMORIES & MOMENTS',
            'x' => 5,
            'y' => 3,
            'width' => 90,
            'height' => 5,
            'font_family' => 'Poppins',
            'font_size' => 30,
            'font_color' => '#0f172a',
            'font_weight' => 'bold',
            'z_index' => 2,
        ]);

        // 4 slots in 2x2 grid
        $gridCoords = [
            ['x' => 6, 'y' => 10],
            ['x' => 52, 'y' => 10],
            ['x' => 6, 'y' => 51],
            ['x' => 52, 'y' => 51],
        ];

        foreach ($gridCoords as $idx => $c) {
            TemplateElement::create([
                'template_id' => $t2->id,
                'type' => 'photo_slot',
                'slot_index' => $idx + 1,
                'label' => "Slot " . ($idx + 1),
                'x' => $c['x'],
                'y' => $c['y'],
                'width' => 42,
                'height' => 38,
                'border_width' => 0,
                'border_radius' => 12,
                'z_index' => 1,
            ]);
        }

        // Footer QR
        TemplateElement::create([
            'template_id' => $t2->id,
            'type' => 'qr_code',
            'label' => 'Scan QR',
            'x' => 42,
            'y' => 91,
            'width' => 16,
            'height' => 7,
            'z_index' => 3,
        ]);

        // Template C: Single Portrait Studio
        $t3 = Template::create([
            'name' => 'Single Luxury Portrait',
            'slug' => 'single-luxury-portrait',
            'description' => 'Satu foto potret besar mewah dengan aksen bingkai emas studio.',
            'category' => 'portrait',
            'photo_count' => 1,
            'width' => 1200,
            'height' => 1800,
            'orientation' => 'portrait',
            'paper_size' => '4R',
            'background_color' => '#0f172a',
            'frame_style' => 'gold',
            'price' => 35000,
            'is_active' => true,
            'is_default' => false,
        ]);

        TemplateElement::create([
            'template_id' => $t3->id,
            'type' => 'photo_slot',
            'slot_index' => 1,
            'label' => 'Main Portrait',
            'x' => 8,
            'y' => 8,
            'width' => 84,
            'height' => 76,
            'border_width' => 4,
            'border_color' => '#f59e0b',
            'border_radius' => 16,
            'z_index' => 1,
        ]);

        TemplateElement::create([
            'template_id' => $t3->id,
            'type' => 'text',
            'content' => '{event_name}',
            'x' => 8,
            'y' => 87,
            'width' => 64,
            'height' => 5,
            'font_family' => 'Poppins',
            'font_size' => 28,
            'font_color' => '#f8fafc',
            'font_weight' => 'bold',
            'text_align' => 'left',
            'z_index' => 2,
        ]);

        TemplateElement::create([
            'template_id' => $t3->id,
            'type' => 'qr_code',
            'label' => 'QR',
            'x' => 76,
            'y' => 86,
            'width' => 16,
            'height' => 9,
            'z_index' => 2,
        ]);

        // Template D: Korean Life4Cuts 4-Strip
        $t4 = Template::create([
            'name' => 'Korean Life4Cuts (인생네컷)',
            'slug' => 'korean-life4cuts-strip',
            'description' => 'Strip 4 foto vertikal klasik ala photobooth Korea Life4Cuts dengan tanggal dan tipografi estetik.',
            'category' => 'studio',
            'photo_count' => 4,
            'width' => 1200,
            'height' => 1800,
            'orientation' => 'portrait',
            'paper_size' => '4R',
            'background_color' => '#ffffff',
            'frame_style' => 'strip',
            'price' => 30000,
            'is_active' => true,
            'is_default' => false,
        ]);

        TemplateElement::create([
            'template_id' => $t4->id,
            'type' => 'text',
            'content' => 'LIFE FOUR CUTS • 인생네컷',
            'x' => 5,
            'y' => 2.5,
            'width' => 90,
            'height' => 4,
            'font_family' => 'Poppins',
            'font_size' => 26,
            'font_color' => '#0f172a',
            'font_weight' => 'bold',
            'text_align' => 'center',
            'z_index' => 2,
        ]);

        for ($i = 1; $i <= 4; $i++) {
            TemplateElement::create([
                'template_id' => $t4->id,
                'type' => 'photo_slot',
                'slot_index' => $i,
                'label' => "Foto {$i}",
                'x' => 12,
                'y' => 7.5 + (($i - 1) * 20.5),
                'width' => 76,
                'height' => 19,
                'border_width' => 0,
                'border_radius' => 6,
                'z_index' => 1,
            ]);
        }

        TemplateElement::create([
            'template_id' => $t4->id,
            'type' => 'text',
            'content' => '{date} • INSAENGNEKEOT',
            'x' => 12,
            'y' => 92,
            'width' => 55,
            'height' => 4,
            'font_family' => 'Poppins',
            'font_size' => 18,
            'font_color' => '#64748b',
            'font_weight' => 'normal',
            'text_align' => 'left',
            'z_index' => 2,
        ]);

        TemplateElement::create([
            'template_id' => $t4->id,
            'type' => 'qr_code',
            'label' => 'Scan QR',
            'x' => 74,
            'y' => 90,
            'width' => 14,
            'height' => 7,
            'z_index' => 3,
        ]);

        // 5. Cameras
        Camera::create([
            'name' => 'Canon EOS R6 Mark II',
            'brand' => 'Canon',
            'model' => 'EOS R6 II',
            'adapter' => 'mock',
            'connection_type' => 'USB 3.2',
            'status' => 'ready',
            'battery_level' => 94,
            'storage_remaining' => '48.2 GB Free',
            'iso' => '400',
            'shutter_speed' => '1/160',
            'aperture' => 'f/2.8',
            'white_balance' => 'Auto (Daylight)',
            'focus_mode' => 'Eye Detection AF',
            'is_default' => true,
            'capabilities' => [
                'live_view' => true,
                'remote_capture' => true,
                'download' => true,
                'iso_control' => true,
                'shutter_control' => true,
                'aperture_control' => true,
                'wb_control' => true,
                'focus_control' => true,
                'battery_read' => true,
                'storage_read' => true,
            ],
        ]);

        Camera::create([
            'name' => 'Sony Alpha 7 IV',
            'brand' => 'Sony',
            'model' => 'ILCE-7M4',
            'adapter' => 'sony',
            'connection_type' => 'USB-C',
            'status' => 'ready',
            'battery_level' => 91,
            'storage_remaining' => '85 GB Free',
            'iso' => '320',
            'shutter_speed' => '1/160',
            'aperture' => 'f/2.8',
            'white_balance' => 'Auto',
            'focus_mode' => 'Real-time Tracking AF',
            'is_default' => false,
            'capabilities' => [
                'live_view' => true,
                'remote_capture' => true,
                'download' => true,
                'iso_control' => true,
                'shutter_control' => true,
                'aperture_control' => true,
                'wb_control' => true,
                'focus_control' => true,
                'battery_read' => true,
                'storage_read' => true,
            ],
        ]);

        Camera::create([
            'name' => 'Logitech Brio 4K / USB Cam',
            'brand' => 'Webcam',
            'model' => 'Brio 4K Ultra HD',
            'adapter' => 'webcam',
            'connection_type' => 'USB 3.0',
            'status' => 'ready',
            'battery_level' => 100,
            'storage_remaining' => 'Host Disk',
            'is_default' => false,
            'capabilities' => [
                'live_view' => true,
                'remote_capture' => true,
                'download' => true,
                'iso_control' => false,
                'shutter_control' => false,
                'aperture_control' => false,
                'wb_control' => false,
                'focus_control' => false,
                'battery_read' => false,
                'storage_read' => false,
            ],
        ]);

        // 6. Printers
        Printer::create([
            'name' => 'DNP DS-RX1HS Dye-Sublimation',
            'brand' => 'DNP',
            'model' => 'DS-RX1HS',
            'adapter' => 'mock',
            'connection_type' => 'USB 2.0',
            'status' => 'ready',
            'default_paper_size' => '4R',
            'supported_paper_sizes' => ['4R', '5R', '6R', 'Strip 2x6'],
            'print_quality' => 'High Quality (300 DPI)',
            'paper_count' => 380,
            'is_default' => true,
        ]);

        Printer::create([
            'name' => 'Epson SureLab D1070',
            'brand' => 'Epson',
            'model' => 'SureLab SL-D1070',
            'adapter' => 'windows',
            'connection_type' => 'USB / LAN',
            'status' => 'ready',
            'default_paper_size' => '4R',
            'supported_paper_sizes' => ['4R', '5R', '6R', 'A4'],
            'print_quality' => 'Fine Art (720 DPI)',
            'paper_count' => 450,
            'is_default' => false,
        ]);

        // 7. General Devices
        Device::create([
            'device_type' => 'display',
            'name' => 'Elo Touch 24-inch Kiosk Screen',
            'status' => 'connected',
            'metadata' => ['touch' => true, 'refresh_rate' => 60, 'resolution' => '1920x1080'],
            'last_seen' => now(),
        ]);

        Device::create([
            'device_type' => 'audio',
            'name' => 'Yamaha StagePas Bluetooth Speaker',
            'status' => 'connected',
            'metadata' => ['volume' => 85, 'latency' => 'low'],
            'last_seen' => now(),
        ]);

        // 8. Promos
        Promo::create([
            'code' => 'MERDEKA20',
            'name' => 'Promo Kemerdekaan 20%',
            'description' => 'Potongan diskon 20% untuk semua template photo booth.',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'min_spend' => 20000,
            'max_discount' => 10000,
            'usage_limit' => 100,
            'usage_count' => 8,
            'is_active' => true,
        ]);

        Promo::create([
            'code' => 'WEDDINGGIFT',
            'name' => 'Free Voucher Wedding Guest',
            'description' => 'Cetak gratis khusus tamu undangan pernikahan Budi & Ayu.',
            'discount_type' => 'percentage',
            'discount_value' => 100,
            'min_spend' => 0,
            'usage_limit' => 500,
            'usage_count' => 25,
            'is_active' => true,
        ]);

        // 9. Settings
        $defaultSettings = [
            ['group' => 'general', 'key' => 'app_name', 'value' => 'PHOTOBOOTH PRO', 'type' => 'string', 'label' => 'Nama Aplikasi'],
            ['group' => 'general', 'key' => 'kiosk_exit_pin', 'value' => '1234', 'type' => 'string', 'label' => 'PIN Keluar Kiosk'],
            ['group' => 'general', 'key' => 'countdown_duration', 'value' => '5', 'type' => 'integer', 'label' => 'Durasi Countdown (detik)'],
            ['group' => 'general', 'key' => 'auto_reset_seconds', 'value' => '20', 'type' => 'integer', 'label' => 'Reset Otomatis ke START (detik)'],
            ['group' => 'audio', 'key' => 'enable_voice_guidance', 'value' => '1', 'type' => 'boolean', 'label' => 'Instruksi Suara Bahasa Indonesia'],
            ['group' => 'audio', 'key' => 'audio_volume', 'value' => '85', 'type' => 'integer', 'label' => 'Volume Suara Speaker'],
            ['group' => 'storage', 'key' => 'auto_cleanup_days', 'value' => '30', 'type' => 'integer', 'label' => 'Pembersihan Otomatis (Hari)'],
            ['group' => 'storage', 'key' => 'keep_originals', 'value' => '1', 'type' => 'boolean', 'label' => 'Simpan Foto Original'],
            ['group' => 'kiosk', 'key' => 'fullscreen_on_start', 'value' => '1', 'type' => 'boolean', 'label' => 'Fullscreen Otomatis Saat Start'],
            ['group' => 'kiosk', 'key' => 'show_touch_indicator', 'value' => '1', 'type' => 'boolean', 'label' => 'Indikator Sentuhan Touchscreen'],
        ];

        foreach ($defaultSettings as $s) {
            Setting::create($s);
        }
    }
}