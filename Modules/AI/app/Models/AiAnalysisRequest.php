<?php

namespace Modules\AI\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Media\Models\Media;

class AiAnalysisRequest extends Model
{
    use HasFactory;

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
        return $this->belongsTo(User::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function result(): HasOne
    {
        return $this->hasOne(AiAnalysisResult::class);
    }
}
