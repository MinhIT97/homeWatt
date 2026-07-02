<?php

namespace Modules\Expense\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Home\Models\Home;
use Modules\Wallet\Models\Wallet;

class ExpenseTransactionTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'home_id',
        'wallet_id',
        'category_id',
        'from_wallet_id',
        'to_wallet_id',
        'name',
        'type',
        'amount',
        'description',
        'notes',
        'icon',
        'is_system',
        'sort_order',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_system' => 'boolean',
        'sort_order' => 'integer',
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

    public function fromWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'from_wallet_id');
    }

    public function toWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'to_wallet_id');
    }
}
