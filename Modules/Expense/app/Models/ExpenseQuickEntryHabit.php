<?php

namespace Modules\Expense\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Home\Models\Home;
use Modules\Wallet\Models\Wallet;

class ExpenseQuickEntryHabit extends Model
{
    protected $fillable = [
        'user_id',
        'home_id',
        'wallet_id',
        'category_id',
        'keyword',
        'usage_count',
        'last_used_at',
    ];

    protected $casts = [
        'usage_count' => 'integer',
        'last_used_at' => 'datetime',
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
