<?php

namespace Modules\Energy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Device\Models\Device;
use Modules\Home\Models\Home;
use Modules\Room\Models\Room;

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
        return $this->belongsTo(Home::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
