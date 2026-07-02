<?php

namespace Modules\Expense\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Home\Models\Home;
use Modules\Wallet\Models\Wallet;

class ExpenseRecurringTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'home_id',
        'wallet_id',
        'category_id',
        'name',
        'type',
        'amount',
        'frequency',
        'description',
        'notes',
        'start_date',
        'next_due_date',
        'last_generated_at',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'next_due_date' => 'date',
        'last_generated_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public const FREQUENCY_WEEKLY = 'weekly';

    public const FREQUENCY_MONTHLY = 'monthly';

    public const FREQUENCY_YEARLY = 'yearly';

    public const FREQUENCIES = [
        self::FREQUENCY_WEEKLY,
        self::FREQUENCY_MONTHLY,
        self::FREQUENCY_YEARLY,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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
}
