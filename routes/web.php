<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FuelCustomerNetIncomePrintController;
use App\Http\Controllers\FuelCustomerPaymentController;
use App\Http\Controllers\FuelCustomerPurchaseController;
use App\Http\Controllers\FuelCustomerPurchaseSummaryReportController;
use App\Http\Controllers\FuelDashboardController;
use App\Http\Controllers\FuelPaymentController;
use App\Http\Controllers\FuelSalesOrderController;
use App\Http\Controllers\FuelSoaPrintController;
use App\Http\Controllers\FuelTankerRecordController;
use App\Http\Controllers\FuelTankerSummaryController;
use App\Http\Controllers\UserController;
use App\Models\FuelCustomerPurchase;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('fuel-dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'account.active', 'idle.timeout'])->group(function () {

    // Lightweight heartbeat the idle-timeout JS pings while the user is
    // actively interacting with the page, to keep the server-side idle
    // clock (see AutoLogoutOnInactivity) in sync without a full reload.
    Route::post('/ping', fn () => response()->noContent())->name('ping');

    Route::get('/fuel-dashboard', [FuelDashboardController::class, 'index'])->name('fuel-dashboard');

    // Supplier sales orders + their payments.
    // Delete is separated out from the resource group and restricted to
    // admin/superadmin — staff can create and edit records but can't
    // erase financial history.
    Route::resource('fuel-sales-orders', FuelSalesOrderController::class)
        ->except(['show', 'destroy']);
    Route::delete('/fuel-sales-orders/{fuelSalesOrder}', [FuelSalesOrderController::class, 'destroy'])
        ->name('fuel-sales-orders.destroy')
        ->middleware('role:superadmin,admin');

    Route::get('/fuel-payments', [FuelPaymentController::class, 'index'])
        ->name('fuel-payments.index');
    Route::post('/fuel-sales-orders/{fuelSalesOrder}/payments', [FuelPaymentController::class, 'store'])
        ->name('fuel-sales-orders.payments.store');
    Route::delete('/fuel-payments/{fuelPayment}', [FuelPaymentController::class, 'destroy'])
        ->name('fuel-payments.destroy')
        ->middleware('role:superadmin,admin');

    // Customer purchases + their payments + their tanker record
    Route::resource('fuel-customer-purchases', FuelCustomerPurchaseController::class)
        ->except(['show', 'destroy']);
    Route::delete('/fuel-customer-purchases/{fuelCustomerPurchase}', [FuelCustomerPurchaseController::class, 'destroy'])
        ->name('fuel-customer-purchases.destroy')
        ->middleware('role:superadmin,admin');

    Route::get('/fuel-customer-payments', [FuelCustomerPaymentController::class, 'index'])
        ->name('fuel-customer-payments.index');
    Route::post('/fuel-customer-purchases/{fuelCustomerPurchase}/payments', [FuelCustomerPaymentController::class, 'store'])
        ->name('fuel-customer-purchases.payments.store');
    Route::delete('/fuel-customer-payments/{fuelCustomerPayment}', [FuelCustomerPaymentController::class, 'destroy'])
        ->name('fuel-customer-payments.destroy')
        ->middleware('role:superadmin,admin');

    Route::get('/fuel-customer-net-income', [FuelCustomerNetIncomePrintController::class, 'index'])
        ->name('fuel-customer-net-income.index');

    Route::get('/fuel-tanker-records', [FuelTankerRecordController::class, 'index'])
        ->name('fuel-tanker-records.index');
    Route::get('/fuel-tanker-records/{fuelTankerRecord}/edit', [FuelTankerRecordController::class, 'edit'])
        ->name('fuel-tanker-records.edit');
    Route::put('/fuel-tanker-records/{fuelTankerRecord}', [FuelTankerRecordController::class, 'update'])
        ->name('fuel-tanker-records.update');

    // SOA / print / summary reports (ported from edzelborapp)
    Route::get('/fuel-payments/soa/{salesOrder}/print', FuelSoaPrintController::class)
        ->name('fuel-payments.soa.print');

    Route::get('/fuel-customer-purchases/{record}/soa', function (FuelCustomerPurchase $record) {
        $record->loadMissing(['items', 'payments', 'salesOrder']);

        return view('fuel-customer-purchases.soa', [
            'purchase' => $record,
        ]);
    })->name('fuel-customer-purchases.soa');

    Route::get(
        '/fuel-customer-purchases/print-summary-report',
        FuelCustomerPurchaseSummaryReportController::class
    )->name('fuel-customer-purchases.print-summary-report');

    Route::get(
        '/fuel-customer-net-income/print-summary',
        [FuelCustomerNetIncomePrintController::class, 'summary']
    )->name('fuel-customer-net-income.print-summary');

    Route::get(
        '/fuel-tanker-records/print-summary',
        [FuelTankerSummaryController::class, 'print']
    )->name('fuel-tanker-records.print-summary');

    // User management — superadmin only. Every action is additionally
    // gated by UserPolicy inside UserController itself (self-delete /
    // last-superadmin protection, role whitelist, etc.), so this
    // middleware is defence-in-depth, not the only check.
    Route::resource('users', UserController::class)
        ->middleware('role:superadmin');

    // Audit trail — superadmin only, read-only. Entries are written
    // automatically by App\Models\Concerns\Auditable (create/update/
    // delete/restore on the audited models) and by AuthController
    // (login/logout/failed-login).
    Route::get('/audit-logs', [AuditLogController::class, 'index'])
        ->name('audit-logs.index')
        ->middleware('role:superadmin');
});
