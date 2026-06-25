<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
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

        try {
            $validated = $request->validate([
                'home_id' => ['required', 'integer'],
                'month' => ['nullable', 'integer', 'min:1', 'max:12'],
                'year' => ['nullable', 'integer', 'min:2020', 'max:2099'],
            ]);
        } catch (ValidationException $e) {
            abort(422, 'Invalid export parameters');
        }

        $homeId = $validated['home_id'];
        $month = $validated['month'] ?? now()->month;
        $year = $validated['year'] ?? now()->year;

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

        try {
            $pdf = Pdf::loadView('dashboard::report', compact(
                'home', 'readings', 'estimates', 'monthlyKwh', 'monthlyCost', 'month', 'year'
            ));

            $safeHomeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $home->name);
            $safeHomeName = mb_substr($safeHomeName ?? 'home', 0, 50);
            $filename = "bao-cao-dien-{$safeHomeName}-{$year}-{$month}.pdf";

            return $pdf->download($filename);
        } catch (\Throwable $e) {
            Log::error('PDF generation failed', [
                'user_id' => $user->id,
                'home_id' => $home->id,
                'error' => $e->getMessage(),
            ]);

            abort(500, 'Report generation failed');
        }
    }
}
