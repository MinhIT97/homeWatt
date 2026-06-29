<?php

namespace Modules\Device\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Modules\AI\Contracts\DeviceImageAnalyzer;
use Modules\Device\Events\DevicePhotoScannedEvent;

class ScanDevicePhotoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    public $timeout = 180;

    public $backoff = 30;

    public function __construct(
        protected string $analysisId,
        protected int $userId,
        protected string $imageBase64
    ) {
        $this->onQueue('ai');
    }

    public function handle(DeviceImageAnalyzer $analyzer): void
    {
        try {
            $result = $analyzer->analyze($this->imageBase64);

            if ($result) {
                $payload = [
                    'status' => 'completed',
                    'success' => true,
                    'user_id' => $this->userId,
                    'data' => $result,
                ];
            } else {
                $payload = [
                    'status' => 'failed',
                    'success' => false,
                    'user_id' => $this->userId,
                    'message' => 'AI could not read specifications from the image.',
                ];
            }
        } catch (\Throwable $e) {
            $payload = [
                'status' => 'failed',
                'success' => false,
                'user_id' => $this->userId,
                'message' => $e->getMessage(),
            ];
        }

        // Store result in cache
        Cache::put("device_analysis:{$this->analysisId}", $payload, 3600);

        broadcast(new DevicePhotoScannedEvent($this->analysisId, [
            'status' => $payload['status'],
            'success' => $payload['success'],
        ]))->toOthers();
    }
}
