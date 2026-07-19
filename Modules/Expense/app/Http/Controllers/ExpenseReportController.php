<?php

namespace Modules\Expense\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Expense\Exports\ExpenseReportExport;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Expense\Models\Transfer;
use Modules\Home\Models\Home;
use Modules\Wallet\Models\Wallet;

class ExpenseReportController extends Controller
{
    private function resolveHome(Request $request): ?Home
    {
        $userId = $request->user()->id;
        $homes = Home::whereHas('members', fn ($q) => $q->where('user_id', $userId))->get();
        $homeId = $request->get('home_id', $homes->first()?->id);

        if (! $homeId || $homes->where('id', $homeId)->isEmpty()) {
            return null;
        }

        return Home::find($homeId);
    }

    private function userHomes(Request $request)
    {
        return Home::whereHas('members', fn ($q) => $q->where('user_id', $request->user()->id))->get();
    }

    // ─── Existing Reports ───────────────────────────────────────────

    public function monthly(Request $request): View
    {
        $userId = $request->user()->id;
        $year = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);
        $home = $this->resolveHome($request);
        $homes = $this->userHomes($request);

        $report = $home ? $this->buildMonthlyReport($home->id, $year, $month) : null;

        return view('expense::report.monthly', compact(
            'homes', 'home', 'report', 'year', 'month'
        ));
    }

    public function byCategory(Request $request): View
    {
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->endOfMonth()->toDateString());
        $home = $this->resolveHome($request);
        $homes = $this->userHomes($request);

        $report = $home ? $this->buildCategoryReport($home->id, $from, $to) : null;

        return view('expense::report.category', compact('homes', 'home', 'report', 'from', 'to'));
    }

    // ─── NEW: Cash Flow Report ──────────────────────────────────────

    public function cashflow(Request $request): View
    {
        $home = $this->resolveHome($request);
        $homes = $this->userHomes($request);
        $year = (int) $request->get('year', now()->year);
        $view = $request->get('view', 'monthly'); // monthly | daily

        $report = $home ? $this->buildCashflowReport($home->id, $year, $view) : null;

        return view('expense::report.cashflow', compact(
            'homes', 'home', 'report', 'year', 'view'
        ));
    }

    private function buildCashflowReport(int $homeId, int $year, string $view): array
    {
        $debtCatIds = ExpenseCategory::where('home_id', $homeId)
            ->whereNull('deleted_at')
            ->whereIn('category_group', ExpenseCategory::DEBT_GROUPS)
            ->pluck('id');

        $query = Expense::where('home_id', $homeId)
            ->whereNull('transfer_id')
            ->whereNotIn('category_id', $debtCatIds)
            ->whereYear('occurred_at', $year);

        if ($view === 'daily') {
            $rows = (clone $query)
                ->selectRaw('DATE(occurred_at) as period, type, SUM(amount) as total')
                ->groupByRaw('DATE(occurred_at), type')
                ->orderBy('period')
                ->get();
        } else {
            $rows = (clone $query)
                ->selectRaw('MONTH(occurred_at) as period, type, SUM(amount) as total')
                ->groupByRaw('MONTH(occurred_at), type')
                ->orderBy('period')
                ->get();
        }

        $transferVolume = (float) Transfer::where('home_id', $homeId)
            ->whereYear('occurred_at', $year)
            ->sum('amount');

        $data = [];
        $incomeSeries = [];
        $expenseSeries = [];
        $netSeries = [];
        $labels = [];
        $cumulativeNet = 0;

        foreach ($rows->groupBy('period') as $period => $group) {
            $income = (float) $group->where('type', 'income')->sum('total');
            $expense = (float) $group->where('type', 'expense')->sum('total');
            $net = $income - $expense;
            $cumulativeNet += $net;

            $data[(string) $period] = compact('income', 'expense', 'net');
            $incomeSeries[] = round($income, 2);
            $expenseSeries[] = round($expense, 2);
            $netSeries[] = round($cumulativeNet, 2);

            if ($view === 'daily') {
                $labels[] = Carbon::parse($period)->format('d/m');
            } else {
                $labels[] = 'T'.(int) $period;
            }
        }

        $totalIncome = array_sum(array_column($data, 'income'));
        $totalExpense = array_sum(array_column($data, 'expense'));

        return compact(
            'data', 'labels', 'incomeSeries', 'expenseSeries', 'netSeries',
            'totalIncome', 'totalExpense', 'transferVolume', 'cumulativeNet', 'view'
        );
    }

    // ─── NEW: Category Trend Report ─────────────────────────────────

    public function trend(Request $request): View
    {
        $home = $this->resolveHome($request);
        $homes = $this->userHomes($request);
        $year = (int) $request->get('year', now()->year);
        $type = $request->get('type', 'expense'); // expense | income

        $report = $home ? $this->buildTrendReport($home->id, $year, $type) : null;

        return view('expense::report.trend', compact(
            'homes', 'home', 'report', 'year', 'type'
        ));
    }

    private function buildTrendReport(int $homeId, int $year, string $type): array
    {
        $debtCatIds = ExpenseCategory::where('home_id', $homeId)
            ->whereNull('deleted_at')
            ->whereIn('category_group', ExpenseCategory::DEBT_GROUPS)
            ->pluck('id');

        // Top 8 categories for the year
        $topCategories = Expense::where('home_id', $homeId)
            ->where('type', $type)
            ->whereNull('transfer_id')
            ->whereNotIn('category_id', $debtCatIds)
            ->whereYear('occurred_at', $year)
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(8)
            ->pluck('category_id');

        $categories = ExpenseCategory::whereIn('id', $topCategories)->get()->keyBy('id');

        // Monthly breakdown per category
        $rows = Expense::where('home_id', $homeId)
            ->where('type', $type)
            ->whereNull('transfer_id')
            ->whereNotIn('category_id', $debtCatIds)
            ->whereIn('category_id', $topCategories)
            ->whereYear('occurred_at', $year)
            ->selectRaw('MONTH(occurred_at) as month, category_id, SUM(amount) as total')
            ->groupByRaw('MONTH(occurred_at), category_id')
            ->get();

        $datasets = [];
        $monthlyTotals = array_fill(1, 12, 0);

        foreach ($topCategories as $catId) {
            $cat = $categories[$catId] ?? null;
            $datasets[$catId] = [
                'label' => $cat?->name ?? 'Unknown',
                'color' => $cat?->color ?? '#64748b',
                'icon' => $cat?->icon ?? '📝',
                'data' => array_fill(1, 12, 0),
            ];
        }

        foreach ($rows as $row) {
            $month = (int) $row->month;
            $datasets[$row->category_id]['data'][$month] = round((float) $row->total, 2);
            $monthlyTotals[$month] += (float) $row->total;
        }

        return [
            'datasets' => $datasets,
            'monthly_totals' => array_map(fn ($v) => round($v, 2), $monthlyTotals),
            'labels' => array_map(fn ($m) => 'T'.$m, range(1, 12)),
            'year_total' => round(array_sum($monthlyTotals), 2),
        ];
    }

    // ─── NEW: Year Comparison Report ─────────────────────────────────

    public function yearComparison(Request $request): View
    {
        $home = $this->resolveHome($request);
        $homes = $this->userHomes($request);

        $years = [];
        $currentYear = (int) ($request->get('end_year', now()->year));
        for ($i = 2; $i >= 0; $i--) {
            $years[] = $currentYear - $i;
        }

        $report = $home ? $this->buildYearComparison($home->id, $years) : null;

        return view('expense::report.year-comparison', compact(
            'homes', 'home', 'report', 'years'
        ));
    }

    private function buildYearComparison(int $homeId, array $years): array
    {
        $debtCatIds = ExpenseCategory::where('home_id', $homeId)
            ->whereNull('deleted_at')
            ->whereIn('category_group', ExpenseCategory::DEBT_GROUPS)
            ->pluck('id');

        $result = [];
        foreach ($years as $year) {
            $rows = Expense::where('home_id', $homeId)
                ->whereNull('transfer_id')
                ->whereNotIn('category_id', $debtCatIds)
                ->whereYear('occurred_at', $year)
                ->selectRaw('MONTH(occurred_at) as month, type, SUM(amount) as total')
                ->groupByRaw('MONTH(occurred_at), type')
                ->get();

            $yearData = array_fill(1, 12, ['income' => 0, 'expense' => 0]);
            foreach ($rows as $row) {
                $yearData[(int) $row->month][$row->type] = round((float) $row->total, 2);
            }

            $annualIncome = array_sum(array_column($yearData, 'income'));
            $annualExpense = array_sum(array_column($yearData, 'expense'));

            $result[$year] = [
                'monthly' => $yearData,
                'total_income' => round($annualIncome, 2),
                'total_expense' => round($annualExpense, 2),
                'net' => round($annualIncome - $annualExpense, 2),
            ];
        }

        return [
            'years' => $result,
            'labels' => array_map(fn ($m) => 'T'.$m, range(1, 12)),
        ];
    }

    // ─── NEW: Net Worth Report ──────────────────────────────────────

    public function networth(Request $request): View
    {
        $home = $this->resolveHome($request);
        $homes = $this->userHomes($request);
        $months = (int) $request->get('months', 12);

        $report = $home ? $this->buildNetworthReport($home->id, $months) : null;

        return view('expense::report.networth', compact(
            'homes', 'home', 'report', 'months'
        ));
    }

    private function buildNetworthReport(int $homeId, int $months): array
    {
        $labels = [];
        $balanceSeries = [];
        $cumulativeIncome = [];
        $cumulativeExpense = [];

        $wallets = Wallet::where('home_id', $homeId)->where('is_archived', false)->get();

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i)->startOfMonth();
            $endOfMonth = $month->copy()->endOfMonth();

            $labels[] = $month->format('m/Y');

            // Total wallet balances at end of month (approximate)
            $income = (float) Expense::where('home_id', $homeId)
                ->where('type', 'income')
                ->whereNull('transfer_id')
                ->where('occurred_at', '<=', $endOfMonth)
                ->sum('amount');

            $expense = (float) Expense::where('home_id', $homeId)
                ->where('type', 'expense')
                ->whereNull('transfer_id')
                ->where('occurred_at', '<=', $endOfMonth)
                ->sum('amount');

            $openingBalance = (float) $wallets->sum('opening_balance');
            $netWorth = $openingBalance + $income - $expense;

            $balanceSeries[] = round($netWorth, 2);
            $cumulativeIncome[] = round($income, 2);
            $cumulativeExpense[] = round($expense, 2);
        }

        $latestBalance = end($balanceSeries) ?: 0;
        $firstBalance = $balanceSeries[0] ?? 0;
        $change = $latestBalance - $firstBalance;
        $changePct = $firstBalance != 0 ? round(($change / abs($firstBalance)) * 100, 1) : 0;

        return compact(
            'labels', 'balanceSeries', 'cumulativeIncome', 'cumulativeExpense',
            'latestBalance', 'change', 'changePct'
        );
    }

    // ─── NEW: Summary Dashboard Report ──────────────────────────────

    public function summary(Request $request): View
    {
        $home = $this->resolveHome($request);
        $homes = $this->userHomes($request);
        $year = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);

        $report = $home ? $this->buildSummaryReport($home->id, $year, $month) : null;

        return view('expense::report.summary', compact(
            'homes', 'home', 'report', 'year', 'month'
        ));
    }

    private function buildSummaryReport(int $homeId, int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $debtCatIds = ExpenseCategory::where('home_id', $homeId)
            ->whereNull('deleted_at')
            ->whereIn('category_group', ExpenseCategory::DEBT_GROUPS)
            ->pluck('id');

        // Income breakdown
        $incomeByCategory = Expense::where('home_id', $homeId)
            ->where('type', 'income')
            ->whereNull('transfer_id')
            ->whereNotIn('category_id', $debtCatIds)
            ->whereBetween('occurred_at', [$start, $end])
            ->selectRaw('category_id, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->with('category')
            ->get();

        // Expense breakdown
        $expenseByCategory = Expense::where('home_id', $homeId)
            ->where('type', 'expense')
            ->whereNull('transfer_id')
            ->whereNotIn('category_id', $debtCatIds)
            ->whereBetween('occurred_at', [$start, $end])
            ->selectRaw('category_id, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->with('category')
            ->get();

        // Debt flows
        $debtGiven = (float) Expense::where('home_id', $homeId)
            ->where('type', 'expense')
            ->whereNull('transfer_id')
            ->whereIn('category_id', $debtCatIds)
            ->whereBetween('occurred_at', [$start, $end])
            ->sum('amount');

        $debtReceived = (float) Expense::where('home_id', $homeId)
            ->where('type', 'income')
            ->whereNull('transfer_id')
            ->whereIn('category_id', $debtCatIds)
            ->whereBetween('occurred_at', [$start, $end])
            ->sum('amount');

        // Transfers
        $transferVolume = (float) Transfer::where('home_id', $homeId)
            ->whereBetween('occurred_at', [$start, $end])
            ->sum('amount');

        // Top transactions
        $topExpenses = Expense::where('home_id', $homeId)
            ->where('type', 'expense')
            ->whereNull('transfer_id')
            ->whereBetween('occurred_at', [$start, $end])
            ->with('category')
            ->orderByDesc('amount')
            ->limit(10)
            ->get();

        $topIncomes = Expense::where('home_id', $homeId)
            ->where('type', 'income')
            ->whereNull('transfer_id')
            ->whereBetween('occurred_at', [$start, $end])
            ->with('category')
            ->orderByDesc('amount')
            ->limit(10)
            ->get();

        $totalIncome = (float) $incomeByCategory->sum('total');
        $totalExpense = (float) $expenseByCategory->sum('total');
        $walletCount = Wallet::where('home_id', $homeId)->where('is_archived', false)->count();
        $totalBalance = (float) Wallet::where('home_id', $homeId)->where('is_archived', false)->get()->sum(fn ($w) => $w->netBalance());

        // Day-by-day breakdown
        $daysInMonth = $start->daysInMonth;
        $dailyRows = Expense::where('home_id', $homeId)
            ->whereNull('transfer_id')
            ->whereNotIn('category_id', $debtCatIds)
            ->whereBetween('occurred_at', [$start, $end])
            ->selectRaw('DAY(occurred_at) as day, type, SUM(amount) as total')
            ->groupByRaw('DAY(occurred_at), type')
            ->get()
            ->groupBy('day');

        $dailyData = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dayRows = $dailyData[$d] ?? collect();
            $dailyData[$d] = [
                'income' => round((float) ($dailyRows[$d]->where('type', 'income')->sum('total') ?? 0), 2),
                'expense' => round((float) ($dailyRows[$d]->where('type', 'expense')->sum('total') ?? 0), 2),
            ];
        }

        return compact(
            'incomeByCategory', 'expenseByCategory', 'topExpenses', 'topIncomes',
            'totalIncome', 'totalExpense', 'debtGiven', 'debtReceived',
            'transferVolume', 'walletCount', 'totalBalance', 'dailyData',
            'daysInMonth', 'start', 'end'
        );
    }

    // ─── EXPORT: PDF ────────────────────────────────────────────────

    public function exportPdf(Request $request): Response
    {
        $home = $this->resolveHome($request);
        if (! $home) {
            abort(404);
        }

        $type = $request->get('type', 'summary');
        $year = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);

        $report = $this->buildSummaryReport($home->id, $year, $month);
        $categoryReport = $this->buildCategoryReport(
            $home->id,
            Carbon::create($year, $month, 1)->startOfMonth()->toDateString(),
            Carbon::create($year, $month, 1)->endOfMonth()->toDateString()
        );

        $pdf = Pdf::loadView('expense::report.pdf.summary', [
            'home' => $home,
            'report' => $report,
            'categoryReport' => $categoryReport,
            'year' => $year,
            'month' => $month,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);

        $pdf->setPaper('a4');
        $pdf->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->download(
            sprintf('homewatt-bao-cao-%s-%04d-%02d.pdf', $home->name, $year, $month)
        );
    }

    public function exportPdfForm(Request $request): View
    {
        $home = $this->resolveHome($request);
        $homes = $this->userHomes($request);

        return view('expense::report.pdf-form', compact('homes', 'home'));
    }

    // ─── EXPORT: Excel ──────────────────────────────────────────────

    public function exportExcel(Request $request)
    {
        $home = $this->resolveHome($request);
        if (! $home) {
            abort(404);
        }

        $year = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);

        return Excel::download(
            new ExpenseReportExport($home->id, $year, $month),
            sprintf('homewatt-bao-cao-%s-%04d-%02d.xlsx', $home->name, $year, $month)
        );
    }

    // ─── Private Builders (reused from original) ────────────────────

    private function buildMonthlyReport(int $homeId, int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $debtCategories = DB::table('expense_categories')
            ->where('home_id', $homeId)
            ->whereNull('deleted_at')
            ->whereIn('category_group', ExpenseCategory::DEBT_GROUPS)
            ->get();
        $debtCategoryIds = $debtCategories->pluck('id')->toArray();

        $lentCategoryIds = $debtCategories->where('category_group', ExpenseCategory::GROUP_LENDING)->pluck('id')->toArray();
        $totalLent = ! empty($lentCategoryIds) ? (float) Expense::where('home_id', $homeId)->whereIn('category_id', $lentCategoryIds)->whereBetween('occurred_at', [$start, $end])->sum('amount') : 0.0;
        $collectedCategoryIds = $debtCategories->where('category_group', ExpenseCategory::GROUP_DEBT_COLLECTION)->pluck('id')->toArray();
        $totalCollected = ! empty($collectedCategoryIds) ? (float) Expense::where('home_id', $homeId)->whereIn('category_id', $collectedCategoryIds)->whereBetween('occurred_at', [$start, $end])->sum('amount') : 0.0;
        $borrowedCategoryIds = $debtCategories->where('category_group', ExpenseCategory::GROUP_BORROWING)->pluck('id')->toArray();
        $totalBorrowed = ! empty($borrowedCategoryIds) ? (float) Expense::where('home_id', $homeId)->whereIn('category_id', $borrowedCategoryIds)->whereBetween('occurred_at', [$start, $end])->sum('amount') : 0.0;
        $repaidCategoryIds = $debtCategories->where('category_group', ExpenseCategory::GROUP_DEBT_REPAYMENT)->pluck('id')->toArray();
        $totalRepaid = ! empty($repaidCategoryIds) ? (float) Expense::where('home_id', $homeId)->whereIn('category_id', $repaidCategoryIds)->whereBetween('occurred_at', [$start, $end])->sum('amount') : 0.0;

        $income = (float) Expense::where('home_id', $homeId)->where('type', Expense::TYPE_INCOME)->whereNull('transfer_id')->whereNotIn('category_id', $debtCategoryIds)->whereBetween('occurred_at', [$start, $end])->sum('amount');
        $expense = (float) Expense::where('home_id', $homeId)->where('type', Expense::TYPE_EXPENSE)->whereNull('transfer_id')->whereNotIn('category_id', $debtCategoryIds)->whereBetween('occurred_at', [$start, $end])->sum('amount');

        $transferIn = (float) Transfer::where('home_id', $homeId)->whereBetween('occurred_at', [$start, $end])->sum('amount');

        $wallets = Wallet::where('home_id', $homeId)->where('is_archived', false)->get();
        $totalBalance = (float) $wallets->sum(fn ($w) => $w->netBalance());

        $dailyRows = Expense::where('home_id', $homeId)->whereNull('transfer_id')->whereNotIn('category_id', $debtCategoryIds)->whereBetween('occurred_at', [$start, $end])->selectRaw('DATE(occurred_at) as date, type, SUM(amount) as total')->groupByRaw('DATE(occurred_at), type')->get();

        $dailyMap = [];
        foreach ($dailyRows as $row) {
            $key = $row->date;
            if (! isset($dailyMap[$key])) {
                $dailyMap[$key] = ['income' => 0.0, 'expense' => 0.0];
            }
            $dailyMap[$key][$row->type] = (float) $row->total;
        }

        $daysInMonth = $start->daysInMonth;
        $dailyData = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = $start->copy()->day($d)->toDateString();
            $dailyData[] = ['date' => $date, 'income' => $dailyMap[$date]['income'] ?? 0.0, 'expense' => $dailyMap[$date]['expense'] ?? 0.0];
        }

        return [
            'income' => $income, 'expense' => $expense, 'net' => $income - $expense,
            'transfer_volume' => $transferIn, 'total_balance' => $totalBalance,
            'wallet_count' => $wallets->count(), 'daily' => $dailyData,
            'period' => $start->format('Y-m'),
            'total_lent' => $totalLent, 'total_collected' => $totalCollected,
            'total_borrowed' => $totalBorrowed, 'total_repaid' => $totalRepaid,
            'debt_active' => ($totalLent > 0 || $totalCollected > 0 || $totalBorrowed > 0 || $totalRepaid > 0),
        ];
    }

    private function buildCategoryReport(int $homeId, string $from, string $to): array
    {
        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();

        $rows = DB::table('expenses')
            ->where('expenses.home_id', $homeId)
            ->whereNull('expenses.deleted_at')
            ->whereNull('transfer_id')
            ->whereBetween('occurred_at', [$start, $end])
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.category_id')
            ->selectRaw('expenses.category_id, expense_categories.name, expense_categories.type, expense_categories.icon, expense_categories.color, SUM(expenses.amount) as total, COUNT(*) as count')
            ->groupBy('expenses.category_id', 'expense_categories.name', 'expense_categories.type', 'expense_categories.icon', 'expense_categories.color')
            ->orderByDesc('total')
            ->get();

        return ['from' => $from, 'to' => $to, 'rows' => $rows];
    }
}
