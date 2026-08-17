<?php
/*
|--------------------------------------------------------------------------
| Add these to your existing routes/web.php
|--------------------------------------------------------------------------
| Keep your existing print/report/SOA routes as they are — just add the
| block below (and the new use statements at the top of the file).
*/

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FuelCustomerPaymentController;
use App\Http\Controllers\FuelCustomerPurchaseController;
use App\Http\Controllers\FuelDashboardController;
use App\Http\Controllers\FuelPaymentController;
use App\Http\Controllers\FuelSalesOrderController;
use App\Http\Controllers\FuelTankerRecordController;

// Replace the existing `Route::get('/', ...)` redirect-to-Filament with this,
// or leave both and just point people to /fuel-dashboard instead.
Route::get('/', fn () => redirect()->route('fuel-dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {

    Route::get('/fuel-dashboard', [FuelDashboardController::class, 'index'])->name('fuel-dashboard');

    // Supplier sales orders + their payments
    Route::resource('fuel-sales-orders', FuelSalesOrderController::class)
        ->except(['show']);
    Route::post('/fuel-sales-orders/{fuelSalesOrder}/payments', [FuelPaymentController::class, 'store'])
        ->name('fuel-sales-orders.payments.store');
    Route::delete('/fuel-payments/{fuelPayment}', [FuelPaymentController::class, 'destroy'])
        ->name('fuel-payments.destroy');

    // Customer purchases + their payments + their tanker record
    Route::resource('fuel-customer-purchases', FuelCustomerPurchaseController::class)
        ->except(['show']);
    Route::post('/fuel-customer-purchases/{fuelCustomerPurchase}/payments', [FuelCustomerPaymentController::class, 'store'])
        ->name('fuel-customer-purchases.payments.store');
    Route::delete('/fuel-customer-payments/{fuelCustomerPayment}', [FuelCustomerPaymentController::class, 'destroy'])
        ->name('fuel-customer-payments.destroy');

    Route::put('/fuel-tanker-records/{fuelTankerRecord}', [FuelTankerRecordController::class, 'update'])
        ->name('fuel-tanker-records.update');
});
