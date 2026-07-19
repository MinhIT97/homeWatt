<?php

namespace Modules\Goal\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'goal_id',
        'snapshot_date',
        'current_amount',
        'percentage',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'current_amount' => 'decimal:2',
            'percentage' => 'decimal:2',
        ];
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }
}
