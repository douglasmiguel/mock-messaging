<?php

use App\Http\Controllers\Api\V1\InternalOrderController;
use App\Http\Controllers\Api\V1\StoreOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/order', StoreOrderController::class)->name('api.v1.order.store');
    Route::post('/orders', StoreOrderController::class)->name('api.v1.orders.store');
});

Route::prefix('v1/internal/orders/{order}')->group(function (): void {
    Route::get('/', [InternalOrderController::class, 'show']);
    Route::post('/restaurant/confirm', [InternalOrderController::class, 'restaurantConfirm']);
    Route::post('/restaurant/refuse', [InternalOrderController::class, 'restaurantRefuse']);
    Route::post('/restaurant/ready', [InternalOrderController::class, 'restaurantReady']);
    Route::post('/client/cancel', [InternalOrderController::class, 'clientCancel']);
    Route::post('/rider-assignment', [InternalOrderController::class, 'assignRider']);
});
