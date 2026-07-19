<?php

namespace Modules\Notification\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Modules\Notification\Models\PushSubscription;

class PushSubscriptionController extends Controller
{
    /**
     * Subscribe the authenticated user to push notifications.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint'         => ['required', 'string', 'max:500'],
            'public_key'       => ['required', 'string', 'max:255'],
            'auth_token'       => ['required', 'string', 'max:255'],
            'content_encoding' => ['nullable', 'string', 'max:50'],
            'user_agent'       => ['nullable', 'string', 'max:255'],
        ]);

        $subscription = PushSubscription::updateOrCreate(
            [
                'user_id'  => $request->user()->id,
                'endpoint' => $validated['endpoint'],
            ],
            [
                'public_key'       => $validated['public_key'],
                'auth_token'       => $validated['auth_token'],
                'content_encoding' => $validated['content_encoding'] ?? 'aesgcm',
                'user_agent'       => $validated['user_agent'] ?? $request->userAgent(),
                'last_used_at'     => now(),
            ]
        );

        return response()->json([
            'message'      => 'Push subscription saved.',
            'subscription' => $subscription,
        ], 201);
    }

    /**
     * Unsubscribe the authenticated user from push notifications.
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
        ]);

        $deleted = PushSubscription::where('user_id', $request->user()->id)
            ->where('endpoint', $validated['endpoint'])
            ->delete();

        if ($deleted === 0) {
            throw ValidationException::withMessages([
                'endpoint' => ['Subscription not found.'],
            ]);
        }

        return response()->json(['message' => 'Push subscription removed.']);
    }
}
