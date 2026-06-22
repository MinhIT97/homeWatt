<?php

namespace Modules\Device\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Energy\Models\EnergyReading;
use Modules\Media\Models\Media;
use Modules\Room\Models\Room;

class Device extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'room_id',
        'device_type_id',
        'name',
        'brand',
        'model',
        'serial',
        'status',
        'purchased_at',
    ];

    protected $casts = [
        'purchased_at' => 'date',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function deviceType(): BelongsTo
    {
        return $this->belongsTo(DeviceType::class);
    }

    public function specification(): HasOne
    {
        return $this->hasOne(DeviceSpecification::class);
    }

    public function usageProfile(): HasOne
    {
        return $this->hasOne(DeviceUsageProfile::class);
    }

    public function energyReadings(): HasMany
    {
        return $this->hasMany(EnergyReading::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'owner');
    }
}
