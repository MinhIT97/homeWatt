<?php

use Illuminate\Support\Facades\Route;
use Modules\Notification\Http\Controllers\NotificationPreferenceController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('notification-preferences', [NotificationPreferenceController::class, 'edit'])
        ->name('notification.preferences');
    Route::put('notification-preferences', [NotificationPreferenceController::class, 'update'])
        ->name('notification.preferences.update');
});
