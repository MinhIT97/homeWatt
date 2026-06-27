<?php

namespace Modules\Wallet\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\Transfer;
use Modules\Home\Models\Home;

class Wallet extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'home_id',
        'name',
        'type',
        'currency',
        'opening_balance',
        'icon',
        'color',
        'description',
        'is_archived',
        'sort_order',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'is_archived' => 'boolean',
        'sort_order' => 'integer',
    ];

    public const TYPE_CASH = 'cash';

    public const TYPE_BANK = 'bank';

    public const TYPE_CREDIT_CARD = 'credit_card';

    public const TYPES = [self::TYPE_CASH, self::TYPE_BANK, self::TYPE_CREDIT_CARD];

    public function home(): BelongsTo
    {
        return $this->belongsTo(Home::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function transfersFrom(): HasMany
    {
        return $this->hasMany(Transfer::class, 'from_wallet_id');
    }

    public function transfersTo(): HasMany
    {
        return $this->hasMany(Transfer::class, 'to_wallet_id');
    }

    public function isAccessibleBy(User $user): bool
    {
        return $this->home?->isMember($user->id) ?? false;
    }

    /**
     * Calculate current balance from opening + transactions.
     * balance field is cached; this is the source of truth.
     */
    public function calculatedBalance(): float
    {
        $income = (float) $this->expenses()
            ->where('type', Expense::TYPE_INCOME)
            ->whereNull('transfer_id')
            ->sum('amount');

        $expense = (float) $this->expenses()
            ->where('type', Expense::TYPE_EXPENSE)
            ->whereNull('transfer_id')
            ->sum('amount');

        $transferIn = (float) Transfer::where('to_wallet_id', $this->id)
            ->whereNull('deleted_at')
            ->sum('amount');

        $transferOut = (float) Transfer::where('from_wallet_id', $this->id)
            ->whereNull('deleted_at')
            ->selectRaw('SUM(amount + fee) as total')->value('total') ?? 0;

        return (float) $this->opening_balance + $income - $expense + $transferIn - $transferOut;
    }

    /**
     * Recalculate and persist balance field.
     */
    public function refreshBalance(): float
    {
        $newBalance = $this->calculatedBalance();

        $this->forceFill(['balance' => $newBalance])->save();

        return $newBalance;
    }

    public function canDelete(): bool
    {
        return abs($this->calculatedBalance()) < 0.01;
    }

    public function archive(): bool
    {
        return $this->forceFill(['is_archived' => true])->save();
    }

    public function unarchive(): bool
    {
        return $this->forceFill(['is_archived' => false])->save();
    }

    public function netBalance(): float
    {
        if ($this->type === self::TYPE_CREDIT_CARD) {
            return (float) $this->balance - (float) $this->opening_balance;
        }
        return (float) $this->balance;
    }
}
