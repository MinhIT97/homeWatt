<?php

namespace Modules\Home\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeInvitation extends Model
{
    protected $fillable = [
        'home_id',
        'invited_by',
        'token',
        'role',
        'expires_at',
        'max_uses',
        'use_count',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'max_uses' => 'integer',
            'use_count' => 'integer',
        ];
    }

    public function home(): BelongsTo
    {
        return $this->belongsTo(Home::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Scope: only valid invitations (not expired and not fully used).
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now())
            ->whereColumn('use_count', '<', 'max_uses');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isFullyUsed(): bool
    {
        return $this->use_count >= $this->max_uses;
    }

    public function isValid(): bool
    {
        return ! $this->isExpired() && ! $this->isFullyUsed();
    }
}
