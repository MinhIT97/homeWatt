<?php

namespace Modules\Tariff\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Tariff\Models\TariffPlan;
use Modules\Tariff\Models\TariffTier;

class TariffController extends Controller
{
    public function index(): View
    {
        $plans = TariffPlan::with('tiers')->latest()->paginate(20);

        return view('tariff::index', compact('plans'));
    }

    public function create(): View
    {
        $this->authorize('create', TariffPlan::class);

        return view('tariff::create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', TariffPlan::class);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'provider' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:residential,commercial,industrial'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after:effective_from'],
            'status' => ['nullable', 'string', 'in:active,inactive,draft'],
            'tiers' => ['required', 'array', 'min:1'],
            'tiers.*.tier_number' => ['required', 'integer', 'min:1', 'distinct'],
            'tiers.*.limit_kwh' => ['nullable', 'numeric', 'min:0'],
            'tiers.*.rate' => ['required', 'numeric', 'min:0'],
            'tiers.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tiers.*.surcharge' => ['nullable', 'numeric', 'min:0'],
        ]);

        $effectiveFrom = $request->input('effective_from');
        $effectiveTo = $request->input('effective_to') ?? '9999-12-31';
        $region = $request->input('region');
        $type = $request->input('type');

        $overlapQuery = TariffPlan::query()
            ->where('status', '!=', 'inactive')
            ->where(function ($q) use ($effectiveFrom, $effectiveTo) {
                $q->where(function ($inner) use ($effectiveFrom) {
                    $inner->where('effective_from', '<=', $effectiveFrom)
                        ->where(function ($e) use ($effectiveFrom) {
                            $e->whereNull('effective_to')
                                ->orWhere('effective_to', '>=', $effectiveFrom);
                        });
                })->orWhere(function ($inner) use ($effectiveFrom, $effectiveTo) {
                    $inner->where('effective_from', '<=', $effectiveTo)
                        ->where(function ($e) use ($effectiveFrom) {
                            $e->whereNull('effective_to')
                                ->orWhere('effective_to', '>=', $effectiveFrom);
                        });
                })->orWhere(function ($inner) use ($effectiveFrom, $effectiveTo) {
                    $inner->where('effective_from', '>=', $effectiveFrom)
                        ->where('effective_from', '<=', $effectiveTo);
                });
            });

        if ($region) {
            $overlapQuery->where('region', $region);
        }
        if ($type) {
            $overlapQuery->where('type', $type);
        }

        if ($overlapQuery->exists()) {
            return back()->withErrors([
                'effective_from' => __('tariff.overlap_detected'),
            ])->withInput();
        }

        $plan = DB::transaction(function () use ($request) {
            $plan = TariffPlan::create($request->only([
                'name', 'provider', 'region', 'type', 'effective_from', 'effective_to', 'status',
            ]));

            foreach ($request->tiers as $tier) {
                TariffTier::create([...$tier, 'tariff_plan_id' => $plan->id]);
            }

            return $plan;
        });

        AuditLogger::log('tariff.created', [
            'tariff_plan_id' => $plan->id,
            'region' => $plan->region,
            'type' => $plan->type,
            'effective_from' => $plan->effective_from?->toDateString(),
        ]);

        return redirect()->route('tariff.index')->with('success', __('tariff.created'));
    }

    public function show(TariffPlan $tariff): View
    {
        $this->authorize('view', $tariff);

        $tariff->load('tiers');
        $plan = $tariff;

        return view('tariff::show', compact('plan'));
    }

    public function destroy(TariffPlan $tariff): RedirectResponse
    {
        $this->authorize('delete', $tariff);

        $tariffId = $tariff->id;
        $tariffName = $tariff->name;

        DB::transaction(function () use ($tariff) {
            $tariff->tiers()->delete();
            $tariff->delete();
        });

        AuditLogger::log('tariff.deleted', [
            'tariff_plan_id' => $tariffId,
            'name' => $tariffName,
        ]);

        return redirect()->route('tariff.index')->with('success', __('tariff.deleted'));
    }
}
