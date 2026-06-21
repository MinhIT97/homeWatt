<?php

namespace Modules\Device\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceSpecification extends Model
{
    protected $fillable = [
        'device_id',
        'voltage',
        'current',
        'rated_power',
        'max_power',
        'standby_power',
        'capacity',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
