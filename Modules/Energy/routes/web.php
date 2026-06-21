<?php

use Illuminate\Support\Facades\Route;
use Modules\Energy\Http\Controllers\EnergyController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::resource('energy', EnergyController::class)->except(['edit', 'update', 'destroy']);
    Route::post('/energy/calculate', [EnergyController::class, 'calculate'])->name('energy.calculate');
});
