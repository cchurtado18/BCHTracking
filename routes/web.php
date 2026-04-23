<?php

use App\Http\Controllers\Web\AgencyController;
use App\Http\Controllers\Web\AgencyClientController;
use App\Http\Controllers\Web\ApiTokenController;
use App\Http\Controllers\Web\AuditLogController;
use App\Http\Controllers\Web\ConsolidationController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DeliveryController;
use App\Http\Controllers\Web\NicConsolidationController;
use App\Http\Controllers\Web\PackageController;
use App\Http\Controllers\Web\PreregistrationController;
use App\Http\Controllers\Web\TrackingController;
use App\Http\Controllers\Web\UserController;
use Illuminate\Support\Facades\Route;

// Público: consulta de tracking (sin autenticación)
Route::get('/tracking', [TrackingController::class, 'index'])->name('tracking.index');

require __DIR__.'/auth.php';

// Panel: requiere autenticación
Route::middleware(['auth'])->group(function () {
    // Dashboard y reporte: administrador o usuario de agencia (usuario regular central no tiene acceso)
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/reporte-paquetes/solicitar', [DashboardController::class, 'reporteSolicitar'])->name('reporte.solicitar');
    Route::get('/reporte-paquetes', [DashboardController::class, 'reportePaquetes'])->name('reporte.paquetes');

    // Solo administrador: agencias, auditoría, usuarios
    Route::middleware('admin')->group(function () {
        Route::resource('agencies', AgencyController::class);
        Route::post('agencies/{id}/toggle', [AgencyController::class, 'toggle'])->name('agencies.toggle');
        Route::post('agencies/{agency}/users/{user}/reset-password', [AgencyController::class, 'resetUserPassword'])->name('agencies.users.reset-password');
        Route::prefix('agencies/{agency_id}/clients')->name('agency-clients.')->group(function () {
            Route::get('/', [AgencyClientController::class, 'index'])->name('index');
            Route::get('/create', [AgencyClientController::class, 'create'])->name('create');
            Route::post('/', [AgencyClientController::class, 'store'])->name('store');
        });
        Route::prefix('agency-clients')->name('agency-clients.')->group(function () {
            Route::get('/{id}', [AgencyClientController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [AgencyClientController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AgencyClientController::class, 'update'])->name('update');
            Route::post('/{id}/toggle', [AgencyClientController::class, 'toggle'])->name('toggle');
        });
        Route::get('auditoria', [AuditLogController::class, 'index'])->name('audit.index');
        Route::get('auditoria/{id}', [AuditLogController::class, 'show'])->name('audit.show');
        Route::resource('users', UserController::class)->except(['show']);
    });

    // Usuario regular y administrador: preregistros, consolidaciones, paquetes, entregas
    Route::resource('preregistrations', PreregistrationController::class);
    Route::get('preregistrations/courier/quick', [PreregistrationController::class, 'quickCourier'])->name('preregistrations.quick-courier');
    Route::post('preregistrations/courier/quick', [PreregistrationController::class, 'storeQuickCourier'])->name('preregistrations.store-quick-courier');
    Route::get('preregistrations/{id}/label', [PreregistrationController::class, 'label'])->name('preregistrations.label');
    Route::get('preregistrations-dropoff-labels', [PreregistrationController::class, 'dropoffLabels'])->name('preregistrations.dropoff-labels');
    Route::post('preregistrations/{id}/photos', [PreregistrationController::class, 'uploadPhoto'])->name('preregistrations.upload-photo');
    Route::post('preregistrations/{id}/photos/{photo}/move', [PreregistrationController::class, 'movePhoto'])->name('preregistrations.photos.move');

    Route::post('preregistrations/{preregistration}/create-single-consolidation', [ConsolidationController::class, 'createSingleFromPreregistration'])->name('preregistrations.create-single-consolidation');
    Route::get('consolidations/create/select', [ConsolidationController::class, 'createSelect'])->name('consolidations.create-select');
    Route::get('consolidations/create/scan', [ConsolidationController::class, 'createScan'])->name('consolidations.create-scan');
    Route::post('consolidations/store-scan', [ConsolidationController::class, 'storeScan'])->name('consolidations.store-scan');
    Route::resource('consolidations', ConsolidationController::class);
    Route::get('consolidations/{id}/label', [ConsolidationController::class, 'label'])->name('consolidations.label');
    Route::post('consolidations/{id}/add-item', [ConsolidationController::class, 'addItem'])->name('consolidations.add-item');
    Route::post('consolidations/{id}/send', [ConsolidationController::class, 'send'])->name('consolidations.send');

    Route::prefix('nic-consolidations')->name('nic-consolidations.')->group(function () {
        Route::get('/', [NicConsolidationController::class, 'index'])->name('index');
        Route::get('/{id}', [NicConsolidationController::class, 'show'])->name('show');
        Route::post('/{id}/scan', [NicConsolidationController::class, 'scan'])->name('scan');
    });

    Route::prefix('packages')->name('packages.')->group(function () {
        Route::get('/', [PackageController::class, 'index'])->name('index');
        Route::get('/{id}', [PackageController::class, 'show'])->name('show');
        Route::get('/{id}/process', [PackageController::class, 'showProcess'])->name('process');
        Route::post('/{id}/process', [PackageController::class, 'process'])->name('process.store');
        Route::post('/{id}/reprint-label', [PackageController::class, 'reprintLabel'])->name('reprint-label');
    });

    Route::prefix('deliveries')->name('deliveries.')->group(function () {
        Route::get('/', [DeliveryController::class, 'index'])->name('index');
        Route::get('/batch', [DeliveryController::class, 'batch'])->name('batch');
        Route::get('/print-report', [DeliveryController::class, 'printReport'])->name('print-report');
        Route::get('/scan', [DeliveryController::class, 'scan'])->name('scan');
        Route::post('/scan', [DeliveryController::class, 'processScan'])->name('process-scan');
        Route::get('/{id}', [DeliveryController::class, 'show'])->name('show');
    });

    Route::get('api-tokens', [ApiTokenController::class, 'index'])->name('api-tokens.index');
    Route::post('api-tokens', [ApiTokenController::class, 'store'])->name('api-tokens.store');
    Route::delete('api-tokens/{tokenId}', [ApiTokenController::class, 'destroy'])->name('api-tokens.destroy');
});
