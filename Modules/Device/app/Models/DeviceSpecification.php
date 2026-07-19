<?php

namespace Modules\Device\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceSpecification extends Model
{
    use HasFactory;

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
        'voltage' => 'decimal:2',
        'current' => 'decimal:2',
        'rated_power' => 'decimal:2',
        'max_power' => 'decimal:2',
        'standby_power' => 'decimal:2',
        'capacity' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
