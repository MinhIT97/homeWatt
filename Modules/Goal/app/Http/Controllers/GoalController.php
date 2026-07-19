<?php

namespace Modules\Goal\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Goal\Models\Goal;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;

class GoalController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $homes = Home::whereHas('members', fn ($q) => $q->where('user_id', $user->id))->get();
        $selectedHomeId = $request->get('home_id', $homes->first()?->id);

        if ($selectedHomeId && $homes->where('id', $selectedHomeId)->isEmpty()) {
            abort(403);
        }

        $goals = collect();
        if ($selectedHomeId) {
            $goals = Goal::where('home_id', $selectedHomeId)
                ->with(['snapshots', 'category', 'wallet'])
                ->latest()
                ->get();
        }

        return view('goal::index', compact('homes', 'selectedHomeId', 'goals'));
    }

    public function create(Request $request): View
    {
        $homes = $request->user()
            ->homeMembers()
            ->with('home')
            ->whereIn('role', ['owner', 'manager'])
            ->get()
            ->pluck('home');

        return view('goal::create', compact('homes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'home_id' => 'required|exists:homes,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:savings,debt_payoff,energy_reduction,expense_limit,income_target',
            'target_amount' => 'required|numeric|min:0',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'icon' => 'nullable|string|max:10',
            'color' => 'nullable|string|max:7',
            'category_id' => ['nullable', Rule::exists('expense_categories', 'id')->where('home_id', $request->input('home_id'))],
            'wallet_id' => ['nullable', Rule::exists('wallets', 'id')->where('home_id', $request->input('home_id'))],
        ]);

        $home = Home::findOrFail($validated['home_id']);
        $member = $home->member($request->user()->id);

        if (! $member || ! $member->canEdit()) {
            abort(403);
        }

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = 'active';

        Goal::create($validated);

        return redirect()->route('goal.index', ['home_id' => $validated['home_id']])
            ->with('success', 'Mục tiêu đã được tạo thành công.');
    }

    public function show(Goal $goal, Request $request): View
    {
        $goal->load(['snapshots' => fn ($q) => $q->orderBy('snapshot_date'), 'category', 'wallet']);

        if (! $goal->home?->isMember($request->user()->id)) {
            abort(403);
        }

        $snapshotDates = $goal->snapshots->pluck('snapshot_date')->map(fn ($d) => $d->format('d/m'))->toArray();
        $snapshotPercentages = $goal->snapshots->pluck('percentage')->toArray();

        return view('goal::show', compact('goal', 'snapshotDates', 'snapshotPercentages'));
    }

    public function edit(Goal $goal, Request $request): View
    {
        if (! $goal->home?->isMember($request->user()->id)) {
            abort(403);
        }

        $homes = $request->user()->homeMembers()
            ->with('home')
            ->whereIn('role', ['owner', 'manager'])
            ->get()
            ->pluck('home');

        return view('goal::edit', compact('goal', 'homes'));
    }

    public function update(Goal $goal, Request $request): RedirectResponse
    {
        if (! $goal->home?->isMember($request->user()->id)) {
            abort(403);
        }

        $validated = $request->validate([
            'home_id' => 'required|exists:homes,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:savings,debt_payoff,energy_reduction,expense_limit,income_target',
            'target_amount' => 'required|numeric|min:0',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'icon' => 'nullable|string|max:10',
            'color' => 'nullable|string|max:7',
            'category_id' => ['nullable', Rule::exists('expense_categories', 'id')->where('home_id', $request->input('home_id'))],
            'wallet_id' => ['nullable', Rule::exists('wallets', 'id')->where('home_id', $request->input('home_id'))],
            'status' => 'nullable|in:active,completed,cancelled',
        ]);

        $goal->update($validated);

        return redirect()->route('goal.index', ['home_id' => $validated['home_id']])
            ->with('success', 'Mục tiêu đã được cập nhật.');
    }

    public function destroy(Goal $goal, Request $request): RedirectResponse
    {
        if (! $goal->home?->isMember($request->user()->id)) {
            abort(403);
        }

        $homeId = $goal->home_id;
        $goal->forceFill(['status' => 'cancelled'])->save();
        $goal->delete();

        return redirect()->route('goal.index', ['home_id' => $homeId])
            ->with('success', 'Mục tiêu đã bị hủy.');
    }
}
