<?php

namespace Modules\Home\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'home_id',
        'user_id',
    ];

    public const ROLE_OWNER = 'owner';

    public const ROLE_MANAGER = 'manager';

    public const ROLE_MEMBER = 'member';

    public const ROLE_VIEWER = 'viewer';

    public const ROLES = [
        self::ROLE_OWNER,
        self::ROLE_MANAGER,
        self::ROLE_MEMBER,
        self::ROLE_VIEWER,
    ];

    public const ROLE_HIERARCHY = [
        self::ROLE_VIEWER => 0,
        self::ROLE_MEMBER => 1,
        self::ROLE_MANAGER => 2,
        self::ROLE_OWNER => 3,
    ];

    public const EDITOR_ROLES = [self::ROLE_OWNER, self::ROLE_MANAGER];

    public function assignRole(string $role): bool
    {
        if (! in_array($role, self::ROLES, true)) {
            throw new \InvalidArgumentException("Invalid role: {$role}");
        }

        return $this->forceFill(['role' => $role])->save();
    }

    public function hasRoleAtLeast(string $role): bool
    {
        $currentLevel = self::ROLE_HIERARCHY[$this->role] ?? -1;
        $requiredLevel = self::ROLE_HIERARCHY[$role] ?? PHP_INT_MAX;

        return $currentLevel >= $requiredLevel;
    }

    public function isEditor(): bool
    {
        return in_array($this->role, self::EDITOR_ROLES, true);
    }

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
        return $this->isEditor();
    }
}
