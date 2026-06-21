<?php

namespace Modules\AI\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiAnalysisResult extends Model
{
    protected $fillable = [
        'ai_analysis_request_id',
        'raw_response',
        'normalized_data',
        'confidence',
        'prompt_tokens',
        'completion_tokens',
        'cost',
        'processing_time_ms',
    ];

    protected $casts = [
        'raw_response' => 'array',
        'normalized_data' => 'array',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(AiAnalysisRequest::class, 'ai_analysis_request_id');
    }

    public function extractions(): HasMany
    {
        return $this->hasMany(DeviceExtraction::class);
    }
}
