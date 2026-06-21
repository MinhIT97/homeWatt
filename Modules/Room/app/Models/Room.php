<?php

namespace Modules\Room\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'home_id',
        'name',
        'type',
        'floor',
        'sort_order',
    ];

    public const TYPES = [
        'living_room' => 'Living Room',
        'bedroom' => 'Bedroom',
        'kitchen' => 'Kitchen',
        'bathroom' => 'Bathroom',
        'garage' => 'Garage',
        'outdoor' => 'Outdoor',
        'other' => 'Other',
    ];

    public function home(): BelongsTo
    {
        return $this->belongsTo(\Modules\Home\Models\Home::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(\Modules\Device\Models\Device::class);
    }
}
