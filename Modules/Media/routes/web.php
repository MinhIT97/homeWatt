<?php

use Illuminate\Support\Facades\Route;
use Modules\Media\Http\Controllers\MediaController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::post('/media/upload', [MediaController::class, 'store'])->name('media.store');
    Route::get('/media/{media}', [MediaController::class, 'serve'])->name('media.serve');
    Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
});
