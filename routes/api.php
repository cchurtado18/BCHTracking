<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AgencyController;
use App\Http\Controllers\Api\AgencyClientController;
use App\Http\Controllers\Api\ConsolidationController;
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\Api\NicConsolidationController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\PreregistrationController;
use App\Http\Controllers\Api\PublicTrackingController;
use Illuminate\Support\Facades\Route;

// ——— Público (sin autenticación) ———
// Tracking para clientes finales
Route::prefix('public/tracking')->group(function () {
    Route::get('/{code}', [PublicTrackingController::class, 'track']);
});

// Login: obtener token con email + password (limitado por throttle)
Route::post('auth/token', [AuthController::class, 'token'])
    ->middleware('throttle:10,1'); // 10 intentos por minuto

// ——— Protegido con Sanctum (Bearer token) ———
Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::prefix('preregistrations')->group(function () {
        Route::get('/', [PreregistrationController::class, 'index']);
        Route::post('/', [PreregistrationController::class, 'store']);
        Route::get('/{id}/photos', [PreregistrationController::class, 'getPhotos']);
        Route::get('/{id}', [PreregistrationController::class, 'show']);
        Route::post('/{id}/photos', [PreregistrationController::class, 'uploadPhoto']);
    });

    Route::prefix('consolidations')->group(function () {
        Route::get('/', [ConsolidationController::class, 'index']);
        Route::post('/', [ConsolidationController::class, 'store']);
        Route::get('/{id}', [ConsolidationController::class, 'show']);
        Route::post('/{id}/items/by-filter', [ConsolidationController::class, 'addItemsByFilter']);
        Route::post('/{id}/items/by-scan', [ConsolidationController::class, 'addItemByScan']);
        Route::post('/{id}/send', [ConsolidationController::class, 'send']);
    });

    Route::prefix('nic/consolidations')->group(function () {
        Route::post('/{id}/scan', [NicConsolidationController::class, 'scan']);
        Route::get('/{id}/summary', [NicConsolidationController::class, 'summary']);
    });

    Route::prefix('packages')->group(function () {
        Route::get('/', [PackageController::class, 'index']);
        Route::get('/{id}', [PackageController::class, 'show']);
        Route::post('/{id}/process', [PackageController::class, 'process']);
        Route::post('/{id}/reprint-label', [PackageController::class, 'reprintLabel']);
        Route::post('/{id}/mark-holding', [PackageController::class, 'markHolding']);
        Route::post('/{id}/resolve-holding', [PackageController::class, 'resolveHolding']);
    });

    Route::prefix('agencies')->group(function () {
        Route::get('/', [AgencyController::class, 'index']);
        Route::post('/', [AgencyController::class, 'store']);
        Route::get('/{id}', [AgencyController::class, 'show']);
        Route::put('/{id}', [AgencyController::class, 'update']);
        Route::patch('/{id}/toggle', [AgencyController::class, 'toggle']);
    });

    Route::prefix('agencies/{agency_id}/clients')->group(function () {
        Route::get('/', [AgencyClientController::class, 'index']);
        Route::post('/', [AgencyClientController::class, 'store']);
    });

    Route::prefix('agency-clients')->group(function () {
        Route::get('/{id}', [AgencyClientController::class, 'show']);
        Route::put('/{id}', [AgencyClientController::class, 'update']);
        Route::patch('/{id}/toggle', [AgencyClientController::class, 'toggle']);
    });

    Route::prefix('deliveries')->group(function () {
        Route::post('/scan', [DeliveryController::class, 'scan']);
        Route::get('/', [DeliveryController::class, 'index']);
        Route::get('/{id}', [DeliveryController::class, 'show']);
    });
});
