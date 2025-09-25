<?php

use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\Api\ReceiptController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('products/search', [ProductApiController::class, 'search']);

Route::get('/receipt/{transaction_number}', [ReceiptController::class, 'show']);
Route::post('/receipt/draft', [ReceiptController::class, 'draftReceipt']);
Route::get('/outlet/preview', [ReceiptController::class, 'preview'])->name('outlet.preview');
