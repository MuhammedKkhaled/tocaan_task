<?php

use Illuminate\Support\Facades\Route;
use Modules\Order\Http\Controllers\OrderController;

Route::middleware(['auth:api', 'throttle:api'])->group(function (): void {
    Route::patch('orders/{order}/status', [OrderController::class, 'changeStatus'])
        ->name('orders.status.update');
    Route::apiResource('orders', OrderController::class);
});
