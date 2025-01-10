<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CustomerPaymentController;
use App\Http\Controllers\API\CustomerPaymentDummyController;
use App\Http\Controllers\API\StockMovementController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->post('/customer-payment/store', [CustomerPaymentController::class, 'store']);
Route::middleware('auth:sanctum')->post('/stock-movements/store', [StockMovementController::class, 'store']);


// DUMMY
Route::middleware('auth:sanctum')->post('/customer-payment-dummy/store', [CustomerPaymentDummyController::class, 'store']);
