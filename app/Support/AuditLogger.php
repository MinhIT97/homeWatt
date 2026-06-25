<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

class AuditLogger
{
    public static function log(string $action, array $context = []): void
    {
        Log::channel(config('logging.default'))->info("[AUDIT] {$action}", array_merge([
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'user_id' => auth()?->id(),
            'timestamp' => now()->toIso8601String(),
        ], $context));
    }
}
