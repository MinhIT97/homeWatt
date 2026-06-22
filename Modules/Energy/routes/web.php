<?php

use Illuminate\Support\Facades\Route;
use Modules\Energy\Http\Controllers\EnergyController;
use Modules\Energy\Http\Controllers\SmartPlugController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::resource('energy', EnergyController::class)->except(['edit', 'update', 'destroy']);
    Route::post('/energy/calculate', [EnergyController::class, 'calculate'])->name('energy.calculate');
});

Route::prefix('api/v1')->group(function () {
    Route::post('/smartplug/reading', [SmartPlugController::class, 'store'])
        ->middleware('throttle:60,1');
});
