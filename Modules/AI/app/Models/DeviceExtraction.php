<?php

namespace Modules\AI\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Device\Models\Device;

class DeviceExtraction extends Model
{
    protected $fillable = [
        'ai_analysis_result_id',
        'device_id',
        'field',
        'ai_value',
        'confirmed_value',
        'confidence',
        'status',
    ];

    public function result(): BelongsTo
    {
        return $this->belongsTo(AiAnalysisResult::class, 'ai_analysis_result_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }
}
