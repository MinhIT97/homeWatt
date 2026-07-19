<?php

namespace Modules\Notification\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Notification\Models\NotificationModel;

class NotificationController extends Controller
{
    /**
     * Get paginated in-app notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = NotificationModel::forUser($request->user()->id)
            ->where('channel', 'in_app')
            ->orderByDesc('sent_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json($notifications);
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(NotificationModel $notification): JsonResponse
    {
        if ($notification->user_id !== request()->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification->update(['read_at' => now()]);

        return response()->json(['message' => 'Notification marked as read.']);
    }

    /**
     * Mark all unread notifications as read for the authenticated user.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        NotificationModel::forUser($request->user()->id)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    /**
     * Get the count of unread in-app notifications.
     */
    public function getUnreadCount(Request $request): JsonResponse
    {
        $count = NotificationModel::forUser($request->user()->id)
            ->where('channel', 'in_app')
            ->unread()
            ->count();

        return response()->json(['unread_count' => $count]);
    }
}
