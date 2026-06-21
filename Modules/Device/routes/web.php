<?php

use Illuminate\Support\Facades\Route;
use Modules\Device\Http\Controllers\DeviceController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::resource('devices', DeviceController::class);

    Route::post('/devices/{device}/upload-image', [DeviceController::class, 'uploadImage'])
        ->name('devices.upload-image');

    Route::delete('/devices/{device}/images/{media}', [DeviceController::class, 'deleteImage'])
        ->name('devices.delete-image');
});
