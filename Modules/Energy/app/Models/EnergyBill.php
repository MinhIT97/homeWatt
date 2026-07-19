<?php

namespace Modules\Energy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Expense\Models\Expense;
use Modules\Home\Models\Home;

class EnergyBill extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'home_id',
        'expense_id',
        'user_id',
        'provider',
        'customer_name',
        'customer_code',
        'billing_period',
        'period_start',
        'period_end',
        'old_index',
        'new_index',
        'kwh',
        'amount',
        'currency',
        'source',
        'raw_payload',
        'scanned_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'old_index' => 'decimal:2',
        'new_index' => 'decimal:2',
        'kwh' => 'decimal:4',
        'amount' => 'decimal:2',
        'raw_payload' => 'array',
        'scanned_at' => 'datetime',
    ];

    public function home(): BelongsTo
    {
        return $this->belongsTo(Home::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
