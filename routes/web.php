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
use App\Http\Controllers\Api\FrameApiController;
use App\Http\Controllers\Api\PrintAgentController;
use App\Http\Controllers\PrintStationController;

/*
|--------------------------------------------------------------------------
| Web Routes - PHOTOBOOTH PRO
|--------------------------------------------------------------------------
*/

// 1. AUTHENTICATION (ADMIN & OPERATOR)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// 2. GUEST DIGITAL DOWNLOAD PAGE (VIA QR CODE - PUBLIC ACCESS)
Route::get('/download/{digitalCode}', [DownloadController::class, 'show'])->name('download.show');

// 3. AUTHENTICATED AREA (KIOSK, CONTROLLER, ADMIN & INTERNAL APIS)
Route::middleware(['auth'])->group(function () {
    // KIOSK TOUCHSCREEN FLOW (CUSTOMER MODE - HANYA BISA DIAKSES SETELAH LOGIN)
    Route::get('/', [KioskController::class, 'index'])->name('kiosk.index');
    Route::get('/session/{sessionId}/template', [KioskController::class, 'templateSelect'])->name('kiosk.template');
    Route::get('/session/{sessionId}/camera', [KioskController::class, 'camera'])->name('kiosk.camera');
    Route::get('/session/{sessionId}/success', [KioskController::class, 'success'])->name('kiosk.success');

    // REMOTE OPERATOR TABLET CONTROLLER (LAN / WI-FI)
    Route::get('/controller', [ControllerPageController::class, 'index'])->name('controller.index');
    Route::get('/api/controller/status', [ControllerPageController::class, 'status']);

    // REAL-TIME WEB PRINT STATION (PC CONNECTED TO PRINTER)
    Route::get('/print-station', [PrintStationController::class, 'index'])->name('print-station.index');

    // ADMIN CONTROL PANEL
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

    // HARDWARE & CORE SESSION REST API
    Route::prefix('api')->group(function () {
        // Session API
        Route::post('/session/start', [SessionApiController::class, 'start']);
        Route::post('/session/{sessionId}/select-template', [SessionApiController::class, 'selectTemplate']);
        Route::post('/session/{sessionId}/set-frame', [SessionApiController::class, 'setFrame']);
        Route::post('/session/{sessionId}/capture', [SessionApiController::class, 'capture']);
        Route::post('/session/{sessionId}/retake', [SessionApiController::class, 'retake']);
        Route::post('/session/{sessionId}/compose', [SessionApiController::class, 'compose']);
        Route::post('/session/{sessionId}/print', [SessionApiController::class, 'print']);
        Route::post('/session/{sessionId}/payment', [SessionApiController::class, 'payment']);
        Route::get('/session/{sessionId}/status', [SessionApiController::class, 'status']);

        // Frames & Custom Overlays API
        Route::get('/frames', [FrameApiController::class, 'index']);
        Route::post('/frames/upload', [FrameApiController::class, 'upload']);
        Route::post('/frames/delete', [FrameApiController::class, 'delete']);

        // Hardware Devices API
        Route::get('/devices/overview', [DeviceApiController::class, 'overview']);
        Route::get('/devices/settings', [DeviceApiController::class, 'settings']);
        Route::post('/devices/select', [DeviceApiController::class, 'selectDevices']);
        Route::post('/devices/lock', [DeviceApiController::class, 'lock']);
        Route::post('/devices/unlock', [DeviceApiController::class, 'unlock']);
        Route::post('/devices/sync-printers', [DeviceApiController::class, 'syncPrinters']);
        Route::post('/camera/test', [DeviceApiController::class, 'testCamera']);
        Route::post('/camera/settings', [DeviceApiController::class, 'setCameraSettings']);
        Route::post('/printer/test', [DeviceApiController::class, 'testPrinter']);

        // Promo API
        Route::post('/promo/check', [PromoApiController::class, 'check']);

        // Remote Print Bridge Agent API (Cloud to Local PC Printer)
        Route::post('/agent/sync-printers', [PrintAgentController::class, 'syncPrinters']);
        Route::get('/agent/jobs', [PrintAgentController::class, 'getPendingJobs']);
        Route::post('/agent/jobs/{jobId}/update', [PrintAgentController::class, 'updateJob']);

        // Web Print Station API (Realtime Vue Direct Browser Printing)
        Route::get('/print-station/jobs', [PrintStationController::class, 'jobs']);
        Route::post('/print-station/toggle', [PrintStationController::class, 'toggle']);
        Route::post('/print-station/test', [PrintStationController::class, 'testPrint']);
        Route::post('/print-station/select-printer', [PrintStationController::class, 'selectPrinter']);
        Route::post('/print-station/sync-printers', [PrintStationController::class, 'syncPrinters']);
        Route::post('/print-station/jobs/{jobId}/reprint', [PrintStationController::class, 'reprint']);
        Route::post('/print-station/jobs/{jobId}/print-direct', [PrintStationController::class, 'printDirect']);
        Route::post('/print-station/jobs/{jobId}/update', [PrintAgentController::class, 'updateJob']);

        // Admin API
        Route::post('/admin/events', [EventController::class, 'store']);
        Route::post('/admin/events/{id}/activate', [EventController::class, 'activate']);
        Route::post('/admin/templates/save', [TemplateController::class, 'save']);
        Route::post('/admin/templates/{id}/toggle', [TemplateController::class, 'toggle']);
        Route::delete('/admin/templates/{id}', [TemplateController::class, 'destroy']);
        Route::post('/admin/promos', [PromoAdminController::class, 'store']);
        Route::get('/admin/reports/export-csv', [ReportController::class, 'exportCsv']);
        Route::post('/admin/settings', [SettingController::class, 'save']);
    });
});