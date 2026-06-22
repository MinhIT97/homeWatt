<?php

namespace Modules\Home\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeMember extends Model
{
    protected $fillable = [
        'home_id',
        'user_id',
        'role',
    ];

    public const ROLES = ['owner', 'manager', 'member', 'viewer'];

    public function home(): BelongsTo
    {
        return $this->belongsTo(Home::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager' || $this->role === 'owner';
    }

    public function canEdit(): bool
    {
        return in_array($this->role, ['owner', 'manager']);
    }
}
