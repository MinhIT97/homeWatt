<?php

namespace Modules\Expense\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Home\Models\Home;

class ExpenseBudget extends Model
{
    use HasFactory;

    protected $fillable = [
        'home_id',
        'category_id',
        'amount',
        'month',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function home(): BelongsTo
    {
        return $this->belongsTo(Home::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }
}
