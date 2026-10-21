<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\PaymentController;

Route::middleware(['auth:api', 'throttle:api'])->group(function (): void {
    Route::get('orders/{order}/payments', [PaymentController::class, 'forOrder'])
        ->name('orders.payments.index');
    Route::post('orders/{order}/payments', [PaymentController::class, 'process'])
        ->name('orders.payments.store');
    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
});
