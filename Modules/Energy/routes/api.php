<?php

use Illuminate\Support\Facades\Route;
use Modules\Energy\Http\Controllers\EnergyController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('energies', EnergyController::class)->names('energy');
});
