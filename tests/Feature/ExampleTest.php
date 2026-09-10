<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Event;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_kiosk_redirects_to_login_when_unauthenticated(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_kiosk_is_accessible_when_authenticated(): void
    {
        $user = User::factory()->create();
        Event::create([
            'name' => 'Event Demo',
            'slug' => 'event-demo',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
    }

    public function test_logout_redirects_to_login(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_printer_test_is_successful_when_authenticated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/printer/test');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_camera_test_returns_image_url_and_metadata(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/camera/test');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'image_url',
                'width',
                'height',
                'camera',
                'metadata',
            ]);
    }

    public function test_remote_capture_session_slot_works(): void
    {
        $user = User::factory()->create();
        $event = Event::create([
            'name' => 'Event Demo',
            'slug' => 'event-demo',
            'is_active' => true,
        ]);
        $template = \App\Models\Template::create([
            'name' => 'Strip 3',
            'slug' => 'strip-3',
            'photo_count' => 3,
            'width' => 1200,
            'height' => 1800,
            'orientation' => 'portrait',
            'paper_size' => '4x6',
            'is_active' => true,
        ]);

        $session = \App\Models\BoothSession::create([
            'session_code' => 'PB-TEST-01',
            'event_id' => $event->id,
            'template_id' => $template->id,
            'total_photos_required' => 3,
            'photos_captured_count' => 0,
            'status' => 'capturing',
            'current_step' => 'capturing',
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($user)->postJson("/api/session/{$session->id}/capture", [
            'slot_index' => 1,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'slot_index' => 1,
                'captured_count' => 1,
            ]);

        $this->assertDatabaseHas('session_photos', [
            'session_id' => $session->id,
            'slot_index' => 1,
            'is_accepted' => true,
        ]);
    }

    public function test_device_settings_api_returns_devices_and_lock_status(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/devices/settings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'is_locked',
                'cameras',
                'printers',
                'active_camera',
                'active_printer',
            ]);
    }

    public function test_device_lock_and_unlock_via_pin(): void
    {
        $user = User::factory()->create(['pin' => '1234']);

        // Lock
        $lockRes = $this->actingAs($user)->postJson('/api/devices/lock');
        $lockRes->assertStatus(200)->assertJson(['success' => true, 'is_locked' => true]);

        // Selection rejected when locked
        $selectRes = $this->actingAs($user)->postJson('/api/devices/select', ['camera_id' => 1]);
        $selectRes->assertStatus(403);

        // Unlock with wrong PIN
        $wrongRes = $this->actingAs($user)->postJson('/api/devices/unlock', ['pin' => '9999']);
        $wrongRes->assertStatus(422);

        // Unlock with correct PIN
        $unlockRes = $this->actingAs($user)->postJson('/api/devices/unlock', ['pin' => '1234']);
        $unlockRes->assertStatus(200)->assertJson(['success' => true, 'is_locked' => false]);
    }

    public function test_device_selection_sets_active_camera_and_printer(): void
    {
        $user = User::factory()->create();
        $cam = \App\Models\Camera::create([
            'name' => 'Test Cam',
            'brand' => 'Canon',
            'adapter' => 'mock',
            'is_default' => false,
        ]);
        $printer = \App\Models\Printer::create([
            'name' => 'EPSON L1210 Series Test',
            'brand' => 'Epson',
            'adapter' => 'windows',
            'is_default' => false,
        ]);

        $res = $this->actingAs($user)->postJson('/api/devices/select', [
            'camera_id' => $cam->id,
            'printer_id' => $printer->id,
            'paper_size' => 'A4',
        ]);

        $res->assertStatus(200)->assertJson(['success' => true]);

        $this->assertTrue($cam->fresh()->is_default);
        $this->assertTrue($printer->fresh()->is_default);
        $this->assertEquals('A4', $printer->fresh()->default_paper_size);
    }

    public function test_kiosk_print_uses_configured_printer_and_paper_size(): void
    {
        $user = User::factory()->create();
        $printer = \App\Models\Printer::create([
            'name' => 'EPSON L1210 Kiosk Test',
            'brand' => 'Epson',
            'adapter' => 'mock',
            'is_default' => true,
            'default_paper_size' => 'A4',
        ]);
        \App\Models\Setting::set('active_printer_id', $printer->id, 'hardware');
        \App\Models\Setting::set('active_printer_paper_size', 'A4', 'hardware');

        $event = \App\Models\Event::create([
            'name' => 'Event Kiosk Print',
            'slug' => 'event-kiosk-print',
            'start_date' => now(),
            'end_date' => now()->addDays(1),
            'default_price' => 0,
            'extra_print_price' => 0,
            'is_active' => true,
        ]);

        $template = \App\Models\Template::create([
            'event_id' => $event->id,
            'name' => 'Template Kiosk Print',
            'slug' => 'template-kiosk-print',
            'width' => 1200,
            'height' => 1800,
            'paper_size' => '4R',
            'is_active' => true,
        ]);

        // Buat file dummy final photo
        $testFinalPath = "events/test/sessions/test-print/final/FINAL.jpg";
        \Illuminate\Support\Facades\Storage::disk('public')->put($testFinalPath, 'dummy image content');

        $session = \App\Models\BoothSession::create([
            'session_code' => 'PB-PRINT-01',
            'event_id' => $event->id,
            'template_id' => $template->id,
            'total_photos_required' => 3,
            'photos_captured_count' => 3,
            'final_photo_path' => $testFinalPath,
            'status' => 'ready_to_print',
            'current_step' => 'printing',
            'payment_status' => 'paid',
        ]);

        $res = $this->actingAs($user)->postJson("/api/session/{$session->id}/print", [
            'copies' => 2,
        ]);

        $res->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('print_jobs', [
            'session_id' => $session->id,
            'printer_id' => $printer->id,
            'copies' => 2,
            'paper_size' => 'A4',
            'status' => 'completed',
        ]);

        $this->assertEquals('printed', $session->fresh()->print_status);
        $this->assertEquals(2, $session->fresh()->print_copies);
    }

    public function test_kiosk_camera_page_loads_with_required_props(): void
    {
        $user = User::factory()->create();
        $event = \App\Models\Event::create([
            'name' => 'Event Camera Test',
            'slug' => 'event-camera-test',
            'is_active' => true,
        ]);

        $template = \App\Models\Template::create([
            'event_id' => $event->id,
            'name' => 'Template 1',
            'slug' => 'template-1',
            'width' => 1200,
            'height' => 1800,
            'paper_size' => '4R',
            'is_active' => true,
        ]);

        $session = \App\Models\BoothSession::create([
            'session_code' => 'PB-CAM-01',
            'event_id' => $event->id,
            'template_id' => $template->id,
            'total_photos_required' => 3,
            'photos_captured_count' => 0,
            'status' => 'active',
            'current_step' => 'camera',
            'payment_status' => 'paid',
        ]);

        $res = $this->actingAs($user)->get("/session/{$session->id}/camera");
        $res->assertStatus(200);
        $res->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->component('Kiosk/Camera')
            ->has('session')
            ->has('template')
            ->has('templates')
            ->has('active_printer')
            ->has('active_paper_size')
        );
    }

    public function test_print_station_page_and_apis_work_properly(): void
    {
        $user = User::factory()->create();

        // 1. Check /print-station page loads
        $res = $this->actingAs($user)->get('/print-station');
        $res->assertStatus(200);
        $res->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->component('PrintStation/Index')
            ->has('active_printer')
            ->has('active_paper_size')
            ->has('web_station_enabled')
            ->has('stats')
        );

        // 2. Queue a test print
        $testRes = $this->actingAs($user)->postJson('/api/print-station/test', [
            'copies' => 1,
            'paper_size' => '4R',
        ]);
        $testRes->assertStatus(200)->assertJson(['success' => true]);
        $jobId = $testRes->json('job_id');
        $this->assertNotNull($jobId);

        // 3. Fetch jobs from print station api
        $jobsRes = $this->actingAs($user)->getJson('/api/print-station/jobs');
        $jobsRes->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['pending_jobs', 'recent_jobs']);
        $this->assertTrue(collect($jobsRes->json('pending_jobs'))->pluck('id')->contains($jobId));

        // 4. Update job to completed
        $updateRes = $this->actingAs($user)->postJson("/api/print-station/jobs/{$jobId}/update", [
            'status' => 'completed',
            'progress' => 100,
        ]);
        $updateRes->assertStatus(200)->assertJson(['success' => true]);

        // 5. Reprint job
        $reprintRes = $this->actingAs($user)->postJson("/api/print-station/jobs/{$jobId}/reprint");
        $reprintRes->assertStatus(200)->assertJson(['success' => true]);
        $this->assertEquals('pending', \App\Models\PrintJob::find($jobId)->status);

        // 6. Toggle station
        $toggleRes = $this->actingAs($user)->postJson('/api/print-station/toggle', [
            'enabled' => false,
        ]);
        $toggleRes->assertStatus(200)->assertJson(['success' => true, 'enabled' => false]);
    }

    public function test_payment_and_subsequent_print_reaches_print_station(): void
    {
        $user = User::factory()->create();
        $event = \App\Models\Event::create([
            'name' => 'Paid Event Test',
            'slug' => 'paid-event-test',
            'default_price' => 25000,
            'extra_print_price' => 10000,
            'is_active' => true,
        ]);

        $template = \App\Models\Template::create([
            'event_id' => $event->id,
            'name' => 'Template Paid',
            'slug' => 'template-paid',
            'width' => 1200,
            'height' => 1800,
            'paper_size' => '4R',
            'is_active' => true,
        ]);

        $finalPhoto = "events/test/sessions/test-paid/final/FINAL.jpg";
        \Illuminate\Support\Facades\Storage::disk('public')->put($finalPhoto, 'test image');

        $session = \App\Models\BoothSession::create([
            'session_code' => 'PB-PAID-01',
            'event_id' => $event->id,
            'template_id' => $template->id,
            'total_photos_required' => 3,
            'photos_captured_count' => 3,
            'final_photo_path' => $finalPhoto,
            'status' => 'ready_to_print',
            'payment_status' => 'unpaid',
        ]);

        // 1. Process payment
        $payRes = $this->actingAs($user)->postJson("/api/session/{$session->id}/payment", [
            'method' => 'cash',
            'amount_paid' => 25000,
            'copies' => 1,
        ]);
        $payRes->assertStatus(200)->assertJson(['success' => true]);
        $this->assertEquals('paid', $session->fresh()->payment_status);

        // 2. Trigger print with Web Print Station enabled
        \App\Models\Setting::set('web_print_station_enabled', '1', 'hardware');

        $printer = \App\Models\Printer::create([
            'name' => 'Epson L1210 Kiosk',
            'brand' => 'Epson',
            'adapter' => 'windows',
            'connection_type' => 'USB (PC Local)',
            'default_paper_size' => '4R',
        ]);
        \App\Models\Setting::set('active_printer_id', $printer->id, 'hardware');

        $printRes = $this->actingAs($user)->postJson("/api/session/{$session->id}/print", [
            'copies' => 1,
            'paper_size' => '4R',
        ]);
        $printRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'pending',
            ]);

        // 3. Verify it is visible in Print Station queue
        $stationJobs = $this->actingAs($user)->getJson('/api/print-station/jobs');
        $stationJobs->assertStatus(200);
        $pendingIds = collect($stationJobs->json('pending_jobs'))->pluck('session_id')->all();
        $this->assertContains($session->id, $pendingIds);
    }
}


