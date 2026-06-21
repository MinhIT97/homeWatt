<?php

namespace Modules\Energy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnergyReading extends Model
{
    protected $fillable = [
        'device_id',
        'recorded_at',
        'watts',
        'kwh',
        'source',
        'measurement_type',
        'interval_minutes',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'watts' => 'float',
        'kwh' => 'float',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(\Modules\Device\Models\Device::class);
    }
}
