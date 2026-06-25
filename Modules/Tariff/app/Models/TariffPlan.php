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
        'is_system',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_system' => 'boolean',
    ];

    public function tiers(): HasMany
    {
        return $this->hasMany(TariffTier::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('effective_from', '<=', now())
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', now()));
    }

    public function scopeEffectiveFor($query, $date)
    {
        return $query->where('status', 'active')
            ->where('effective_from', '<=', $date)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date));
    }

    public static function findEffectiveFor($date, ?string $region = null, ?string $type = null): ?self
    {
        $query = static::query()->effectiveFor($date);

        if ($region) {
            $query->where('region', $region);
        }

        if ($type) {
            $query->where('type', $type);
        }

        // Prefer system templates first, then most recent
        return $query
            ->orderByDesc('is_system')
            ->orderByDesc('effective_from')
            ->first();
    }
}
