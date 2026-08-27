<?php

use App\Http\Controllers\MetricsController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/metrics');
Route::get('/metrics', MetricsController::class)->name('metrics');
