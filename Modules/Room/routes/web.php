<?php

use Illuminate\Support\Facades\Route;
use Modules\Room\Http\Controllers\RoomController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::resource('rooms', RoomController::class);
});
