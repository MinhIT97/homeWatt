<?php

namespace Modules\Tariff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TariffTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'tariff_plan_id',
        'tier_number',
        'limit_kwh',
        'rate',
        'tax_percent',
        'surcharge',
    ];

    protected $casts = [
        'limit_kwh' => 'float',
        'rate' => 'float',
        'tax_percent' => 'float',
        'surcharge' => 'float',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(TariffPlan::class, 'tariff_plan_id');
    }
}
