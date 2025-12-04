<?php

use App\Http\Controllers\CashierController;
use App\Http\Controllers\GoldBuybackController;
use App\Http\Controllers\GoldDamagedController;
use App\Http\Controllers\ItemTypesController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoldSaleController;
use App\Http\Controllers\Master\MenusController;
use App\Http\Controllers\Master\RolesController;
use App\Http\Controllers\Master\UsersController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\PurchasesController;
use App\Http\Controllers\Store\PaymentMethodController;
use App\Http\Controllers\Store\StoreController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::middleware('auth')->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('role:super-admin')->name('store.')->prefix('store')->group(function () {
        Route::get('settings', [StoreController::class, 'index'])->name('settings.index');
        Route::patch('settings', [StoreController::class, 'update'])->name('settings.update');
        Route::resource('payment-methods', PaymentMethodController::class)->except(['show', 'create', 'edit']);

        Route::resource('item-types', ItemTypesController::class)->except(['show', 'create', 'edit']);
        Route::resource('items', ItemsController::class)->except(['show', 'create', 'edit']);

        Route::resource('purchases', PurchasesController::class)->except(['show']);
        Route::patch('purchases/receive/{purchase}', [PurchasesController::class, 'receive'])->name('purchases.receive');
    });

    Route::middleware('role:super-admin')->name('outlet.')->prefix('outlet')->group(function () {
        Route::resource('customers', CustomersController::class)->except(['show', 'create', 'edit']);
        Route::get('customer/{customer}/point', [CustomersController::class, 'point'])->name('customer.point');
        Route::post('customer/{customer}/point/redeem', [CustomersController::class, 'redeemPoint'])->name('customer.point.redeem');
        Route::get('customer/{customer}/deposit', [CustomersController::class, 'deposit'])->name('customer.deposit');
        Route::post('customer/{customer}/deposit', [CustomersController::class, 'storeDeposit'])->name('customer.deposit.store');
        Route::post('customer/{customer}/deposit/refund', [CustomersController::class, 'storeRefund'])->name('customer.deposit.refund');
    });

    Route::resource('cashier', CashierController::class)->except(['show', 'create', 'edit']);

    Route::name('transactions.sales.')->prefix('transactions/sales')->group(function () {
        Route::get('gold', [GoldSaleController::class, 'index'])->name('gold.index');
        Route::get('gold/create', [GoldSaleController::class, 'create'])->name('gold.create');
        Route::post('gold', [GoldSaleController::class, 'store'])->name('gold.store');
    });

    Route::name('buyback.')->prefix('buyback')->group(function () {
        Route::get('gold', [GoldBuybackController::class, 'index'])->name('gold.index');
        Route::get('gold/create/{sale}', [GoldBuybackController::class, 'create'])->name('gold.create');
        Route::post('gold/store', [GoldBuybackController::class, 'store'])->name('gold.store');
        Route::patch('gold/item/{buybackItem}/qc', [GoldBuybackController::class, 'processQC'])->name('gold.item.qc');
    });

    Route::name('damaged.')->prefix('damaged')->group(function () {
        Route::get('gold', [GoldDamagedController::class, 'index'])->name('gold.index');
        Route::patch('gold/{item}/restore', [GoldDamagedController::class, 'restoreToStock'])->name('gold.restore');
    });

    Route::middleware('role:super-admin')->name('master.')->prefix('master')->group(function () {
        Route::resource('users', UsersController::class);
        Route::resource('menus', MenusController::class);
        Route::resource('roles', RolesController::class);
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
