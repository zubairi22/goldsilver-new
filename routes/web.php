<?php

use App\Http\Controllers\CashierController;
use App\Http\Controllers\GoldDebtController;
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
use App\Http\Controllers\Store\PaymentMethodController;
use App\Http\Controllers\Store\StoreController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::middleware('auth')->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('/cashier', [CashierController::class, 'index'])->name('cashier.index');
    Route::post('/cashier/open', [CashierController::class, 'open'])->name('cashier.open');
    Route::post('/cashier/close', [CashierController::class, 'close'])->name('cashier.close');
    Route::post('/cashier/scan', [CashierController::class, 'scan'])->name('cashier.scan');

    Route::middleware('role:super-admin')->name('store.')->prefix('store')->group(function () {
        Route::get('settings', [StoreController::class, 'index'])->name('settings.index');
        Route::patch('settings', [StoreController::class, 'update'])->name('settings.update');
        Route::resource('payment-methods', PaymentMethodController::class)->except(['show', 'create', 'edit']);

        Route::resource('item-types', ItemTypesController::class)->except(['show', 'create', 'edit']);
        Route::resource('items', ItemsController::class)->except(['show', 'create', 'edit']);

        Route::resource('customers', CustomersController::class)->except(['show', 'create', 'edit']);
    });

    Route::middleware('cashier.open')->group(function () {
        Route::name('gold.')->group(function () {
            Route::name('transactions.sales.')->prefix('transactions/sales')->group(function () {
                Route::get('gold', [GoldSaleController::class, 'index'])->name('index');
                Route::get('gold/create', [GoldSaleController::class, 'create'])->name('create');
                Route::post('gold', [GoldSaleController::class, 'store'])->name('store');

                Route::get('gold/{sale}/print', [GoldSaleController::class, 'print'])->name('print');
            });

            Route::name('buyback.')->prefix('buyback')->group(function () {
                Route::get('gold', [GoldBuybackController::class, 'index'])->name('index');
                Route::get('gold/create/{sale}', [GoldBuybackController::class, 'create'])->name('create');
                Route::post('gold/store', [GoldBuybackController::class, 'store'])->name('store');
                Route::patch('gold/item/{buybackItem}/qc', [GoldBuybackController::class, 'processQC'])->name('item.qc');
            });

            Route::name('damaged.')->prefix('damaged')->group(function () {
                Route::get('gold', [GoldDamagedController::class, 'index'])->name('index');
                Route::patch('gold/{item}/restore', [GoldDamagedController::class, 'restoreToStock'])->name('restore');
            });

            Route::name('transactions.debts.')->prefix('transactions/debts')->group(function () {
                Route::get('gold', [GoldDebtController::class, 'index'])->name('index');
                Route::post('gold/settle/{sale}', [GoldDebtController::class, 'settleDebt'])->name('settle');
                Route::post('gold/due-date/{sale}', [GoldDebtController::class, 'setDueDate'])->name('dueDate');
            });

        });
    });

    Route::middleware('role:super-admin')->name('master.')->prefix('master')->group(function () {
        Route::resource('users', UsersController::class);
        Route::resource('menus', MenusController::class);
        Route::resource('roles', RolesController::class);
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
