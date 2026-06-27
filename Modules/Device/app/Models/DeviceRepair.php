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

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
