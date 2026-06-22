<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Device\Models\Device;
use Modules\Energy\Models\EnergyReading;
use Modules\Energy\Models\MonthlyEnergySummary;
use Modules\Energy\Services\SavingSuggestion;
use Modules\Home\Models\Home;

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
        ];

        $topDevices = collect();
        $dailyLabels = [];
        $dailyData = [];
        $lastMonthDailyData = [];
        $suggestions = [];

        if ($selectedHomeId) {
            $home = Home::find($selectedHomeId);
            if ($home && $home->members()->where('user_id', $user->id)->exists()) {
                $now = Carbon::now();
                $deviceIds = Device::whereHas('room', fn ($q) => $q->where('home_id', $home->id))
                    ->pluck('id');

                $stats['total_rooms'] = $home->rooms()->count();
                $stats['total_devices'] = $deviceIds->count();

                $summaries = MonthlyEnergySummary::where('home_id', $home->id)
                    ->where('year', $now->year)
                    ->where('month', $now->month)
                    ->get();

                $stats['estimated_monthly_kwh'] = $summaries->sum('total_kwh');
                $stats['estimated_monthly_cost'] = $summaries->sum('estimated_cost');

                $measuredCount = $summaries->where('reading_count', '>', 0)->count();
                $totalCount = $summaries->count();
                $stats['measured_ratio'] = $totalCount > 0 ? $measuredCount / $totalCount : 0;

                $topDevices = $summaries->sortByDesc('total_kwh')->take(5);

                // Daily chart — this month vs last month
                $dailyLabels = [];
                $dailyData = [];
                $lastMonthDailyData = [];
                $daysInMonth = $now->daysInMonth;
                $lastMonth = $now->copy()->subMonth();

                for ($i = 6; $i >= 0; $i--) {
                    $date = $now->copy()->subDays($i);
                    $dailyLabels[] = $date->format('d/m');

                    $dayKwh = EnergyReading::whereIn('device_id', $deviceIds)
                        ->whereDate('recorded_at', $date)
                        ->sum('kwh');

                    if ($dayKwh <= 0 && $stats['estimated_monthly_kwh'] > 0) {
                        $dayKwh = $stats['estimated_monthly_kwh'] / $daysInMonth;
                    }

                    $dailyData[] = round($dayKwh, 2);

                    // Last month same day-of-week
                    $lastDate = $date->copy()->subMonth();
                    $lastDay = EnergyReading::whereIn('device_id', $deviceIds)
                        ->whereDate('recorded_at', $lastDate)
                        ->sum('kwh');

                    $lmSummaries = MonthlyEnergySummary::where('home_id', $home->id)
                        ->where('year', $lastMonth->year)
                        ->where('month', $lastMonth->month)
                        ->sum('total_kwh');

                    if ($lastDay <= 0 && $lmSummaries > 0) {
                        $lastDay = $lmSummaries / $lastMonth->daysInMonth;
                    }

                    $lastMonthDailyData[] = round($lastDay, 2);
                }

                // Today's kWh
                $todayKwh = EnergyReading::whereIn('device_id', $deviceIds)
                    ->whereDate('recorded_at', $now)
                    ->sum('kwh');

                if ($todayKwh <= 0 && $stats['estimated_monthly_kwh'] > 0) {
                    $todayKwh = $stats['estimated_monthly_kwh'] / $daysInMonth;
                }
                $stats['today_kwh'] = $todayKwh;

                // Yesterday comparison
                $yesterdayKwh = EnergyReading::whereIn('device_id', $deviceIds)
                    ->whereDate('recorded_at', $now->copy()->subDay())
                    ->sum('kwh');

                if ($yesterdayKwh <= 0 && $stats['estimated_monthly_kwh'] > 0) {
                    $yesterdayKwh = $stats['estimated_monthly_kwh'] / $daysInMonth;
                }

                if ($yesterdayKwh > 0) {
                    $stats['pct_vs_yesterday'] = round((($todayKwh - $yesterdayKwh) / $yesterdayKwh) * 100);
                }

                // Last month comparison
                $lastMonthSummaries = MonthlyEnergySummary::where('home_id', $home->id)
                    ->where('year', $lastMonth->year)
                    ->where('month', $lastMonth->month)
                    ->sum('total_kwh');

                if ($lastMonthSummaries > 0) {
                    $stats['pct_vs_last_month'] = round((($stats['estimated_monthly_kwh'] - $lastMonthSummaries) / $lastMonthSummaries) * 100);
                }

                // Saving suggestions
                $devices = Device::whereHas('room', fn ($q) => $q->where('home_id', $home->id))
                    ->with(['specification', 'usageProfile', 'deviceType'])
                    ->get();

                $suggestions = (new SavingSuggestion)->analyze($devices, $stats['estimated_monthly_cost']);
            }
        }

        return view('dashboard::index', compact(
            'stats', 'homes', 'selectedHomeId', 'topDevices',
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

            $monthlyKwh = MonthlyEnergySummary::where('home_id', $home->id)
                ->where('year', $now->year)
                ->where('month', $now->month)
                ->sum('total_kwh');

            $monthlyCost = MonthlyEnergySummary::where('home_id', $home->id)
                ->where('year', $now->year)
                ->where('month', $now->month)
                ->sum('estimated_cost');

            $lastMonthKwh = MonthlyEnergySummary::where('home_id', $home->id)
                ->where('year', $now->copy()->subMonth()->year)
                ->where('month', $now->copy()->subMonth()->month)
                ->sum('total_kwh');

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
