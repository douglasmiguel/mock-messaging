<?php

use App\Models\Rider;
use App\Models\RiderAssignment;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home', [
        'riderCount' => Rider::query()->count(),
        'activeAssignments' => RiderAssignment::query()->where('status', 'active')->count(),
    ]);
});
