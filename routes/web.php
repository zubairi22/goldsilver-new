<?php

use App\Http\Controllers\CashierController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebtsController;
use App\Http\Controllers\Master\MenusController;
use App\Http\Controllers\Master\RolesController;
use App\Http\Controllers\Master\UsersController;
use App\Http\Controllers\Outlet\FinancialAccountController;
use App\Http\Controllers\Outlet\OutletController;
use App\Http\Controllers\Outlet\PaymentMethodController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\PurchasesController;
use App\Http\Controllers\RefundsController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SuppliersController;
use App\Http\Controllers\UnitsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::middleware('auth')->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::name('outlet.')->prefix('outlet')->group(function () {
        Route::get('settings', [OutletController::class, 'index'])->name('settings.index');
        Route::patch('settings', [OutletController::class, 'update'])->name('settings.update');
        Route::resource('financial-accounts', FinancialAccountController::class)->except(['show', 'create', 'edit']);
        Route::resource('payment-methods', PaymentMethodController::class)->except(['show', 'create', 'edit']);

        Route::resource('units', UnitsController::class)->except(['show', 'create', 'edit']);
        Route::resource('categories', CategoriesController::class)->except(['show', 'create', 'edit']);
        Route::resource('products', ProductsController::class)->except(['show', 'create', 'edit']);
        Route::resource('customers', CustomersController::class)->except(['show', 'create', 'edit']);
        Route::get('customer/{customer}/point', [CustomersController::class, 'point'])->name('customer.point');
        Route::post('customer/{customer}/point/redeem', [CustomersController::class, 'redeemPoint'])->name('customer.point.redeem');
        Route::get('customer/{customer}/deposit', [CustomersController::class, 'deposit'])->name('customer.deposit');
        Route::post('customer/{customer}/deposit', [CustomersController::class, 'storeDeposit'])->name('customer.deposit.store');
        Route::post('customer/{customer}/deposit/refund', [CustomersController::class, 'storeRefund'])->name('customer.deposit.refund');

        Route::resource('suppliers', SuppliersController::class)->except(['show']);

        Route::resource('purchases', PurchasesController::class)->except(['show']);
        Route::patch('purchases/receive/{purchase}', [PurchasesController::class, 'receive'])->name('purchases.receive');
    });

    Route::resource('cashier', CashierController::class)->except(['show', 'create', 'edit']);

    Route::name('transaction.')->prefix('transaction')->group(function () {
        Route::resource('sales', SalesController::class)->except(['show', 'create', 'edit']);
        Route::resource('refunds', RefundsController::class)->except(['show', 'create', 'edit', 'store']);
        Route::post('refund/{transaction}', [RefundsController::class, 'store'])->name('refunds.store');
        Route::resource('debts', DebtsController::class)->except(['show', 'create', 'edit']);
        Route::post('debt/{customer}/settlement', [DebtsController::class, 'settleDebt'])->name('debt.settle');
        Route::post('debt/{transaction}/cancel-item', [DebtsController::class, 'cancelDebtItem'])->name('debt.cancel.item');
        Route::post('debt/{transaction}/generate-invoice', [DebtsController::class, 'generateInvoice'])->name('debt.invoice.generate');
        Route::get('debt/{transaction}/view-invoice', [DebtsController::class, 'viewInvoice'])->name('debt.invoice.view');
    });

    Route::middleware('role:super-admin')->name('master.')->prefix('master')->group(function () {
        Route::resource('users', UsersController::class);
        Route::resource('menus', MenusController::class);
        Route::resource('roles', RolesController::class);
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
