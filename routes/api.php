<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::apiResource('orders', OrderController::class)->parameter('orders', 'uuid');
    Route::post('orders/status/{uuid}', [OrderController::class, 'status']);

    Route::post('payment/charge', [PaymentController::class, 'charge']);
});

Route::prefix('payment')->group(function () {
    Route::get('/success', [PaymentController::class, 'success'])->name('payment.success');
    Route::get('/cancel', [PaymentController::class, 'cancel'])->name('payment.cancel');

    // Webhook endpoint
    Route::post('/{gateway}', [PaymentController::class, 'webhook']);
});
