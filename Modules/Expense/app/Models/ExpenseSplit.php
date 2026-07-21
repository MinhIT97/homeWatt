<?php

namespace Modules\Expense\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Home\Models\Home;

class ExpenseSplit extends Model
{
    protected $table = 'expense_splits';

    protected $fillable = [
        'expense_id',
        'home_id',
        'paid_by',
        'owed_by',
        'amount',
        'paid_amount',
        'status',
        'settled_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'settled_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_SETTLED = 'settled';

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function ower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owed_by');
    }

    public function home(): BelongsTo
    {
        return $this->belongsTo(Home::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeSettled($query)
    {
        return $query->where('status', self::STATUS_SETTLED);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('paid_by', $userId)
                ->orWhere('owed_by', $userId);
        });
    }

    public function scopeByHome($query, int $homeId)
    {
        return $query->where('home_id', $homeId);
    }

    public function remaining(): float
    {
        return (float) $this->amount - (float) $this->paid_amount;
    }

    public function settle(?float $amount = null): void
    {
        if ($this->status === self::STATUS_SETTLED) {
            return;
        }

        $settleAmount = $amount ?? $this->remaining();

        $newPaid = (float) $this->paid_amount + $settleAmount;

        if ($newPaid >= (float) $this->amount) {
            $this->forceFill([
                'paid_amount' => $this->amount,
                'status' => self::STATUS_SETTLED,
                'settled_at' => now(),
            ])->save();
        } else {
            $this->forceFill([
                'paid_amount' => $newPaid,
                'status' => self::STATUS_PARTIAL,
            ])->save();
        }
    }

    public function isSettled(): bool
    {
        return $this->status === self::STATUS_SETTLED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
