<?php

use App\Http\Controllers\CashierController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\ItemTypesController;
use App\Http\Controllers\Reports\SalesEmployeeReportController;
use App\Http\Controllers\Reports\SalesItemReportController;
use App\Http\Controllers\Reports\SalesNoteReportController;
use App\Http\Controllers\Reports\SalesSummaryReportController;
use App\Http\Controllers\Reports\StockReportController;
use App\Http\Controllers\StockOpnameController;
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
    Route::get('cashier/scan', [CashierController::class, 'scanView'])->name('cashier.scan.view');
    Route::post('cashier/open', [CashierController::class, 'open'])->name('cashier.open');
    Route::post('cashier/close', [CashierController::class, 'close'])->name('cashier.close');
    Route::post('cashier/add-cash', [CashierController::class, 'addCash'])->name('cashier.add-cash');
    Route::post('cashier/scan', [CashierController::class, 'submitScan'])->name('cashier.scan.submit');

    Route::middleware('role:super-admin')->prefix('store')->name('store.')->group(
        function () {
            Route::prefix('settings')->name('settings.')->group(
                function () {
                    Route::get('{category}', [StoreController::class, 'index'])
                        ->whereIn('category', ['gold', 'silver'])
                        ->name('index');

                    Route::patch('{category}', [StoreController::class, 'update'])
                        ->whereIn('category', ['gold', 'silver'])
                        ->name('update');
                }
            );

            Route::resource('payment-methods', PaymentMethodController::class)->except(['show', 'create', 'edit']);
            Route::resource('item-types', ItemTypesController::class)->except(['show', 'create', 'edit']);
            Route::resource('items', ItemsController::class)->except(['show', 'create', 'edit']);
            Route::get('items/print-label', [ItemsController::class, 'printLabel'])->name('items.print-label');
            Route::get('items/{item}/print-label', [ItemsController::class, 'printSingleLabel'])->name('items.print-single-label');

            Route::prefix('stock-opnames')->name('stock-opnames.')->group(
                function () {
                    Route::get('/', [StockOpnameController::class, 'index'])->name('index');
                    Route::get('/create', [StockOpnameController::class, 'create'])->name('create');
                    Route::post('/', [StockOpnameController::class, 'store'])->name('store');
                    Route::get('/{stockOpname}/edit', [StockOpnameController::class, 'edit'])->name('edit');
                    Route::patch('/{stockOpname}', [StockOpnameController::class, 'update'])->name('update');
                    Route::patch('/{stockOpname}/approve', [StockOpnameController::class, 'approve'])->name('approve');
                    Route::get('/{stockOpname}/compare', [StockOpnameController::class, 'compare'])->name('compare');
                    Route::delete('/{stockOpname}', [StockOpnameController::class, 'destroy'])->name('destroy');
                }
            );
        }
    );

    Route::middleware('cashier.open')->group(
        function () {
            Route::prefix('{category}')->whereIn('category', ['gold', 'silver'])->group(
                function () {
                    Route::prefix('debt')
                        ->name('debt.')
                        ->group(
                            function () {
                                Route::get('/', [DebtController::class, 'index'])->name('index');
                                Route::post('/settle/{sale}', [DebtController::class, 'settle'])->name('settle');
                                Route::post('/due-date/{sale}', [DebtController::class, 'setDueDate'])->name('dueDate');
                            }
                        );

                    Route::prefix('buyback')
                        ->name('buyback.')
                        ->group(
                            function () {
                                Route::get('/', [BuybackController::class, 'index'])->name('index');
                                Route::get('/manual', [BuybackController::class, 'createManual'])->name('create.manual');
                                Route::get('/{sale}', [BuybackController::class, 'create'])->whereNumber('sale')->name('create');
                                Route::post('/', [BuybackController::class, 'store'])->name('store');
                                Route::patch('/item/{buybackItem}/qc', [BuybackController::class, 'processQC'])->name('item.qc');
                                Route::get('/item/{buybackItem}/print-label', [BuybackController::class, 'printLabel'])->name('item.print-label');
                                Route::get('bulk/print-label', [BuybackController::class, 'printBulkLabel'])->name('bulk.print-label');
                            }
                        );

                    Route::prefix('damaged')
                        ->name('damaged.')
                        ->group(
                            function () {
                                Route::get('/', [DamagedController::class, 'index'])->name('index');
                                Route::patch('/{item}/restore', [DamagedController::class, 'restoreToStock'])->name('restore');
                            }
                        );

                    Route::prefix('sale')
                        ->name('sales.')
                        ->group(
                            function () {
                                Route::get('/', [SaleController::class, 'index'])->name('index');
                                Route::get('/retail/create', [SaleController::class, 'createRetail'])
                                    ->name('create.retail');

                                Route::get('/wholesale/create', [SaleController::class, 'createWholesale'])
                                    ->name('create.wholesale');
                                Route::post('/', [SaleController::class, 'store'])->name('store');
                                Route::post('/initiate', [SaleController::class, 'initiate'])->name('initiate');
                                Route::post('/{sale}/addItem', [SaleController::class, 'addItem'])->name('addItem');
                                Route::delete('/{sale}/removeItem', [SaleController::class, 'removeItem'])->name('removeItem');
                                Route::get('/{sale}/print', [SaleController::class, 'print'])->name('print');
                                Route::get('/{sale}/edit', [SaleController::class, 'edit'])->name('edit');
                                Route::patch('/{sale}', [SaleController::class, 'update'])->name('update');
                                Route::delete('/{sale}', [SaleController::class, 'destroy'])->name('destroy');
                            }
                        );
                }
            );
        }
    );

    Route::middleware('role:super-admin')->name('reports.')->prefix('reports')->group(
        function () {
            Route::get('sales/summary/{category}', [SalesSummaryReportController::class, 'index'])->name('sales.summary');
            Route::get('sales/note', [SalesNoteReportController::class, 'index'])->name('sales.note');
            Route::get('sales/item', [SalesItemReportController::class, 'index'])->name('sales.item');
            Route::get('sales/item/gold/retail', [SalesItemReportController::class, 'retail'])->name('sales.item.gold.retail');
            Route::get('sales/item/gold/wholesale', [SalesItemReportController::class, 'wholesale'])->name('sales.item.gold.wholesale');
            Route::get('sales/item/silver/retail', [SalesItemReportController::class, 'retail'])->name('sales.item.silver.retail');
            Route::get('sales/item/silver/wholesale', [SalesItemReportController::class, 'wholesale'])->name('sales.item.silver.wholesale');
            Route::get('sales/employee', [SalesEmployeeReportController::class, 'index'])->name('sales.employee');
            Route::get('stock', [StockReportController::class, 'index'])->name('stock.index');
        }
    );

    Route::middleware('role:super-admin|admin gold|admin silver')->prefix('master')->name('master.')->group(
        function () {
            Route::resource('users', UsersController::class);
            Route::resource('menus', MenusController::class);
            Route::resource('roles', RolesController::class);
        }
    );
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
