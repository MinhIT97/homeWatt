<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Device\Models\Device;
use Modules\Energy\Models\MonthlyEnergySummary;
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
        ];

        $topDevices = collect();

        if ($selectedHomeId) {
            $home = Home::find($selectedHomeId);
            if ($home && $home->members()->where('user_id', $user->id)->exists()) {
                $now = now();
                $stats['total_rooms'] = $home->rooms()->count();
                $stats['total_devices'] = Device::whereHas('room', fn ($q) => $q->where('home_id', $home->id))->count();

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
            }
        }

        return view('dashboard::index', compact('stats', 'homes', 'selectedHomeId', 'topDevices'));
    }
}
