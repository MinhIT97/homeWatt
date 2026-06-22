<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Device\Models\Device;
use Modules\Energy\Models\EnergyEstimate;
use Modules\Energy\Models\EnergyReading;
use Modules\Energy\Models\MonthlyEnergySummary;
use Modules\Home\Models\Home;

class ReportController extends Controller
{
    public function export(Request $request)
    {
        $user = $request->user();
        $homeId = $request->get('home_id');
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $home = Home::findOrFail($homeId);
        if (! $home->members()->where('user_id', $user->id)->exists()) {
            abort(403);
        }

        $deviceIds = Device::whereHas('room', fn ($q) => $q->where('home_id', $home->id))->pluck('id');

        $readings = EnergyReading::whereIn('device_id', $deviceIds)
            ->whereYear('recorded_at', $year)
            ->whereMonth('recorded_at', $month)
            ->with('device')
            ->latest()
            ->get();

        $estimates = EnergyEstimate::whereIn('device_id', $deviceIds)
            ->whereYear('period_start', $year)
            ->whereMonth('period_start', $month)
            ->with('device.tariffPlan')
            ->latest()
            ->get();

        $monthlyKwh = MonthlyEnergySummary::where('home_id', $home->id)
            ->where('year', $year)
            ->where('month', $month)
            ->sum('total_kwh');

        $monthlyCost = MonthlyEnergySummary::where('home_id', $home->id)
            ->where('year', $year)
            ->where('month', $month)
            ->sum('estimated_cost');

        $pdf = Pdf::loadView('dashboard::report', compact(
            'home', 'readings', 'estimates', 'monthlyKwh', 'monthlyCost', 'month', 'year'
        ));

        return $pdf->download("bao-cao-dien-{$home->name}-{$year}-{$month}.pdf");
    }
}
