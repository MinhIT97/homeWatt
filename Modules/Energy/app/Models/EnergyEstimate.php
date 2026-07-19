<?php

namespace Modules\Energy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Device\Models\Device;
use Modules\Tariff\Models\TariffPlan;

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
        'estimated_kwh' => 'decimal:4',
        'estimated_cost' => 'decimal:2',
        'confidence' => 'decimal:2',
        'lower_range_kwh' => 'decimal:4',
        'upper_range_kwh' => 'decimal:4',
        'input_snapshot' => 'array',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function tariffPlan(): BelongsTo
    {
        return $this->belongsTo(TariffPlan::class);
    }
}
