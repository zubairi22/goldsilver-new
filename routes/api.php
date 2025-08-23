<?php

use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\Api\ReceiptController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/receipt/{transaction_number}', [ReceiptController::class, 'show']);
Route::get('products/search', [ProductApiController::class, 'search']);
