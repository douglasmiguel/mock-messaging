<?php

use App\Http\Controllers\ClientOrderController;
use App\Http\Controllers\RestaurantOrderController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home');

Route::prefix('restaurant-orders/{token}')->middleware('throttle:order-actions')->name('restaurant-orders.')->group(function (): void {
    Route::get('/', [RestaurantOrderController::class, 'show'])->name('show');
    Route::post('/confirm', [RestaurantOrderController::class, 'confirm'])->name('confirm');
    Route::post('/refuse', [RestaurantOrderController::class, 'refuse'])->name('refuse');
    Route::post('/ready', [RestaurantOrderController::class, 'ready'])->name('ready');
});

Route::prefix('client-orders/{token}')->middleware('throttle:order-actions')->name('client-orders.')->group(function (): void {
    Route::get('/', [ClientOrderController::class, 'show'])->name('show');
    Route::post('/cancel', [ClientOrderController::class, 'cancel'])->name('cancel');
    Route::post('/issues', [ClientOrderController::class, 'issue'])->name('issues');
});
