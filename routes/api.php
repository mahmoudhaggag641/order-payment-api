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

    Route::get('payments', [PaymentController::class, 'index']);
    Route::post('payment/charge', [PaymentController::class, 'charge']);
});

Route::prefix('payment')->group(function () {
    Route::get('/redirect/{status}/{id?}', [PaymentController::class, 'redirect'])->name('payment.redirect');

    // Webhook endpoint
    Route::post('/{gateway}/webhook', [PaymentController::class, 'webhook']);
});
