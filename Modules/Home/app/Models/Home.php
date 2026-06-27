<?php

namespace Modules\Home\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Device\Models\Device;
use Modules\Room\Models\Room;

class Home extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'owner_id',
        'name',
        'address',
        'timezone',
        'currency',
    ];

    public const STATUSES = ['active', 'inactive'];

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    public function updateStatus(string $status): bool
    {
        if (! in_array($status, self::STATUSES, true)) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }

        return $this->forceFill(['status' => $status])->save();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(HomeMember::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isMember(int|string $userId): bool
    {
        return $this->members()->where('user_id', $userId)->exists();
    }

    public function member(int|string $userId): ?HomeMember
    {
        return $this->members()->where('user_id', $userId)->first();
    }

    public function hasMemberWithRole(int|string $userId, array|string $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];

        return $this->members()
            ->where('user_id', $userId)
            ->whereIn('role', $roles)
            ->exists();
    }

    public function totalRoomsPrice(): float
    {
        return (float) $this->rooms()->sum('price');
    }

    public function totalDevicesPrice(): float
    {
        return (float) Device::whereHas(
            'room',
            fn ($q) => $q->where('home_id', $this->id)
        )->sum('purchase_price');
    }

    public function totalPrice(): float
    {
        return $this->totalRoomsPrice() + $this->totalDevicesPrice();
    }
}
