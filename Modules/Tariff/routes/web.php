<?php

use Illuminate\Support\Facades\Route;
use Modules\Tariff\Http\Controllers\TariffController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::resource('tariff', TariffController::class)->except(['edit', 'update']);
});
