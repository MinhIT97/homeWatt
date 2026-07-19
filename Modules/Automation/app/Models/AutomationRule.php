<?php

namespace Modules\Automation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Home\Models\Home;

class AutomationRule extends Model
{
    protected $fillable = [
        'home_id', 'user_id', 'name', 'description',
        'trigger_event', 'is_active',
        'conditions', 'actions',
        'priority', 'last_triggered_at', 'trigger_count',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'conditions' => 'array',
            'actions' => 'array',
            'last_triggered_at' => 'datetime',
            'trigger_count' => 'integer',
            'priority' => 'integer',
        ];
    }

    public function home(): BelongsTo
    {
        return $this->belongsTo(Home::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AutomationLog::class, 'rule_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForEvent($query, string $event)
    {
        return $query->where('trigger_event', $event);
    }

    public function markTriggered(): void
    {
        $this->forceFill([
            'last_triggered_at' => now(),
            'trigger_count' => $this->trigger_count + 1,
        ])->save();
    }
}
