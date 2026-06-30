<?php

namespace Modules\Device\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceRepair extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'repaired_at',
        'cost',
        'description',
        'repairer',
    ];

    protected $casts = [
        'repaired_at' => 'date',
        'cost' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::saved(function ($repair) {
            $device = $repair->device;
            if ($device && $repair->repaired_at) {
                if (! $device->last_maintained_at || $repair->repaired_at->gt($device->last_maintained_at)) {
                    $device->update(['last_maintained_at' => $repair->repaired_at]);
                }
            }
        });
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
