<?php

namespace Modules\Expense\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Home\Models\Home;
use Modules\Wallet\Models\Wallet;

class Transfer extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'transfers';

    protected $fillable = [
        'home_id',
        'from_wallet_id',
        'to_wallet_id',
        'user_id',
        'amount',
        'fee',
        'currency',
        'description',
        'occurred_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'occurred_at' => 'datetime',
    ];

    public function home(): BelongsTo
    {
        return $this->belongsTo(Home::class);
    }

    public function fromWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'from_wallet_id');
    }

    public function toWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'to_wallet_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'transfer_id');
    }

    public function totalAmount(): float
    {
        return (float) $this->amount + (float) $this->fee;
    }
}
