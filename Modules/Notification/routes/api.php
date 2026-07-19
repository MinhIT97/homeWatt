<?php

use Illuminate\Support\Facades\Route;
use Modules\Notification\Http\Controllers\NotificationController;
use Modules\Notification\Http\Controllers\PushSubscriptionController;

Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('v1')->group(function () {
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'getUnreadCount']);
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('push/subscribe', [PushSubscriptionController::class, 'subscribe']);
    Route::delete('push/unsubscribe', [PushSubscriptionController::class, 'unsubscribe']);
});
