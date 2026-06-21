<?php

namespace Modules\Device\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceUsageProfile extends Model
{
    protected $fillable = [
        'device_id',
        'hours_per_day',
        'days_per_week',
        'duty_cycle',
        'season',
        'source',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
