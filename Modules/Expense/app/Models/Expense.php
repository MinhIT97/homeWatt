<?php

namespace Modules\Expense\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Energy\Models\EnergyBill;
use Modules\Home\Models\Home;
use Modules\Wallet\Models\Wallet;

class Expense extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'expenses';

    protected $fillable = [
        'home_id',
        'wallet_id',
        'category_id',
        'user_id',
        'type',
        'amount',
        'currency',
        'description',
        'notes',
        'occurred_at',
        'reference',
        'transfer_id',
        'media_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'occurred_at' => 'datetime',
    ];

    public const TYPE_INCOME = 'income';

    public const TYPE_EXPENSE = 'expense';

    public const TYPES = [self::TYPE_INCOME, self::TYPE_EXPENSE];

    public function home(): BelongsTo
    {
        return $this->belongsTo(Home::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class, 'transfer_id');
    }

    public function energyBill(): HasOne
    {
        return $this->hasOne(EnergyBill::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(\Modules\Media\Models\Media::class, 'media_id');
    }

    public function signedAmount(): float
    {
        $sign = $this->type === self::TYPE_INCOME ? 1 : -1;

        return $sign * (float) $this->amount;
    }

    public function isIncome(): bool
    {
        return $this->type === self::TYPE_INCOME;
    }

    public function isExpense(): bool
    {
        return $this->type === self::TYPE_EXPENSE;
    }

    public function belongsToTransfer(): bool
    {
        return $this->transfer_id !== null;
    }
}
