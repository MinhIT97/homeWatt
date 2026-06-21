<?php

use Illuminate\Support\Facades\Route;
use Modules\Device\Http\Controllers\DeviceController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::resource('devices', DeviceController::class);
});
