<?php

use Illuminate\Support\Facades\Route;
use Modules\Home\Http\Controllers\HomeController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::resource('homes', HomeController::class);

    Route::get('/homes/{home}/members', [HomeController::class, 'members'])
        ->name('homes.members');

    Route::post('/homes/{home}/invite', [HomeController::class, 'invite'])
        ->name('homes.invite');

    Route::delete('/homes/{home}/members/{member}', [HomeController::class, 'removeMember'])
        ->name('homes.members.remove');
});
