<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FareEstimateController;
use App\Http\Controllers\Api\RideController;
use App\Http\Controllers\Api\RideLifecycleController;


Route::post('/fare-estimate', FareEstimateController::class)
    ->name('api.fare-estimate');

Route::post('/rides', [RideController::class, 'store'])
    ->name('api.rides.store');

Route::get('/rides/{id}', [RideController::class, 'show'])
    ->name('api.rides.show');

Route::post('/rides/{id}/accept', [RideLifecycleController::class, 'accept'])
    ->name('api.rides.accept');

Route::post('/rides/{id}/start', [RideLifecycleController::class, 'start'])
    ->name('api.rides.start');

Route::post('/rides/{id}/complete', [RideLifecycleController::class, 'complete'])
    ->name('api.rides.complete');

Route::post('/rides/{id}/cancel', [RideLifecycleController::class, 'cancel'])
    ->name('api.rides.cancel');