<?php

namespace Modules\Energy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnergyEstimate extends Model
{
    protected $fillable = [
        'device_id',
        'period_type',
        'period_start',
        'period_end',
        'method',
        'estimated_kwh',
        'estimated_cost',
        'confidence',
        'lower_range_kwh',
        'upper_range_kwh',
        'input_snapshot',
        'tariff_plan_id',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'estimated_kwh' => 'float',
        'estimated_cost' => 'float',
        'confidence' => 'float',
        'lower_range_kwh' => 'float',
        'upper_range_kwh' => 'float',
        'input_snapshot' => 'array',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(\Modules\Device\Models\Device::class);
    }

    public function tariffPlan(): BelongsTo
    {
        return $this->belongsTo(\Modules\Tariff\Models\TariffPlan::class);
    }
}
