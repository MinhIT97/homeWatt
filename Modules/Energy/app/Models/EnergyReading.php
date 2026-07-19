<?php

namespace Modules\Energy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Device\Models\Device;

class EnergyReading extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'device_id',
        'recorded_at',
        'watts',
        'kwh',
        'source',
        'measurement_type',
        'interval_minutes',
        'idempotency_key',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'watts' => 'decimal:2',
        'kwh' => 'decimal:4',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
