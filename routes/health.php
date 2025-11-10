<?php

use Cliomusetours\LaravelHealth\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/live', [HealthController::class, 'live'])->name('health.live');
Route::get('/ready', [HealthController::class, 'ready'])->name('health.ready');
