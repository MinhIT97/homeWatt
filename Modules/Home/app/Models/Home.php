<?php

namespace Modules\Home\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Home extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'owner_id',
        'name',
        'address',
        'timezone',
        'currency',
        'status',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(HomeMember::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(\Modules\Room\Models\Room::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
