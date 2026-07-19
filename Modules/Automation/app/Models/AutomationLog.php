<?php

namespace Modules\Automation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationLog extends Model
{
    protected $fillable = [
        'rule_id', 'home_id', 'trigger_event',
        'matched_conditions', 'executed_actions',
        'status', 'error_message', 'executed_at',
    ];

    protected function casts(): array
    {
        return [
            'matched_conditions' => 'array',
            'executed_actions' => 'array',
            'executed_at' => 'datetime',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'rule_id');
    }
}
