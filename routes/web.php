<?php

use App\Http\Controllers\CashierController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\ItemTypesController;
use App\Http\Controllers\Store\PaymentMethodController;
use App\Http\Controllers\Store\StoreController;
use App\Http\Controllers\Master\MenusController;
use App\Http\Controllers\Master\RolesController;
use App\Http\Controllers\Master\UsersController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\BuybackController;
use App\Http\Controllers\DamagedController;
use App\Http\Controllers\DebtController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::middleware('auth')->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('cashier', [CashierController::class, 'index'])->name('cashier.index');
    Route::post('cashier/open', [CashierController::class, 'open'])->name('cashier.open');
    Route::post('cashier/close', [CashierController::class, 'close'])->name('cashier.close');
    Route::post('cashier/scan', [CashierController::class, 'scan'])->name('cashier.scan');

    Route::middleware('role:super-admin')->prefix('store')->name('store.')->group(function () {
        Route::get('settings', [StoreController::class, 'index'])->name('settings.index');
        Route::patch('settings', [StoreController::class, 'update'])->name('settings.update');

        Route::resource('payment-methods', PaymentMethodController::class)->except(['show', 'create', 'edit']);
        Route::resource('item-types', ItemTypesController::class)->except(['show', 'create', 'edit']);
        Route::resource('items', ItemsController::class)->except(['show', 'create', 'edit']);
        Route::resource('customers', CustomersController::class)->except(['show', 'create', 'edit']);
    });

    Route::middleware('cashier.open')->group(function () {
        // Grup untuk kategori 'gold'
        Route::prefix('gold')->group(function () {

            Route::prefix('debt')
                ->name('gold.debt.')
                ->group(function () {
                    Route::get('/', [DebtController::class, 'index'])->name('index');
                    Route::post('/settle/{sale}', [DebtController::class, 'settle'])->name('settle');
                    Route::post('/due-date/{sale}', [DebtController::class, 'setDueDate'])->name('dueDate');
                });

            Route::prefix('buyback')
                ->name('gold.buyback.')
                ->group(function () {
                    Route::get('/', [BuybackController::class, 'index'])->name('index');
                    Route::get('/{sale}', [BuybackController::class, 'create'])->name('create');
                    Route::post('/', [BuybackController::class, 'store'])->name('store');
                    Route::patch('/item/{buybackItem}/qc', [BuybackController::class, 'processQC'])->name('item.qc');
                });

            Route::prefix('damaged')
                ->name('gold.damaged.')
                ->group(function () {
                    Route::get('/', [DamagedController::class, 'index'])->name('index');
                    Route::patch('/{item}/restore', [DamagedController::class, 'restoreToStock'])->name('restore');
                });

            Route::prefix('sale')
                ->name('gold.sales.')
                ->group(function () {
                    Route::get('/', [SaleController::class, 'index'])->name('index');
                    Route::get('/create', [SaleController::class, 'create'])->name('create');
                    Route::post('/', [SaleController::class, 'store'])->name('store');
                    Route::get('/{sale}/print', [SaleController::class, 'print'])->name('print');
                });
        });

        Route::prefix('silver')->group(function () {

            Route::prefix('debt')
                ->name('silver.debt.')
                ->group(function () {
                    Route::get('/', [DebtController::class, 'index'])->name('index');
                    Route::post('/settle/{sale}', [DebtController::class, 'settle'])->name('settle');
                    Route::post('/due-date/{sale}', [DebtController::class, 'setDueDate'])->name('dueDate');
                });

            Route::prefix('buyback')
                ->name('silver.buyback.')
                ->group(function () {
                    Route::get('/', [BuybackController::class, 'index'])->name('index');
                    Route::get('/{sale}', [BuybackController::class, 'create'])->name('create');
                    Route::post('/', [BuybackController::class, 'store'])->name('store');
                    Route::patch('/item/{buybackItem}/qc', [BuybackController::class, 'processQC'])->name('item.qc');
                });

            Route::prefix('damaged')
                ->name('silver.damaged.')
                ->group(function () {
                    Route::get('/', [DamagedController::class, 'index'])->name('index');
                    Route::patch('/{item}/restore', [DamagedController::class, 'restoreToStock'])->name('restore');
                });

            Route::prefix('sale')
                ->name('silver.sales.')
                ->group(function () {
                    Route::get('/', [SaleController::class, 'index'])->name('index');
                    Route::get('/create', [SaleController::class, 'create'])->name('create');
                    Route::post('/', [SaleController::class, 'store'])->name('store');
                    Route::get('/{sale}/print', [SaleController::class, 'print'])->name('print');
                });
        });
    });


    Route::middleware('role:super-admin')->prefix('master')->name('master.')->group(function () {
        Route::resource('users', UsersController::class);
        Route::resource('menus', MenusController::class);
        Route::resource('roles', RolesController::class);
    });
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
