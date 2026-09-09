<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Events
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->date('event_date')->nullable();
            $table->string('location')->nullable();
            $table->string('logo_path')->nullable();
            $table->decimal('default_price', 12, 2)->default(25000);
            $table->decimal('extra_print_price', 12, 2)->default(10000);
            $table->string('watermark_text')->nullable();
            $table->string('watermark_logo')->nullable();
            $table->integer('countdown_seconds')->default(5);
            $table->boolean('is_active')->default(true);
            $table->json('custom_settings')->nullable();
            $table->timestamps();
        });

        // 2. Templates
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('category')->default('general');
            $table->integer('photo_count')->default(3);
            $table->integer('width')->default(1200);
            $table->integer('height')->default(1800);
            $table->string('orientation')->default('portrait'); // portrait, landscape
            $table->string('paper_size')->default('4R'); // 4R, 5R, 6R, Strip 2x6, A4
            $table->string('background_color')->default('#ffffff');
            $table->string('background_image')->nullable();
            $table->string('overlay_image')->nullable();
            $table->string('frame_style')->default('classic');
            $table->decimal('price', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->string('preview_image')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        // 3. Template Elements
        Schema::create('template_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('templates')->onDelete('cascade');
            $table->string('type')->default('photo_slot'); // photo_slot, text, image, sticker, qr_code, shape
            $table->integer('slot_index')->nullable(); // 1, 2, 3...
            $table->string('label')->nullable();
            $table->float('x')->default(0);
            $table->float('y')->default(0);
            $table->float('width')->default(100);
            $table->float('height')->default(100);
            $table->integer('z_index')->default(1);
            $table->float('rotation')->default(0);
            $table->integer('border_radius')->default(0);
            $table->integer('border_width')->default(0);
            $table->string('border_color')->nullable();
            $table->text('content')->nullable();
            $table->string('font_family')->default('Poppins');
            $table->integer('font_size')->default(24);
            $table->string('font_color')->default('#111827');
            $table->string('font_weight')->default('normal');
            $table->string('text_align')->default('center');
            $table->float('opacity')->default(1.0);
            $table->boolean('is_locked')->default(false);
            $table->json('custom_styles')->nullable();
            $table->timestamps();
        });

        // 4. Cameras
        Schema::create('cameras', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('brand')->default('Canon'); // Canon, Sony, Nikon, Webcam, Mock
            $table->string('model')->nullable();
            $table->string('adapter')->default('mock'); // canon, sony, nikon, webcam, mock
            $table->string('connection_type')->default('USB');
            $table->string('port')->nullable();
            $table->string('status')->default('ready'); // ready, busy, error, disconnected
            $table->integer('battery_level')->default(100);
            $table->string('storage_remaining')->nullable()->default('32 GB');
            $table->string('iso')->default('Auto');
            $table->string('shutter_speed')->default('1/125');
            $table->string('aperture')->default('f/4.0');
            $table->string('white_balance')->default('Auto');
            $table->string('focus_mode')->default('AF-S');
            $table->boolean('is_default')->default(false);
            $table->json('capabilities')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        // 5. Printers
        Schema::create('printers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('brand')->nullable()->default('DNP');
            $table->string('model')->nullable();
            $table->string('adapter')->default('mock'); // windows, thermal, dyesub, mock
            $table->string('connection_type')->default('USB');
            $table->string('status')->default('ready'); // ready, printing, paper_empty, error, disconnected
            $table->string('default_paper_size')->default('4R');
            $table->json('supported_paper_sizes')->nullable();
            $table->string('print_quality')->default('high');
            $table->integer('paper_count')->default(400);
            $table->boolean('is_default')->default(true);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        // 6. Devices (General status)
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_type'); // camera, printer, display, audio, tablet, sensor
            $table->string('name');
            $table->string('identifier')->nullable();
            $table->string('status')->default('connected'); // connected, disconnected, warning, error
            $table->string('ip_address')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_seen')->nullable();
            $table->timestamps();
        });

        // 7. Sessions (Photobooth Sessions with UUID)
        Schema::create('sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('session_code')->unique();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('templates')->nullOnDelete();
            $table->foreignId('camera_id')->nullable()->constrained('cameras')->nullOnDelete();
            $table->foreignId('printer_id')->nullable()->constrained('printers')->nullOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('status')->default('init'); // init, template_selected, capturing, reviewing, composing, ready_to_print, printing, completed, cancelled
            $table->integer('total_photos_required')->default(3);
            $table->integer('photos_captured_count')->default(0);
            $table->string('current_step')->default('template');
            $table->string('final_photo_path')->nullable();
            $table->string('final_thumbnail_path')->nullable();
            $table->string('digital_code', 16)->unique()->nullable();
            $table->string('qr_code_url')->nullable();
            $table->string('payment_status')->default('unpaid'); // unpaid, pending, paid, free
            $table->string('print_status')->default('none'); // none, queued, printing, printed, failed
            $table->integer('print_copies')->default(1);
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // 8. Session Photos (Raw and processed shots in session)
        Schema::create('session_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('session_id')->constrained('sessions')->onDelete('cascade');
            $table->integer('slot_index')->default(1);
            $table->string('original_path');
            $table->string('edited_path')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->integer('width')->default(1920);
            $table->integer('height')->default(1080);
            $table->boolean('is_accepted')->default(true);
            $table->integer('retake_count')->default(0);
            $table->json('camera_metadata')->nullable();
            $table->timestamps();
        });

        // 9. Final Photos
        Schema::create('final_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('session_id')->constrained('sessions')->onDelete('cascade');
            $table->string('file_path');
            $table->string('thumbnail_path')->nullable();
            $table->integer('width')->default(1200);
            $table->integer('height')->default(1800);
            $table->string('mime_type')->default('image/jpeg');
            $table->integer('file_size')->nullable();
            $table->timestamps();
        });

        // 10. Print Jobs
        Schema::create('print_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('session_id')->constrained('sessions')->onDelete('cascade');
            $table->foreignId('printer_id')->nullable()->constrained('printers')->nullOnDelete();
            $table->integer('copies')->default(1);
            $table->string('paper_size')->default('4R');
            $table->string('status')->default('pending'); // pending, printing, completed, failed, cancelled
            $table->integer('progress')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // 11. Payments
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('session_id')->constrained('sessions')->onDelete('cascade');
            $table->string('method')->default('cash'); // cash, qris, transfer, free, voucher
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('change_amount', 12, 2)->default(0);
            $table->string('status')->default('pending'); // pending, paid, refunded, failed
            $table->string('reference_number')->nullable();
            $table->text('qris_payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 12. Promos
        Schema::create('promos', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('discount_type')->default('percentage'); // percentage, fixed
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('min_spend', 12, 2)->default(0);
            $table->decimal('max_discount', 12, 2)->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_count')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 13. Promo Usages
        Schema::create('promo_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promo_id')->constrained('promos')->onDelete('cascade');
            $table->foreignUuid('session_id')->constrained('sessions')->onDelete('cascade');
            $table->decimal('discount_applied', 12, 2)->default(0);
            $table->timestamp('used_at')->useCurrent();
            $table->timestamps();
        });

        // 14. Settings
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group')->default('general'); // general, camera, printer, audio, display, payment, storage, kiosk, network
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, json, float
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 15. Activity Logs
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // 16. Device Logs
        Schema::create('device_logs', function (Blueprint $table) {
            $table->id();
            $table->string('device_type'); // camera, printer, audio, display, system
            $table->string('device_id')->nullable();
            $table->string('event');
            $table->text('message');
            $table->string('severity')->default('info'); // info, warning, error, critical
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_logs');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('promo_usages');
        Schema::dropIfExists('promos');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('print_jobs');
        Schema::dropIfExists('final_photos');
        Schema::dropIfExists('session_photos');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('devices');
        Schema::dropIfExists('printers');
        Schema::dropIfExists('cameras');
        Schema::dropIfExists('template_elements');
        Schema::dropIfExists('templates');
        Schema::dropIfExists('events');
    }
};