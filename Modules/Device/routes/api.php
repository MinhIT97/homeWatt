<?php

use Illuminate\Support\Facades\Route;
use Modules\Device\Http\Controllers\DeviceController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('devices', DeviceController::class)->names('device');
});
