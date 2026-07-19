<?php

namespace Modules\Goal\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Modules\Energy\Models\EnergyReading;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Home\Models\Home;
use Modules\Wallet\Models\Wallet;

class Goal extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'home_id',
        'user_id',
        'name',
        'type',
        'target_amount',
        'current_amount',
        'starts_at',
        'ends_at',
        'icon',
        'color',
        'category_id',
        'wallet_id',
        'status',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'current_amount' => 'decimal:2',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function home(): BelongsTo
    {
        return $this->belongsTo(Home::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(GoalSnapshot::class);
    }

    public function percentage(): float
    {
        if ((float) $this->target_amount <= 0) {
            return 0.0;
        }

        return min(100.0, round(((float) $this->current_amount / (float) $this->target_amount) * 100, 2));
    }

    public function recalculate(): void
    {
        $now = now();

        $currentAmount = match ($this->type) {
            'savings' => $this->calculateSavings($now),
            'debt_payoff' => $this->calculateDebtPayoff($now),
            'energy_reduction' => $this->calculateEnergyReduction($now),
            'expense_limit' => $this->calculateExpenseLimit($now),
            'income_target' => $this->calculateIncomeTarget($now),
            default => (float) $this->current_amount,
        };

        $this->forceFill(['current_amount' => $currentAmount])->save();
    }

    private function calculateSavings($now): float
    {
        $income = (float) Expense::where('home_id', $this->home_id)
            ->where('type', 'income')
            ->whereNull('transfer_id')
            ->whereBetween('occurred_at', [$this->starts_at->startOfDay(), $this->ends_at->endOfDay()])
            ->sum('amount');

        $expense = (float) Expense::where('home_id', $this->home_id)
            ->where('type', 'expense')
            ->whereNull('transfer_id')
            ->whereBetween('occurred_at', [$this->starts_at->startOfDay(), $this->ends_at->endOfDay()])
            ->sum('amount');

        return max(0, $income - $expense);
    }

    private function calculateDebtPayoff($now): float
    {
        $debtCategoryIds = DB::table('expense_categories')
            ->where('home_id', $this->home_id)
            ->whereIn('category_group', ExpenseCategory::DEBT_GROUPS)
            ->pluck('id');

        return (float) Expense::where('home_id', $this->home_id)
            ->where('type', 'expense')
            ->whereNull('transfer_id')
            ->whereIn('category_id', $debtCategoryIds->all())
            ->whereBetween('occurred_at', [$this->starts_at->startOfDay(), $this->ends_at->endOfDay()])
            ->sum('amount');
    }

    private function calculateEnergyReduction($now): float
    {
        // Total kWh consumed during the goal period (compared to a baseline)
        $deviceIds = DB::table('devices')
            ->join('rooms', 'rooms.id', '=', 'devices.room_id')
            ->where('rooms.home_id', $this->home_id)
            ->pluck('devices.id');

        return (float) EnergyReading::whereIn('device_id', $deviceIds->all())
            ->whereBetween('recorded_at', [$this->starts_at->startOfDay(), $this->ends_at->endOfDay()])
            ->sum('kwh');
    }

    private function calculateExpenseLimit($now): float
    {
        $query = Expense::where('home_id', $this->home_id)
            ->where('type', 'expense')
            ->whereNull('transfer_id')
            ->whereBetween('occurred_at', [$this->starts_at->startOfDay(), $this->ends_at->endOfDay()]);

        if ($this->category_id) {
            $query->where('category_id', $this->category_id);
        }
        if ($this->wallet_id) {
            $query->where('wallet_id', $this->wallet_id);
        }

        return (float) $query->sum('amount');
    }

    private function calculateIncomeTarget($now): float
    {
        return (float) Expense::where('home_id', $this->home_id)
            ->where('type', 'income')
            ->whereNull('transfer_id')
            ->whereBetween('occurred_at', [$this->starts_at->startOfDay(), $this->ends_at->endOfDay()])
            ->sum('amount');
    }
}
