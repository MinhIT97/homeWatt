<?php

namespace Modules\Home\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Device\Models\Device;
use Modules\Energy\Models\EnergyBill;
use Modules\Energy\Models\EnergyReading;
use Modules\Expense\Models\Expense;
use Modules\Room\Models\Room;

class Home extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
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

    public function energyBills(): HasMany
    {
        return $this->hasMany(EnergyBill::class);
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

    public function expenseComparison(string $period = 'month'): array
    {
        $now = now();
        $currentStart = $period === 'month' ? $now->copy()->startOfMonth() : $now->copy()->startOfDay();
        $currentEnd = $period === 'day' ? $now->copy()->endOfDay() : $now->copy()->endOfMonth();
        $lastStart = $currentStart->copy()->subMonth();
        $lastEnd = $currentEnd->copy()->subMonth();
        $lastYearStart = $currentStart->copy()->subYear();
        $lastYearEnd = $currentEnd->copy()->subYear();

        $currentTotal = (float) Expense::where('home_id', $this->id)
            ->where('type', 'expense')->whereNull('transfer_id')
            ->whereBetween('occurred_at', [$currentStart, $currentEnd])
            ->sum('amount');

        $lastMonthTotal = (float) Expense::where('home_id', $this->id)
            ->where('type', 'expense')->whereNull('transfer_id')
            ->whereBetween('occurred_at', [$lastStart, $lastEnd])
            ->sum('amount');

        $lastYearTotal = (float) Expense::where('home_id', $this->id)
            ->where('type', 'expense')->whereNull('transfer_id')
            ->whereBetween('occurred_at', [$lastYearStart, $lastYearEnd])
            ->sum('amount');

        return [
            'current' => $currentTotal,
            'vs_last_month' => $lastMonthTotal > 0 ? round((($currentTotal - $lastMonthTotal) / $lastMonthTotal) * 100, 1) : null,
            'vs_last_year' => $lastYearTotal > 0 ? round((($currentTotal - $lastYearTotal) / $lastYearTotal) * 100, 1) : null,
        ];
    }

    public function energyComparison(): array
    {
        $now = now();
        $currentStart = $now->copy()->startOfMonth();
        $currentEnd = $now->copy()->endOfMonth();
        $lastStart = $currentStart->copy()->subMonth();
        $lastEnd = $currentEnd->copy()->subMonth();

        $currentKwh = (float) EnergyReading::whereHas(
            'device.room', fn ($q) => $q->where('home_id', $this->id)
        )->whereBetween('recorded_at', [$currentStart, $currentEnd])->sum('kwh');

        $lastKwh = (float) EnergyReading::whereHas(
            'device.room', fn ($q) => $q->where('home_id', $this->id)
        )->whereBetween('recorded_at', [$lastStart, $lastEnd])->sum('kwh');

        return [
            'current_kwh' => $currentKwh,
            'previous_kwh' => $lastKwh,
            'change_pct' => $lastKwh > 0 ? round((($currentKwh - $lastKwh) / $lastKwh) * 100, 1) : null,
        ];
    }
}
