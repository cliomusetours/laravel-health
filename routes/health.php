<?php

use Cliomusetours\LaravelHealth\Http\Controllers\LivenessController;
use Cliomusetours\LaravelHealth\Http\Controllers\ReadinessController;
use Illuminate\Support\Facades\Route;

Route::get('/live', LivenessController::class)->name('health.live');
Route::get('/ready', ReadinessController::class)->name('health.ready');
