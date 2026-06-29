<?php

namespace Modules\Device\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DevicePhotoScannedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $analysisId,
        public array $data
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('device-analysis'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'scanned';
    }
}
