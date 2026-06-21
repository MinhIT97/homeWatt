<?php

namespace Modules\Device\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'default_duty_cycle',
    ];

    public function devices()
    {
        return $this->hasMany(Device::class);
    }
}
