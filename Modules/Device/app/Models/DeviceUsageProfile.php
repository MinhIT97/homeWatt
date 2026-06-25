<?php

namespace Modules\Device\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceUsageProfile extends Model
{
    use HasFactory;

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
