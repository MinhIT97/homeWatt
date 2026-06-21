<?php

namespace Modules\Tariff\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Tariff\Models\TariffPlan;
use Modules\Tariff\Models\TariffTier;

class TariffController extends \App\Http\Controllers\Controller
{
    public function index(): View
    {
        $plans = TariffPlan::with('tiers')->latest()->paginate(20);
        return view('tariff::index', compact('plans'));
    }

    public function create(): View
    {
        return view('tariff::create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'provider' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:residential,commercial,industrial'],
            'effective_from' => ['required', 'date'],
            'tiers' => ['required', 'array', 'min:1'],
            'tiers.*.tier_number' => ['required', 'integer', 'min:1'],
            'tiers.*.limit_kwh' => ['nullable', 'numeric', 'min:0'],
            'tiers.*.rate' => ['required', 'numeric', 'min:0'],
            'tiers.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tiers.*.surcharge' => ['nullable', 'numeric', 'min:0'],
        ]);

        $plan = TariffPlan::create($request->only(['name', 'provider', 'region', 'type', 'effective_from']));

        foreach ($request->tiers as $tier) {
            TariffTier::create([...$tier, 'tariff_plan_id' => $plan->id]);
        }

        return redirect()->route('tariff.index')->with('success', 'Tariff plan created.');
    }

    public function show(TariffPlan $plan): View
    {
        $plan->load('tiers');
        return view('tariff::show', compact('plan'));
    }

    public function destroy(TariffPlan $plan): RedirectResponse
    {
        $plan->delete();
        return redirect()->route('tariff.index')->with('success', 'Tariff plan deleted.');
    }
}
