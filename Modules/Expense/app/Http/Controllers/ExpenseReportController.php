<?php

namespace Modules\Expense\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\Transfer;
use Modules\Home\Models\Home;
use Modules\Wallet\Models\Wallet;

class ExpenseReportController extends Controller
{
    public function monthly(Request $request): View
    {
        $userId = $request->user()->id;

        $year = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);
        $homeId = $request->get('home_id');

        $homes = Home::whereHas('members', fn ($q) => $q->where('user_id', $userId))->get();
        $selectedHomeId = $homeId ?? $homes->first()?->id;

        $report = null;
        if ($selectedHomeId) {
            $report = $this->buildMonthlyReport($selectedHomeId, $year, $month);
        }

        return view('expense::report.monthly', compact(
            'homes', 'selectedHomeId', 'report', 'year', 'month'
        ));
    }

    public function byCategory(Request $request): View
    {
        $userId = $request->user()->id;

        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->endOfMonth()->toDateString());
        $homeId = $request->get('home_id');

        $homes = Home::whereHas('members', fn ($q) => $q->where('user_id', $userId))->get();
        $selectedHomeId = $homeId ?? $homes->first()?->id;

        $report = null;
        if ($selectedHomeId) {
            $report = $this->buildCategoryReport($selectedHomeId, $from, $to);
        }

        return view('expense::report.category', compact(
            'homes', 'selectedHomeId', 'report', 'from', 'to'
        ));
    }

    private function buildMonthlyReport(int $homeId, int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        // Get debt categories
        $debtCategories = DB::table('expense_categories')
            ->where('home_id', $homeId)
            ->whereIn('name', ['Cho vay', 'Trả nợ', 'Đi vay', 'Thu nợ'])
            ->get();
        $debtCategoryIds = $debtCategories->pluck('id')->toArray();

        // Calculate specific debt types
        $lentCategoryId = $debtCategories->firstWhere('name', 'Cho vay')?->id;
        $totalLent = $lentCategoryId ? (float) Expense::where('home_id', $homeId)
            ->where('category_id', $lentCategoryId)
            ->whereBetween('occurred_at', [$start, $end])
            ->sum('amount') : 0.0;

        $collectedCategoryId = $debtCategories->firstWhere('name', 'Thu nợ')?->id;
        $totalCollected = $collectedCategoryId ? (float) Expense::where('home_id', $homeId)
            ->where('category_id', $collectedCategoryId)
            ->whereBetween('occurred_at', [$start, $end])
            ->sum('amount') : 0.0;

        $borrowedCategoryId = $debtCategories->firstWhere('name', 'Đi vay')?->id;
        $totalBorrowed = $borrowedCategoryId ? (float) Expense::where('home_id', $homeId)
            ->where('category_id', $borrowedCategoryId)
            ->whereBetween('occurred_at', [$start, $end])
            ->sum('amount') : 0.0;

        $repaidCategoryId = $debtCategories->firstWhere('name', 'Trả nợ')?->id;
        $totalRepaid = $repaidCategoryId ? (float) Expense::where('home_id', $homeId)
            ->where('category_id', $repaidCategoryId)
            ->whereBetween('occurred_at', [$start, $end])
            ->sum('amount') : 0.0;

        $income = (float) Expense::where('home_id', $homeId)
            ->where('type', Expense::TYPE_INCOME)
            ->whereNull('transfer_id')
            ->whereNotIn('category_id', $debtCategoryIds)
            ->whereBetween('occurred_at', [$start, $end])
            ->sum('amount');

        $expense = (float) Expense::where('home_id', $homeId)
            ->where('type', Expense::TYPE_EXPENSE)
            ->whereNull('transfer_id')
            ->whereNotIn('category_id', $debtCategoryIds)
            ->whereBetween('occurred_at', [$start, $end])
            ->sum('amount');

        // Add transfers
        $transferIn = (float) Transfer::where('home_id', $homeId)
            ->whereBetween('occurred_at', [$start, $end])
            ->sum('amount');
        $transferOut = $transferIn; // net effect is zero for budget

        // Wallet balances
        $wallets = Wallet::where('home_id', $homeId)->where('is_archived', false)->get();
        $totalBalance = (float) $wallets->sum(function ($w) {
            if ($w->type === 'credit_card') {
                return (float) $w->balance - (float) $w->opening_balance;
            }
            return (float) $w->balance;
        });

        // Daily breakdown
        $daysInMonth = $start->daysInMonth;
        $dailyData = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $day = $start->copy()->day($d);
            $dayEnd = $day->copy()->endOfDay();
            $dailyData[] = [
                'date' => $day->toDateString(),
                'income' => (float) Expense::where('home_id', $homeId)
                    ->where('type', Expense::TYPE_INCOME)->whereNull('transfer_id')
                    ->whereNotIn('category_id', $debtCategoryIds)
                    ->whereBetween('occurred_at', [$day, $dayEnd])->sum('amount'),
                'expense' => (float) Expense::where('home_id', $homeId)
                    ->where('type', Expense::TYPE_EXPENSE)->whereNull('transfer_id')
                    ->whereNotIn('category_id', $debtCategoryIds)
                    ->whereBetween('occurred_at', [$day, $dayEnd])->sum('amount'),
            ];
        }

        return [
            'income' => $income,
            'expense' => $expense,
            'net' => $income - $expense,
            'transfer_volume' => $transferIn,
            'total_balance' => $totalBalance,
            'wallet_count' => $wallets->count(),
            'daily' => $dailyData,
            'period' => $start->format('Y-m'),
            
            // Debt metrics
            'total_lent' => $totalLent,
            'total_collected' => $totalCollected,
            'total_borrowed' => $totalBorrowed,
            'total_repaid' => $totalRepaid,
            'debt_active' => ($totalLent > 0 || $totalCollected > 0 || $totalBorrowed > 0 || $totalRepaid > 0),
        ];
    }

    private function buildCategoryReport(int $homeId, string $from, string $to): array
    {
        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();

        $rows = DB::table('expenses')
            ->where('expenses.home_id', $homeId)
            ->whereNull('transfer_id')
            ->whereBetween('occurred_at', [$start, $end])
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.category_id')
            ->selectRaw('
                expenses.category_id,
                expense_categories.name,
                expense_categories.type,
                expense_categories.icon,
                expense_categories.color,
                SUM(expenses.amount) as total,
                COUNT(*) as count
            ')
            ->groupBy('expenses.category_id', 'expense_categories.name', 'expense_categories.type', 'expense_categories.icon', 'expense_categories.color')
            ->orderByDesc('total')
            ->get();

        return [
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
        ];
    }
}
