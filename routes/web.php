<?php

use App\Http\Controllers\CustomersController;
use App\Http\Controllers\DebtsController;
use App\Http\Controllers\Master\MenusController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\Master\RolesController;
use App\Http\Controllers\Master\UsersController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\TransactionsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::resource('products', ProductsController::class)->except(['show', 'create', 'edit']);
    Route::resource('transactions', TransactionsController::class)->except(['show', 'create', 'edit']);
    Route::resource('sales', SalesController::class)->except(['show', 'create', 'edit']);
    Route::resource('debts', DebtsController::class)->except(['show', 'create', 'edit']);
    Route::resource('customers', CustomersController::class)->except(['show', 'create', 'edit']);


    Route::middleware(['role:super-admin'])->name('master.')->prefix('master')->group(function () {
        Route::resource('users', UsersController::class);
        Route::resource('menus', MenusController::class);
        Route::resource('roles', RolesController::class);
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
