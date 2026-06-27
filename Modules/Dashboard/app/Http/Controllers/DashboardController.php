<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Device\Models\Device;
use Modules\Energy\Models\MonthlyEnergySummary;
use Modules\Energy\Services\SavingSuggestion;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Home\Models\Home;
use Modules\Wallet\Models\Wallet;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $homes = Home::whereHas('members', fn ($q) => $q->where('user_id', $user->id))->get();
        $selectedHomeId = $request->get('home_id', $homes->first()?->id);

        $stats = [
            'total_homes' => $homes->count(),
            'total_devices' => 0,
            'total_rooms' => 0,
            'estimated_monthly_kwh' => 0.0,
            'estimated_monthly_cost' => 0.0,
            'measured_ratio' => 0.0,
            'today_kwh' => 0.0,
            'pct_vs_yesterday' => null,
            'pct_vs_last_month' => null,
            'total_balance' => 0.0,
            'wallet_count' => 0,
            'month_income' => 0.0,
            'month_expense' => 0.0,
        ];

        $topDevices = collect();
        $roomsData = collect();
        $dailyLabels = [];
        $dailyData = [];
        $lastMonthDailyData = [];
        $suggestions = [];

        if ($selectedHomeId) {
            $home = Home::find($selectedHomeId);
            if ($home && $home->members()->where('user_id', $user->id)->exists()) {
                // Financial overview
                $now = Carbon::now();
                $monthStart = $now->copy()->startOfMonth();
                $monthEnd = $now->copy()->endOfMonth();

                $walletsList = Wallet::where('home_id', $home->id)
                    ->where('is_archived', false)
                    ->get();

                $stats['wallet_count'] = $walletsList->count();
                $stats['total_balance'] = (float) $walletsList->sum(fn ($w) => $w->netBalance());

                $debtCategoryIds = DB::table('expense_categories')
                    ->where('home_id', $home->id)
                    ->whereIn('category_group', ExpenseCategory::DEBT_GROUPS)
                    ->pluck('id');

                $monthlyIncome = DB::table('expenses')
                    ->where('home_id', $home->id)
                    ->whereNull('transfer_id')
                    ->where('type', 'income')
                    ->whereNotIn('category_id', $debtCategoryIds)
                    ->whereBetween('occurred_at', [$monthStart, $monthEnd])
                    ->sum('amount');
                $monthlyExpense = DB::table('expenses')
                    ->where('home_id', $home->id)
                    ->whereNull('transfer_id')
                    ->where('type', 'expense')
                    ->whereNotIn('category_id', $debtCategoryIds)
                    ->whereBetween('occurred_at', [$monthStart, $monthEnd])
                    ->sum('amount');

                $stats['month_income'] = (float) $monthlyIncome;
                $stats['month_expense'] = (float) $monthlyExpense;
                $now = Carbon::now();
                $lastMonth = $now->copy()->subMonth();
                $cacheKey = "dashboard:home:{$home->id}:".$now->format('Ym');

                $payload = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($home, $now, $lastMonth) {
                    $deviceIds = DB::table('devices')
                        ->join('rooms', 'rooms.id', '=', 'devices.room_id')
                        ->where('rooms.home_id', $home->id)
                        ->pluck('devices.id');

                    $deviceIdsArr = $deviceIds->all();
                    $deviceCount = count($deviceIdsArr);

                    $roomCount = DB::table('rooms')->where('home_id', $home->id)->count();

                    $currentSummary = DB::table('monthly_energy_summaries')
                        ->where('home_id', $home->id)
                        ->where('year', $now->year)
                        ->where('month', $now->month)
                        ->selectRaw('COALESCE(SUM(total_kwh), 0) as total_kwh, COALESCE(SUM(estimated_cost), 0) as total_cost, SUM(CASE WHEN reading_count > 0 THEN 1 ELSE 0 END) as measured_count, COUNT(*) as total_count')
                        ->first();

                    $lastMonthKwh = DB::table('monthly_energy_summaries')
                        ->where('home_id', $home->id)
                        ->where('year', $lastMonth->year)
                        ->where('month', $lastMonth->month)
                        ->sum('total_kwh');

                    $topDevicesRows = DB::table('monthly_energy_summaries')
                        ->where('home_id', $home->id)
                        ->where('year', $now->year)
                        ->where('month', $now->month)
                        ->orderByDesc('total_kwh')
                        ->limit(5)
                        ->get();

                    $daysInMonth = $now->daysInMonth;
                    $lastMonthDays = $lastMonth->daysInMonth;

                    // Aggregate all 7 days readings in one query
                    $startDate = $now->copy()->subDays(6)->startOfDay();
                    $endDate = $now->copy()->endOfDay();

                    $dailyReadings = DB::table('energy_readings')
                        ->whereIn('device_id', $deviceIdsArr)
                        ->whereBetween('recorded_at', [$startDate, $endDate])
                        ->selectRaw('DATE(recorded_at) as day, SUM(kwh) as total_kwh')
                        ->groupBy(DB::raw('DATE(recorded_at)'))
                        ->pluck('total_kwh', 'day');

                    $lastStartDate = $startDate->copy()->subMonth();
                    $lastEndDate = $endDate->copy()->subMonth();

                    $lastMonthDailyReadings = DB::table('energy_readings')
                        ->whereIn('device_id', $deviceIdsArr)
                        ->whereBetween('recorded_at', [$lastStartDate, $lastEndDate])
                        ->selectRaw('DATE(recorded_at) as day, SUM(kwh) as total_kwh')
                        ->groupBy(DB::raw('DATE(recorded_at)'))
                        ->pluck('total_kwh', 'day');

                    $dailyLabels = [];
                    $dailyData = [];
                    $lastMonthDailyData = [];
                    $fallbackDaily = $daysInMonth > 0 && $currentSummary->total_kwh > 0
                        ? $currentSummary->total_kwh / $daysInMonth
                        : 0;
                    $fallbackLastDaily = $lastMonthDays > 0 && $lastMonthKwh > 0
                        ? $lastMonthKwh / $lastMonthDays
                        : 0;

                    for ($i = 6; $i >= 0; $i--) {
                        $date = $now->copy()->subDays($i);
                        $dailyLabels[] = $date->format('d/m');

                        $dayKey = $date->format('Y-m-d');
                        $dayKwh = (float) ($dailyReadings[$dayKey] ?? 0);
                        if ($dayKwh <= 0 && $fallbackDaily > 0) {
                            $dayKwh = $fallbackDaily;
                        }
                        $dailyData[] = round($dayKwh, 2);

                        $lastDate = $date->copy()->subMonth();
                        $lastKey = $lastDate->format('Y-m-d');
                        $lastDay = (float) ($lastMonthDailyReadings[$lastKey] ?? 0);
                        if ($lastDay <= 0 && $fallbackLastDaily > 0) {
                            $lastDay = $fallbackLastDaily;
                        }
                        $lastMonthDailyData[] = round($lastDay, 2);
                    }

                    $todayKwh = (float) ($dailyReadings[$now->format('Y-m-d')] ?? 0);
                    if ($todayKwh <= 0 && $fallbackDaily > 0) {
                        $todayKwh = $fallbackDaily;
                    }

                    $yesterdayKwh = (float) ($dailyReadings[$now->copy()->subDay()->format('Y-m-d')] ?? 0);
                    if ($yesterdayKwh <= 0 && $fallbackDaily > 0) {
                        $yesterdayKwh = $fallbackDaily;
                    }

                    $pctYesterday = $yesterdayKwh > 0
                        ? round((($todayKwh - $yesterdayKwh) / $yesterdayKwh) * 100)
                        : null;

                    $pctLastMonth = $lastMonthKwh > 0
                        ? round((($currentSummary->total_kwh - $lastMonthKwh) / $lastMonthKwh) * 100)
                        : null;

                    $measuredRatio = $currentSummary->total_count > 0
                        ? $currentSummary->measured_count / $currentSummary->total_count
                        : 0;

                    return [
                        'room_count' => $roomCount,
                        'device_count' => $deviceCount,
                        'device_ids' => $deviceIdsArr,
                        'estimated_monthly_kwh' => (float) $currentSummary->total_kwh,
                        'estimated_monthly_cost' => (float) $currentSummary->total_cost,
                        'measured_ratio' => $measuredRatio,
                        'today_kwh' => $todayKwh,
                        'pct_vs_yesterday' => $pctYesterday,
                        'pct_vs_last_month' => $pctLastMonth,
                        'top_devices' => $topDevicesRows->map(fn($row) => (array) $row)->all(),
                        'daily_labels' => $dailyLabels,
                        'daily_data' => $dailyData,
                        'last_month_daily_data' => $lastMonthDailyData,
                    ];
                });

                $stats['total_rooms'] = $payload['room_count'];
                $stats['total_devices'] = $payload['device_count'];
                $stats['estimated_monthly_kwh'] = $payload['estimated_monthly_kwh'];
                $stats['estimated_monthly_cost'] = $payload['estimated_monthly_cost'];
                $stats['measured_ratio'] = $payload['measured_ratio'];
                $stats['today_kwh'] = $payload['today_kwh'];
                $stats['pct_vs_yesterday'] = $payload['pct_vs_yesterday'];
                $stats['pct_vs_last_month'] = $payload['pct_vs_last_month'];

                $topDevices = collect($payload['top_devices'])->map(fn($row) => (object) $row);
                $dailyLabels = $payload['daily_labels'];
                $dailyData = $payload['daily_data'];
                $lastMonthDailyData = $payload['last_month_daily_data'];

                if (! empty($payload['device_ids'])) {
                    $devices = Device::whereIn('id', $payload['device_ids'])
                        ->with(['specification', 'usageProfile', 'deviceType'])
                        ->get();

                    $suggestions = (new SavingSuggestion)->analyze($devices, $stats['estimated_monthly_cost']);
                }
            }
        }

        return view('dashboard::index', compact(
            'stats', 'homes', 'selectedHomeId', 'topDevices', 'roomsData',
            'dailyLabels', 'dailyData', 'lastMonthDailyData', 'suggestions',
        ));
    }

    public function compare(Request $request): View
    {
        $user = $request->user();
        $homes = Home::whereHas('members', fn ($q) => $q->where('user_id', $user->id))
            ->withCount(['rooms', 'members'])
            ->get();

        $now = Carbon::now();
        $comparisons = [];

        foreach ($homes as $home) {
            $deviceIds = Device::whereHas('room', fn ($q) => $q->where('home_id', $home->id))->pluck('id');
            $deviceCount = $deviceIds->count();

            // Aggregate current + last month summaries in one query
            $summaries = DB::table('monthly_energy_summaries')
                ->where('home_id', $home->id)
                ->where(function ($q) use ($now) {
                    $q->where(function ($q1) use ($now) {
                        $q1->where('year', $now->year)->where('month', $now->month);
                    })->orWhere(function ($q1) use ($now) {
                        $lastMonth = $now->copy()->subMonth();
                        $q1->where('year', $lastMonth->year)->where('month', $lastMonth->month);
                    });
                })
                ->get()
                ->groupBy(fn ($row) => $row->year.'-'.$row->month);

            $currentKey = $now->year.'-'.$now->month;
            $lastKey = $now->copy()->subMonth()->year.'-'.$now->copy()->subMonth()->month;

            $monthlyKwh = (float) ($summaries->get($currentKey)?->sum('total_kwh') ?? 0);
            $monthlyCost = (float) ($summaries->get($currentKey)?->sum('estimated_cost') ?? 0);
            $lastMonthKwh = (float) ($summaries->get($lastKey)?->sum('total_kwh') ?? 0);

            $pctChange = $lastMonthKwh > 0
                ? round((($monthlyKwh - $lastMonthKwh) / $lastMonthKwh) * 100)
                : null;

            $topDevice = MonthlyEnergySummary::where('home_id', $home->id)
                ->where('year', $now->year)
                ->where('month', $now->month)
                ->with('device')
                ->orderByDesc('total_kwh')
                ->first();

            $comparisons[] = [
                'home' => $home,
                'device_count' => $deviceCount,
                'monthly_kwh' => $monthlyKwh,
                'monthly_cost' => $monthlyCost,
                'pct_change' => $pctChange,
                'top_device' => $topDevice,
            ];
        }

        return view('dashboard::compare', compact('comparisons'));
    }
}
