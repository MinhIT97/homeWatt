<?php

namespace Modules\AI\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AiAnalysisRequest extends Model
{
    protected $fillable = [
        'user_id',
        'media_id',
        'provider',
        'model',
        'status',
        'attempts',
        'error',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(\Modules\Media\Models\Media::class);
    }

    public function result(): HasOne
    {
        return $this->hasOne(AiAnalysisResult::class);
    }
}
