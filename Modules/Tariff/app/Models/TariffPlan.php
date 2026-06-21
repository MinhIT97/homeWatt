<?php

namespace Modules\Tariff\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TariffPlan extends Model
{
    protected $fillable = [
        'name',
        'provider',
        'region',
        'type',
        'effective_from',
        'effective_to',
        'status',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function tiers(): HasMany
    {
        return $this->hasMany(TariffTier::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('effective_from', '<=', now())
            ->where(fn($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', now()));
    }
}
