<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ThrottleAiAnalysis
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $perUserLimit = (int) config('ai.rate_limits.per_user_per_hour', 20);
        $perHomeLimit = (int) config('ai.rate_limits.per_home_per_hour', 50);

        $userKey = "ai_throttle:user:{$user->id}:".now()->format('YmdH');
        $userCount = (int) Cache::increment($userKey);
        if ($userCount === 1) {
            Cache::put($userKey, 1, now()->addHour());
        }

        if ($userCount > $perUserLimit) {
            return response()->json([
                'error' => 'rate_limit_exceeded',
                'message' => __('ai.rate_limit_user_exceeded'),
                'retry_after_seconds' => 3600,
            ], 429);
        }

        $homeIds = $user->homeMembers()->pluck('home_id')->all();

        foreach ($homeIds as $homeId) {
            $homeKey = "ai_throttle:home:{$homeId}:".now()->format('YmdH');
            $homeCount = (int) Cache::increment($homeKey);
            if ($homeCount === 1) {
                Cache::put($homeKey, 1, now()->addHour());
            }

            if ($homeCount > $perHomeLimit) {
                return response()->json([
                    'error' => 'rate_limit_exceeded',
                    'message' => __('ai.rate_limit_home_exceeded'),
                    'retry_after_seconds' => 3600,
                ], 429);
            }
        }

        return $next($request);
    }
}
