<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Kiosk\KioskController;
use App\Http\Controllers\ControllerPageController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\TemplateController;
use App\Http\Controllers\Admin\DeviceAdminController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\PromoAdminController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Api\SessionApiController;
use App\Http\Controllers\Api\DeviceApiController;
use App\Http\Controllers\Api\PromoApiController;

/*
|--------------------------------------------------------------------------
| Web Routes - PHOTOBOOTH PRO
|--------------------------------------------------------------------------
*/

// 1. KIOSK TOUCHSCREEN FLOW (CUSTOMER MODE)
Route::get('/', [KioskController::class, 'index'])->name('kiosk.index');
Route::get('/session/{sessionId}/template', [KioskController::class, 'templateSelect'])->name('kiosk.template');
Route::get('/session/{sessionId}/camera', [KioskController::class, 'camera'])->name('kiosk.camera');
Route::get('/session/{sessionId}/success', [KioskController::class, 'success'])->name('kiosk.success');

// 2. REMOTE OPERATOR TABLET CONTROLLER (LAN / WI-FI)
Route::get('/controller', [ControllerPageController::class, 'index'])->name('controller.index');
Route::get('/api/controller/status', [ControllerPageController::class, 'status']);

// 3. GUEST DIGITAL DOWNLOAD PAGE (VIA QR CODE)
Route::get('/download/{digitalCode}', [DownloadController::class, 'show'])->name('download.show');

// 4. AUTHENTICATION (ADMIN & OPERATOR)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// 5. ADMIN CONTROL PANEL
Route::prefix('admin')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    
    // Event Management
    Route::get('/events', [EventController::class, 'index'])->name('admin.events');
    
    // Templates & Visual Template Builder
    Route::get('/templates', [TemplateController::class, 'index'])->name('admin.templates');
    Route::get('/templates/builder', [TemplateController::class, 'builder'])->name('admin.templates.builder');
    
    // Devices & Compatibility Center
    Route::get('/devices', [DeviceAdminController::class, 'index'])->name('admin.devices');
    Route::get('/devices/compatibility', [DeviceAdminController::class, 'compatibility'])->name('admin.devices.compatibility');
    
    // Gallery
    Route::get('/gallery', [GalleryController::class, 'index'])->name('admin.gallery');
    
    // Promos & Vouchers
    Route::get('/promos', [PromoAdminController::class, 'index'])->name('admin.promos');
    
    // Reports & Statistics
    Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports');
    
    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('admin.settings');
});

// 6. HARDWARE & CORE SESSION REST API
Route::prefix('api')->group(function () {
    // Session API
    Route::post('/session/start', [SessionApiController::class, 'start']);
    Route::post('/session/{sessionId}/select-template', [SessionApiController::class, 'selectTemplate']);
    Route::post('/session/{sessionId}/capture', [SessionApiController::class, 'capture']);
    Route::post('/session/{sessionId}/retake', [SessionApiController::class, 'retake']);
    Route::post('/session/{sessionId}/compose', [SessionApiController::class, 'compose']);
    Route::post('/session/{sessionId}/print', [SessionApiController::class, 'print']);
    Route::post('/session/{sessionId}/payment', [SessionApiController::class, 'payment']);
    Route::get('/session/{sessionId}/status', [SessionApiController::class, 'status']);

    // Hardware Devices API
    Route::get('/devices/overview', [DeviceApiController::class, 'overview']);
    Route::post('/camera/test', [DeviceApiController::class, 'testCamera']);
    Route::post('/camera/settings', [DeviceApiController::class, 'setCameraSettings']);
    Route::post('/printer/test', [DeviceApiController::class, 'testPrinter']);

    // Promo API
    Route::post('/promo/check', [PromoApiController::class, 'check']);

    // Admin API
    Route::post('/admin/events', [EventController::class, 'store']);
    Route::post('/admin/events/{id}/activate', [EventController::class, 'activate']);
    Route::post('/admin/templates/save', [TemplateController::class, 'save']);
    Route::post('/admin/promos', [PromoAdminController::class, 'store']);
    Route::get('/admin/reports/export-csv', [ReportController::class, 'exportCsv']);
    Route::post('/admin/settings', [SettingController::class, 'save']);
});