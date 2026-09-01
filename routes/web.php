<?php

use App\Http\Controllers\Web\AccountingCreditNoteController;
use App\Http\Controllers\Web\AccountingExpenseController;
use App\Http\Controllers\Web\AccountingInvoiceController;
use App\Http\Controllers\Web\AccountingPaymentController;
use App\Http\Controllers\Web\AccountingProfitabilityController;
use App\Http\Controllers\Web\AccountingRateCardController;
use App\Http\Controllers\Web\AccountingReceivableController;
use App\Http\Controllers\Web\AccountingReportController;
use App\Http\Controllers\Web\AccountingSettingController;
use App\Http\Controllers\Web\AdminPreregistrationResetController;
use App\Http\Controllers\Web\AgencyClientController;
use App\Http\Controllers\Web\AgencyController;
use App\Http\Controllers\Web\AlertController;
use App\Http\Controllers\Web\ApiTokenController;
use App\Http\Controllers\Web\AuditLogController;
use App\Http\Controllers\Web\ConsolidationController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DeliveryController;
use App\Http\Controllers\Web\NicConsolidationController;
use App\Http\Controllers\Web\PackageController;
use App\Http\Controllers\Web\PreregistrationController;
use App\Http\Controllers\Web\ReceiptNoteController;
use App\Http\Controllers\Web\TimeEntryAdminController;
use App\Http\Controllers\Web\TimeEntryController;
use App\Http\Controllers\Web\TrackingController;
use App\Http\Controllers\Web\UserController;
use Illuminate\Support\Facades\Route;

// Público: consulta de tracking (sin autenticación)
Route::get('/tracking', [TrackingController::class, 'index'])->name('tracking.index');

require __DIR__.'/auth.php';

// Voucher de factura con enlace firmado (para envío por correo al cliente)
Route::get('/factura/{invoice}/voucher', [AccountingInvoiceController::class, 'publicVoucher'])
    ->middleware('signed')
    ->name('accounting.invoices.public-voucher');

// Panel: requiere autenticación
Route::middleware(['auth'])->group(function () {
    // Panel operativo: solo administrador. El resto se redirige a paquetes.
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::middleware('central')->group(function () {
        Route::get('/reporte-paquetes/solicitar', [DashboardController::class, 'reporteSolicitar'])->name('reporte.solicitar');
        Route::get('/reporte-paquetes', [DashboardController::class, 'reportePaquetes'])->name('reporte.paquetes');
    });

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
        Route::get('alertas', [AlertController::class, 'index'])->name('alerts.index');
        Route::post('alertas/revisar', [AlertController::class, 'dispatch'])->name('alerts.dispatch');
        Route::post('alertas/{alert}/revisada', [AlertController::class, 'dismiss'])->name('alerts.dismiss');
        Route::get('admin/time-entries', [TimeEntryAdminController::class, 'index'])->name('time-entries.admin.index');
        Route::post('preregistrations/{id}/admin/reset-to-miami', [AdminPreregistrationResetController::class, 'resetToMiami'])
            ->name('preregistrations.admin.reset-to-miami');
        Route::post('preregistrations/{id}/admin/intake-type', [AdminPreregistrationResetController::class, 'updateIntakeType'])
            ->name('preregistrations.admin.intake-type');
        Route::resource('users', UserController::class)->except(['show']);

        Route::get('api-tokens', [ApiTokenController::class, 'index'])->name('api-tokens.index');
        Route::post('api-tokens', [ApiTokenController::class, 'store'])->name('api-tokens.store');
        Route::delete('api-tokens/{tokenId}', [ApiTokenController::class, 'destroy'])->name('api-tokens.destroy');

        Route::prefix('contabilidad/facturas')->name('accounting.invoices.')->group(function () {
            Route::get('/nueva', [AccountingInvoiceController::class, 'create'])->name('create');
            Route::post('/nueva', [AccountingInvoiceController::class, 'startCreate'])->name('start-create');
            Route::get('/desde-nota/{deliveryNote}', [AccountingInvoiceController::class, 'createFromNote'])->name('create-from-note');
            Route::post('/desde-nota/{deliveryNote}', [AccountingInvoiceController::class, 'storeFromNote'])->name('store-from-note');
            Route::post('/{invoice}/anular', [AccountingInvoiceController::class, 'void'])->name('void');
            Route::post('/{invoice}/enviar', [AccountingInvoiceController::class, 'sendEmail'])->name('send');
            Route::delete('/{invoice}', [AccountingInvoiceController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('contabilidad/tarifas')->name('accounting.rates.')->group(function () {
            Route::get('/', [AccountingRateCardController::class, 'index'])->name('index');
            Route::post('/', [AccountingRateCardController::class, 'store'])->name('store');
            Route::get('/historico', [AccountingRateCardController::class, 'history'])->name('history');
            Route::get('/{agency}', [AccountingRateCardController::class, 'show'])->name('show');
        });

        Route::prefix('contabilidad/cobros')->name('accounting.payments.')->group(function () {
            Route::get('/', [AccountingPaymentController::class, 'index'])->name('index');
            Route::get('/nuevo', [AccountingPaymentController::class, 'create'])->name('create');
            Route::post('/', [AccountingPaymentController::class, 'store'])->name('store');
            Route::get('/{payment}', [AccountingPaymentController::class, 'show'])->name('show');
            Route::post('/{payment}/cancelar', [AccountingPaymentController::class, 'void'])->name('void');
        });

        Route::prefix('contabilidad/cxc')->name('accounting.receivables.')->group(function () {
            Route::get('/', [AccountingReceivableController::class, 'index'])->name('index');
            Route::get('/{agency}', [AccountingReceivableController::class, 'show'])->name('show');
        });

        Route::prefix('contabilidad/notas-credito')->name('accounting.credit-notes.')->group(function () {
            Route::get('/', [AccountingCreditNoteController::class, 'index'])->name('index');
            Route::get('/nueva', [AccountingCreditNoteController::class, 'create'])->name('create');
            Route::post('/', [AccountingCreditNoteController::class, 'store'])->name('store');
            Route::get('/{creditNote}', [AccountingCreditNoteController::class, 'show'])->name('show');
            Route::post('/{creditNote}/anular', [AccountingCreditNoteController::class, 'void'])->name('void');
        });

        Route::get('contabilidad/rentabilidad', [AccountingProfitabilityController::class, 'index'])->name('accounting.profitability.index');
        Route::get('contabilidad/rentabilidad/cliente/{agency}', [AccountingProfitabilityController::class, 'show'])->name('accounting.profitability.show');

        Route::prefix('contabilidad/gastos')->name('accounting.expenses.')->group(function () {
            Route::get('/', [AccountingExpenseController::class, 'index'])->name('index');
            Route::post('/', [AccountingExpenseController::class, 'store'])->name('store');
            Route::post('/categorias', [AccountingExpenseController::class, 'storeCategory'])->name('store-category');
            Route::delete('/{expense}', [AccountingExpenseController::class, 'destroy'])->name('destroy');
        });

        Route::get('contabilidad/reportes', [AccountingReportController::class, 'index'])->name('accounting.reports.index');

        Route::get('contabilidad/parametros', [AccountingSettingController::class, 'edit'])->name('accounting.settings.edit');
        Route::put('contabilidad/parametros', [AccountingSettingController::class, 'update'])->name('accounting.settings.update');
    });

    Route::prefix('contabilidad/facturas')->name('accounting.invoices.')->group(function () {
        Route::get('/', [AccountingInvoiceController::class, 'index'])->name('index');
        Route::get('/{invoice}/voucher', [AccountingInvoiceController::class, 'voucher'])->name('voucher');
        Route::get('/{invoice}/pdf', [AccountingInvoiceController::class, 'pdf'])->name('pdf');
        Route::get('/{invoice}', [AccountingInvoiceController::class, 'show'])->name('show');
    });

    // Usuario central: preregistros, consolidaciones, comprobantes recepción, escaneo NIC
    Route::middleware('central')->group(function () {
        Route::resource('preregistrations', PreregistrationController::class);
        Route::get('preregistrations/courier/quick', [PreregistrationController::class, 'quickCourier'])->name('preregistrations.quick-courier');
        Route::post('preregistrations/courier/quick', [PreregistrationController::class, 'storeQuickCourier'])->name('preregistrations.store-quick-courier');
        Route::get('preregistrations/{id}/label', [PreregistrationController::class, 'label'])->name('preregistrations.label');
        Route::get('preregistrations-dropoff-labels', [PreregistrationController::class, 'dropoffLabels'])->name('preregistrations.dropoff-labels');
        Route::post('preregistrations/{id}/photos', [PreregistrationController::class, 'uploadPhoto'])->name('preregistrations.upload-photo');
        Route::post('preregistrations/{id}/photos/{photo}/move', [PreregistrationController::class, 'movePhoto'])->name('preregistrations.photos.move');

        Route::post('preregistrations/{preregistration}/create-single-consolidation', [ConsolidationController::class, 'createSingleFromPreregistration'])->name('preregistrations.create-single-consolidation');
        Route::post('preregistrations/{preregistration}/quick-receipt', [ReceiptNoteController::class, 'quickFromPreregistration'])->name('preregistrations.quick-receipt');

        Route::prefix('receipt-notes')->name('receipt-notes.')->group(function () {
            Route::get('/', [ReceiptNoteController::class, 'index'])->name('index');
            Route::get('/batch', [ReceiptNoteController::class, 'batch'])->name('batch');
            Route::post('/', [ReceiptNoteController::class, 'store'])->name('store');
            Route::post('/{id}/items', [ReceiptNoteController::class, 'addItem'])->name('add-item');
            Route::delete('/{id}/items/{preregistration}', [ReceiptNoteController::class, 'removeItem'])->name('remove-item');
            Route::get('/{id}/print', [ReceiptNoteController::class, 'printReport'])->name('print');
            Route::delete('/{id}', [ReceiptNoteController::class, 'destroy'])->name('destroy');
        });
        Route::get('consolidations/create/select', [ConsolidationController::class, 'createSelect'])->name('consolidations.create-select');
        Route::get('consolidations/create/scan', [ConsolidationController::class, 'createScan'])->name('consolidations.create-scan');
        Route::post('consolidations/store-scan', [ConsolidationController::class, 'storeScan'])->name('consolidations.store-scan');
        Route::resource('consolidations', ConsolidationController::class);
        Route::get('consolidations/{id}/label', [ConsolidationController::class, 'label'])->name('consolidations.label');
        Route::get('consolidations/{id}/report', [ConsolidationController::class, 'report'])->name('consolidations.report');
        Route::post('consolidations/{id}/add-item', [ConsolidationController::class, 'addItem'])->name('consolidations.add-item');
        Route::post('consolidations/{id}/scan-item', [ConsolidationController::class, 'addItemByScan'])->name('consolidations.scan-item');
        Route::delete('consolidations/{id}/items/{item}', [ConsolidationController::class, 'removeItem'])->name('consolidations.items.destroy');
        Route::post('consolidations/{id}/send', [ConsolidationController::class, 'send'])->name('consolidations.send');

        Route::prefix('nic-consolidations')->name('nic-consolidations.')->group(function () {
            Route::get('/', [NicConsolidationController::class, 'index'])->name('index');
            Route::get('/{id}', [NicConsolidationController::class, 'show'])->name('show');
            Route::post('/{id}/scan', [NicConsolidationController::class, 'scan'])->name('scan');
        });
    });

    // Paquetes y entregas: central y subagencias (con filtro por agencia en controlador)
    Route::prefix('packages')->name('packages.')->group(function () {
        Route::get('/', [PackageController::class, 'index'])->name('index');
        Route::get('/{id}', [PackageController::class, 'show'])->name('show');
        Route::get('/{id}/process', [PackageController::class, 'showProcess'])->name('process');
        Route::post('/{id}/process', [PackageController::class, 'process'])->name('process.store');
        Route::post('/{id}/reprint-label', [PackageController::class, 'reprintLabel'])->name('reprint-label');
    });

    Route::prefix('salidas')->name('salidas.')->group(function () {
        Route::get('/', [DeliveryController::class, 'index'])->name('index');
        Route::get('/nueva', [DeliveryController::class, 'create'])->name('create');
        Route::get('/batch', [DeliveryController::class, 'batch'])->name('batch');
        Route::post('/batch/retirer-session', [DeliveryController::class, 'storeBatchRetirerSession'])->name('batch-retirer-session');
        Route::post('/batch/clear-retirer-session', [DeliveryController::class, 'clearBatchRetirerSession'])->name('batch-clear-retirer-session');
        Route::get('/print-report', [DeliveryController::class, 'printReport'])->name('print-report');
        Route::get('/scan', [DeliveryController::class, 'scan'])->name('scan');
        Route::post('/scan/retirer-session', [DeliveryController::class, 'storeScanRetirerSession'])->name('scan-retirer-session');
        Route::post('/scan/clear-retirer-session', [DeliveryController::class, 'clearScanRetirerSession'])->name('scan-clear-retirer-session');
        Route::post('/scan', [DeliveryController::class, 'processScan'])->name('process-scan');
        Route::middleware('admin')->prefix('hojas')->name('hojas.')->group(function () {
            Route::get('/{deliveryNote}', [DeliveryController::class, 'editNote'])->name('edit');
            Route::put('/{deliveryNote}', [DeliveryController::class, 'updateNote'])->name('update');
            Route::delete('/{deliveryNote}/paquetes/{delivery}', [DeliveryController::class, 'removeFromNote'])->name('remove-package');
        });
        Route::get('/{id}', [DeliveryController::class, 'show'])->name('show');
    });

    Route::redirect('/deliveries', '/salidas', 301);
    Route::get('/deliveries/{path}', function (string $path) {
        return redirect('/salidas/'.$path, 301);
    })->where('path', '.*');

    Route::middleware('central.worker')->prefix('time-entries')->name('time-entries.')->group(function () {
        Route::get('/', [TimeEntryController::class, 'index'])->name('index');
        Route::post('/clock-in', [TimeEntryController::class, 'clockIn'])->name('clock-in');
        Route::post('/clock-out', [TimeEntryController::class, 'clockOut'])->name('clock-out');
        Route::post('/break-start', [TimeEntryController::class, 'breakStart'])->name('break-start');
        Route::post('/break-end', [TimeEntryController::class, 'breakEnd'])->name('break-end');
    });
});
