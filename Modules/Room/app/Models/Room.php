<?php

namespace Modules\Room\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Device\Models\Device;
use Modules\Home\Models\Home;

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

    public function home(): BelongsTo
    {
        return $this->belongsTo(Home::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
