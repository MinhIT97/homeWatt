<?php

namespace Modules\Energy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Device\Models\Device;
use Modules\Energy\Models\EnergyEstimate;
use Modules\Energy\Models\EnergyReading;
use Modules\Energy\Services\EnergyCalculator;
use Modules\Tariff\Models\TariffPlan;

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

        $device = Device::findOrFail($request->device_id);
        $member = $device->room->home->members()->where('user_id', $request->user()->id)->first();
        if (! $member || ! $member->canEdit()) {
            abort(403);
        }

        EnergyReading::create($request->only([
            'device_id', 'recorded_at', 'watts', 'kwh', 'source', 'measurement_type',
        ]));

        return redirect()->route('energy.index')
            ->with('success', 'Reading recorded.');
    }

    public function show(EnergyReading $reading): View
    {
        $reading->load('device.room.home');

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

        $device = Device::findOrFail($request->device_id);
        $tariffPlan = $request->tariff_plan_id ? TariffPlan::find($request->tariff_plan_id) : null;

        $calculator = app(EnergyCalculator::class);
        $estimate = $calculator->estimateMonthly($device, (int) $request->year, (int) $request->month, $tariffPlan);
        $estimate->save();

        return redirect()->route('energy.index')
            ->with('success', "Estimate: {$estimate->estimated_kwh} kWh, {$estimate->estimated_cost} cost.");
    }
}
