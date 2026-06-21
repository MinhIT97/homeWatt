<?php

namespace Modules\Energy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyEnergySummary extends Model
{
    protected $fillable = [
        'home_id',
        'room_id',
        'device_id',
        'year',
        'month',
        'total_kwh',
        'estimated_cost',
        'reading_count',
        'estimate_count',
        'metadata',
    ];

    protected $casts = [
        'total_kwh' => 'float',
        'estimated_cost' => 'float',
        'metadata' => 'array',
    ];

    public function home(): BelongsTo
    {
        return $this->belongsTo(\Modules\Home\Models\Home::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(\Modules\Room\Models\Room::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(\Modules\Device\Models\Device::class);
    }
}
