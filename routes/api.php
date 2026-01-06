<?php

use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::apiResource('orders', OrderController::class)->parameter('orders', 'uuid');
});
