<?php

use Illuminate\Support\Facades\Route;
use Modules\Device\Http\Controllers\DeviceController;
use Modules\Device\Http\Controllers\ImportController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::resource('devices', DeviceController::class);

    Route::post('/devices/analyze-image', [DeviceController::class, 'analyzeImage'])
        ->name('devices.analyze-image');

    Route::post('/devices/{device}/upload-image', [DeviceController::class, 'uploadImage'])
        ->name('devices.upload-image');

    Route::delete('/devices/{device}/images/{media}', [DeviceController::class, 'deleteImage'])
        ->name('devices.delete-image');

    Route::post('/devices/{device}/repairs', [DeviceController::class, 'storeRepair'])
        ->name('devices.repairs.store');

    Route::get('/devices/import', [ImportController::class, 'showForm'])->name('devices.import');
    Route::post('/devices/import', [ImportController::class, 'import']);
});
