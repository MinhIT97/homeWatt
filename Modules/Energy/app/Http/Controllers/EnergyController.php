<?php

namespace Modules\Energy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Device\Models\Device;
use Modules\Energy\Models\EnergyEstimate;
use Modules\Energy\Models\EnergyReading;
use Modules\Energy\Services\EnergyCalculator;
use Modules\Home\Models\Home;
use Modules\Tariff\Models\TariffPlan;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EnergyController extends Controller
{
    public function index(Request $request): View
    {
        $devices = Device::whereHas('room.home.members', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with(['specification', 'usageProfile', 'room.home'])
            ->get();

        $estimates = EnergyEstimate::whereIn('device_id', $devices->pluck('id'))
            ->with('device')
            ->latest()
            ->paginate(20);

        return view('energy::index', compact('devices', 'estimates'));
    }

    public function create(Request $request): View
    {
        $devices = Device::whereHas('room.home.members', fn ($q) => $q->where('user_id', $request->user()->id)
            ->whereIn('role', ['owner', 'manager']))
            ->with('room.home')
            ->get();

        return view('energy::create', compact('devices'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'device_id' => ['required', 'exists:devices,id'],
            'recorded_at' => ['required', 'date'],
            'watts' => ['nullable', 'numeric', 'min:0'],
            'kwh' => ['nullable', 'numeric', 'min:0'],
            'source' => ['required', 'string', 'in:manual,measured,ai'],
            'measurement_type' => ['nullable', 'string', 'in:instant,cumulative'],
        ]);

        try {
            DB::transaction(function () use ($request) {
                $device = Device::where('id', $request->device_id)
                    ->lockForUpdate()
                    ->with('room.home.members')
                    ->firstOrFail();

                $member = $device->room?->home?->members
                    ->where('user_id', $request->user()->id)
                    ->first();

                if (! $member || ! $member->canEdit()) {
                    abort(403);
                }

                EnergyReading::create($request->only([
                    'device_id', 'recorded_at', 'watts', 'kwh', 'source', 'measurement_type',
                ]));
            });
        } catch (HttpException $e) {
            throw $e;
        }

        return redirect()->route('energy.index')
            ->with('success', __('energy.reading_recorded'));
    }

    public function show(Request $request, EnergyReading $reading): View
    {
        $this->authorize('view', $reading);

        $reading->loadMissing('device.room.home');

        return view('energy::show', compact('reading'));
    }

    public function calculate(Request $request): RedirectResponse
    {
        $request->validate([
            'device_id' => ['required', 'exists:devices,id'],
            'year' => ['required', 'integer', 'min:2020', 'max:2099'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'tariff_plan_id' => ['nullable', 'exists:tariff_plans,id'],
        ]);

        $estimate = DB::transaction(function () use ($request) {
            $device = Device::where('id', $request->device_id)
                ->lockForUpdate()
                ->with('room.home.members')
                ->firstOrFail();

            $member = $device->room?->home?->members
                ->where('user_id', $request->user()->id)
                ->first();

            if (! $member || ! $member->canEdit()) {
                abort(403);
            }

            $tariffPlan = $request->tariff_plan_id
                ? TariffPlan::find($request->tariff_plan_id)
                : null;

            $calculator = app(EnergyCalculator::class);
            $estimate = $calculator->estimateMonthly($device, (int) $request->year, (int) $request->month, $tariffPlan);

            $existing = EnergyEstimate::where('device_id', $device->id)
                ->where('period_type', $estimate->period_type)
                ->where('period_start', $estimate->period_start)
                ->first();

            if ($existing) {
                $existing->update($estimate->getAttributes());

                return $existing->fresh();
            }

            $estimate->save();

            return $estimate;
        });

        return redirect()->route('energy.index')
            ->with('success', __('energy.estimate_result', ['kwh' => $estimate->estimated_kwh, 'cost' => $estimate->estimated_cost]));
    }

    public function tieredReport(Request $request): View
    {
        $validated = $request->validate([
            'home_id' => ['nullable', 'integer'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $user = $request->user();

        $homes = Home::whereHas('members', fn ($q) => $q->where('user_id', $user->id))->get();
        $selectedHomeId = $validated['home_id'] ?? $homes->first()?->id;
        $selectedMonth = $validated['month'] ?? now()->format('Y-m');

        $home = $selectedHomeId ? $homes->firstWhere('id', (int) $selectedHomeId) : null;
        if ($selectedHomeId && ! $home) {
            abort(403);
        }

        $startOfMonth = Carbon::parse($selectedMonth.'-01')->startOfMonth();
        $endOfMonth = Carbon::parse($selectedMonth.'-01')->endOfMonth();

        $devices = $home
            ? Device::whereHas('room', fn ($q) => $q->where('home_id', $home->id))->get()
            : collect();
        $deviceIds = $devices->pluck('id');

        $totalKwh = 0.0;
        if ($deviceIds->isNotEmpty()) {
            $totalKwh = (float) EnergyReading::whereIn('device_id', $deviceIds)
                ->whereBetween('recorded_at', [$startOfMonth, $endOfMonth])
                ->sum('kwh');
        }

        $tariffPlan = TariffPlan::where('status', 'active')->first()
            ?: TariffPlan::orderByDesc('id')->first();

        $tiers = $tariffPlan ? $tariffPlan->tiers()->orderBy('tier_number')->get() : collect();

        $calculation = [];
        $remainingKwh = $totalKwh;
        $totalCost = 0.0;
        $previousLimit = 0.0;

        foreach ($tiers as $index => $tier) {
            $limit = $tier->limit_kwh;

            if (is_null($limit)) {
                $consumedInTier = $remainingKwh;
            } else {
                $tierCap = $limit - $previousLimit;
                $consumedInTier = min($tierCap, $remainingKwh);
                $previousLimit = $limit;
            }

            $consumedInTier = max(0.0, $consumedInTier);
            $remainingKwh -= $consumedInTier;
            $remainingKwh = max(0.0, $remainingKwh);

            $cost = $consumedInTier * $tier->rate;
            $totalCost += $cost;

            $calculation[] = [
                'tier_number' => $tier->tier_number,
                'limit_from' => $index === 0 ? 0 : $tiers[$index - 1]->limit_kwh,
                'limit_to' => $limit,
                'rate' => $tier->rate,
                'consumed' => $consumedInTier,
                'cost' => $cost,
                'percentage' => $totalKwh > 0 ? round(($consumedInTier / $totalKwh) * 100, 1) : 0,
            ];
        }

        $deviceBreakdown = [];
        if ($totalKwh > 0 && $deviceIds->isNotEmpty()) {
            $deviceReadings = EnergyReading::whereIn('device_id', $deviceIds)
                ->whereBetween('recorded_at', [$startOfMonth, $endOfMonth])
                ->select('device_id', DB::raw('SUM(kwh) as total_kwh'))
                ->groupBy('device_id')
                ->get();

            foreach ($deviceReadings as $dr) {
                $dev = $devices->firstWhere('id', $dr->device_id);
                if ($dev) {
                    $devKwh = (float) $dr->total_kwh;
                    $deviceBreakdown[] = [
                        'device' => $dev,
                        'kwh' => $devKwh,
                        'percentage' => round(($devKwh / $totalKwh) * 100, 1),
                        'estimated_cost' => ($totalCost / $totalKwh) * $devKwh,
                    ];
                }
            }

            usort($deviceBreakdown, fn ($a, $b) => $b['kwh'] <=> $a['kwh']);
        }

        return view('energy::tiered_report', compact(
            'homes',
            'selectedHomeId',
            'selectedMonth',
            'totalKwh',
            'calculation',
            'totalCost',
            'deviceBreakdown',
            'tariffPlan'
        ));
    }
}
