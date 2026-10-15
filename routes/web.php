
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

/*
|--------------------------------------------------------------------------
| Default Route
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => redirect()->route('fuel-dashboard'));

/*
|--------------------------------------------------------------------------
| Guest Authentication Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {

    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:login');

});

/*
|--------------------------------------------------------------------------
| Logout Route
|--------------------------------------------------------------------------
*/

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Authenticated Application Routes
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'account.active',
    'idle.timeout'
])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Session Heartbeat
    |--------------------------------------------------------------------------
    */

    Route::post('/ping', fn () => response()->noContent())
        ->name('ping');

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/fuel-dashboard',
        [FuelDashboardController::class, 'index']
    )->name('fuel-dashboard');

    /*
    |--------------------------------------------------------------------------
    | Supplier Sales Orders
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'fuel-sales-orders',
        FuelSalesOrderController::class
    )->except(['show', 'destroy']);

    Route::delete(
        '/fuel-sales-orders/{fuelSalesOrder}',
        [FuelSalesOrderController::class, 'destroy']
    )
        ->name('fuel-sales-orders.destroy')
        ->middleware('role:superadmin,admin');

    /*
    |--------------------------------------------------------------------------
    | Supplier Payments
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/fuel-payments',
        [FuelPaymentController::class, 'index']
    )->name('fuel-payments.index');

    /*
    | Supplier Payment Proof Image Preview
    | Used by Supplier Orders and Supplier Payments.
    */

    Route::get(
        '/fuel-payments/{fuelPayment}/proof',
        [FuelPaymentController::class, 'showProof']
    )->name('fuel-payments.proof');

    /*
    | Store Supplier Payment
    */

    Route::post(
        '/fuel-sales-orders/{fuelSalesOrder}/payments',
        [FuelPaymentController::class, 'store']
    )->name('fuel-sales-orders.payments.store');

    /*
    | Delete Supplier Payment
    | Admin and Superadmin only.
    */

    Route::delete(
        '/fuel-payments/{fuelPayment}',
        [FuelPaymentController::class, 'destroy']
    )
        ->name('fuel-payments.destroy')
        ->middleware('role:superadmin,admin');

    /*
    |--------------------------------------------------------------------------
    | Customer Purchase Reports
    |--------------------------------------------------------------------------
    |
    | Register specific report paths before resource routes.
    */

    Route::get(
        '/fuel-customer-purchases/print-summary-report',
        FuelCustomerPurchaseSummaryReportController::class
    )->name('fuel-customer-purchases.print-summary-report');

    /*
    |--------------------------------------------------------------------------
    | Customer Purchases
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'fuel-customer-purchases',
        FuelCustomerPurchaseController::class
    )->except(['show', 'destroy']);

    Route::delete(
        '/fuel-customer-purchases/{fuelCustomerPurchase}',
        [FuelCustomerPurchaseController::class, 'destroy']
    )
        ->name('fuel-customer-purchases.destroy')
        ->middleware('role:superadmin,admin');

    /*
    |--------------------------------------------------------------------------
    | Customer Payments
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/fuel-customer-payments',
        [FuelCustomerPaymentController::class, 'index']
    )->name('fuel-customer-payments.index');

    /*
    | Customer Payment Proof Image Preview
    | Used by Customer Purchases and Customer Payments.
    */

    Route::get(
        '/fuel-customer-payments/{fuelCustomerPayment}/proof',
        [FuelCustomerPaymentController::class, 'showProof']
    )->name('fuel-customer-payments.proof');

    /*
    | Store Customer Payment
    */

    Route::post(
        '/fuel-customer-purchases/{fuelCustomerPurchase}/payments',
        [FuelCustomerPaymentController::class, 'store']
    )->name('fuel-customer-purchases.payments.store');

    /*
    | Delete Customer Payment
    | Admin and Superadmin only.
    */

    Route::delete(
        '/fuel-customer-payments/{fuelCustomerPayment}',
        [FuelCustomerPaymentController::class, 'destroy']
    )
        ->name('fuel-customer-payments.destroy')
        ->middleware('role:superadmin,admin');

    /*
    |--------------------------------------------------------------------------
    | Customer Net Income
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/fuel-customer-net-income',
        [FuelCustomerNetIncomePrintController::class, 'index']
    )->name('fuel-customer-net-income.index');

    Route::get(
        '/fuel-customer-net-income/print-summary',
        [FuelCustomerNetIncomePrintController::class, 'summary']
    )->name('fuel-customer-net-income.print-summary');

    /*
    |--------------------------------------------------------------------------
    | Tanker Records
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/fuel-tanker-records',
        [FuelTankerRecordController::class, 'index']
    )->name('fuel-tanker-records.index');

    Route::get(
        '/fuel-tanker-records/print-summary',
        [FuelTankerSummaryController::class, 'print']
    )->name('fuel-tanker-records.print-summary');

    Route::get(
        '/fuel-tanker-records/{fuelTankerRecord}/edit',
        [FuelTankerRecordController::class, 'edit']
    )->name('fuel-tanker-records.edit');

    Route::put(
        '/fuel-tanker-records/{fuelTankerRecord}',
        [FuelTankerRecordController::class, 'update']
    )->name('fuel-tanker-records.update');

    /*
    |--------------------------------------------------------------------------
    | Supplier SOA Print
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/fuel-payments/soa/{salesOrder}/print',
        FuelSoaPrintController::class
    )->name('fuel-payments.soa.print');

    /*
    |--------------------------------------------------------------------------
    | Customer SOA Print
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/fuel-customer-purchases/{record}/soa',
        function (FuelCustomerPurchase $record) {

            $record->loadMissing([
                'items',
                'payments',
                'salesOrder'
            ]);

            return view('fuel-customer-purchases.soa', [
                'purchase' => $record,
            ]);

        }
    )->name('fuel-customer-purchases.soa');

    /*
    |--------------------------------------------------------------------------
    | User Management
    |--------------------------------------------------------------------------
    |
    | Superadmin only.
    */

    Route::resource(
        'users',
        UserController::class
    )->middleware('role:superadmin');

    /*
    |--------------------------------------------------------------------------
    | Audit Logs
    |--------------------------------------------------------------------------
    |
    | Superadmin only.
    */

    Route::get(
        '/audit-logs',
        [AuditLogController::class, 'index']
    )
        ->name('audit-logs.index')
        ->middleware('role:superadmin');

});
