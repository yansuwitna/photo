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
}


