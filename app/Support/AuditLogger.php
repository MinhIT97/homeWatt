<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditLogger
{
    public static function log(string $action, array $context = []): void
    {
        // 1. Log to database
        try {
            DB::table('audit_logs')->insert([
                'user_id' => auth()?->id(),
                'action' => $action,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'context' => json_encode($context),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to write audit log to database', [
                'error' => $e->getMessage(),
                'action' => $action,
                'context' => $context,
            ]);
        }

        // 2. Duplicate to standard logs for dev/ops monitoring
        Log::channel(config('logging.default'))->info("[AUDIT] {$action}", array_merge([
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'user_id' => auth()?->id(),
            'timestamp' => now()->toIso8601String(),
        ], $context));
    }
}
