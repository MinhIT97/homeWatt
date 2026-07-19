<?php

namespace Modules\Device\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'default_duty_cycle',
    ];

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function getDisplayNameAttribute(): string
    {
        $translation = __("device.types.{$this->slug}");
        if ($translation === "device.types.{$this->slug}") {
            return $this->name;
        }

        return $translation;
    }
}
