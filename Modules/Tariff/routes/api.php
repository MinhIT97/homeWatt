<?php

use Illuminate\Support\Facades\Route;
use Modules\Tariff\Http\Controllers\TariffController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('tariffs', TariffController::class)->names('tariff');
});
