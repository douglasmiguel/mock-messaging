<?php

use App\Http\Controllers\Admin\AuthenticatedSessionController;
use App\Http\Controllers\Admin\OrderIndexController;
use App\Http\Controllers\Metrics\OrderBusinessMetricsController;
use App\Http\Controllers\TestOrder\GenerateTestOrderController;
use App\Http\Controllers\TestOrder\HomeController;
use App\Http\Controllers\TestOrder\TestOrderFlowController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/metrics/business', OrderBusinessMetricsController::class)->name('metrics.business');
Route::post('/test-orders', GenerateTestOrderController::class)->name('test-orders.store');
Route::prefix('test-orders/{order}')->name('test-orders.')->group(function (): void {
    Route::post('/confirm', [TestOrderFlowController::class, 'confirm'])->name('confirm');
    Route::post('/ready', [TestOrderFlowController::class, 'ready'])->name('ready');
    Route::post('/pick-up', [TestOrderFlowController::class, 'pickUp'])->name('pick-up');
    Route::post('/deliver', [TestOrderFlowController::class, 'deliver'])->name('deliver');
    Route::post('/cancel', [TestOrderFlowController::class, 'cancel'])->name('cancel');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/admin/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])->name('admin.login.store');
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/orders', OrderIndexController::class)->name('orders.index');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
