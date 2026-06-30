<?php

namespace Modules\Device\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Modules\Energy\Models\EnergyReading;
use Modules\Media\Models\Media;
use Modules\Room\Models\Room;

class Device extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'room_id',
        'device_type_id',
        'name',
        'brand',
        'model',
        'location',
        'serial',
        'purchased_at',
        'purchase_price',
        'warranty_duration',
        'warranty_unit',
        'maintenance_interval',
        'last_maintained_at',
    ];

    public const STATUSES = ['active', 'inactive', 'broken'];

    public function updateStatus(string $status): bool
    {
        if (! in_array($status, self::STATUSES, true)) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }

        return $this->forceFill(['status' => $status])->save();
    }

    protected $casts = [
        'purchased_at' => 'date',
        'purchase_price' => 'decimal:2',
        'last_maintained_at' => 'date',
    ];

    public function getNextMaintenanceAtAttribute()
    {
        if (! $this->maintenance_interval) {
            return null;
        }

        $baseDate = $this->last_maintained_at
            ?: ($this->purchased_at ?: $this->created_at);

        if (! $baseDate) {
            return null;
        }

        // Handle case where baseDate is string from database
        $base = Carbon::parse($baseDate);

        return $base->addMonths($this->maintenance_interval);
    }

    public function getIsDueForMaintenanceAttribute(): bool
    {
        $next = $this->next_maintenance_at;
        if (! $next) {
            return false;
        }

        return $next->isPast() || $next->isToday();
    }

    public function getWarrantyExpiresAtAttribute()
    {
        if (! $this->purchased_at || ! $this->warranty_duration) {
            return null;
        }

        $unit = $this->warranty_unit === 'year' ? 'addYears' : 'addMonths';

        return $this->purchased_at->copy()->$unit($this->warranty_duration);
    }

    public function getIsUnderWarrantyAttribute(): bool
    {
        $expiry = $this->warranty_expires_at;
        if (! $expiry) {
            return false;
        }

        return $expiry->isFuture() || $expiry->isToday();
    }

    public function repairs(): HasMany
    {
        return $this->hasMany(DeviceRepair::class);
    }

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
