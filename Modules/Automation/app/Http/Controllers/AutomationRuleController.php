<?php

namespace Modules\Automation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Automation\Models\AutomationRule;
use Modules\Automation\Services\AutomationEngine;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Home\Models\Home;

class AutomationRuleController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->user()->id;
        $homes = Home::whereHas('members', fn ($q) => $q->where('user_id', $userId)
            ->whereIn('role', ['owner', 'manager']))->get();
        $selectedHomeId = $request->get('home_id', $homes->first()?->id);

        $rules = collect();
        if ($selectedHomeId) {
            $rules = AutomationRule::where('home_id', $selectedHomeId)
                ->with('user')
                ->orderBy('priority')
                ->orderBy('name')
                ->get();
        }

        return view('automation::index', compact('homes', 'selectedHomeId', 'rules'));
    }

    public function create(Request $request): View
    {
        $homes = $request->user()->homeMembers()
            ->with('home')
            ->whereIn('role', ['owner', 'manager'])
            ->get()
            ->pluck('home');

        $events = AutomationEngine::EVENTS;
        $actionTypes = AutomationEngine::ACTION_TYPES;

        $selectedHomeId = $request->get('home_id');

        // Load categories for category-related actions
        $categories = $selectedHomeId
            ? ExpenseCategory::where('home_id', $selectedHomeId)->orderBy('name')->get()
            : collect();

        return view('automation::create', compact('homes', 'events', 'actionTypes', 'categories', 'selectedHomeId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'home_id' => 'required|exists:homes,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'trigger_event' => 'required|string|in:'.implode(',', array_keys(AutomationEngine::EVENTS)),
            'is_active' => 'boolean',
            'priority' => 'nullable|integer|min:0|max:999',
            'conditions' => 'nullable|json',
            'actions' => 'required|json',
        ]);

        // Verify home membership
        $home = Home::findOrFail($validated['home_id']);
        $member = $home->member($request->user()->id);
        if (! $member || ! $member->canEdit()) {
            abort(403);
        }

        AutomationRule::create([
            ...$validated,
            'user_id' => $request->user()->id,
            'is_active' => $validated['is_active'] ?? true,
            'priority' => $validated['priority'] ?? 0,
            'conditions' => json_decode($validated['conditions'], true) ?? [],
            'actions' => json_decode($validated['actions'], true) ?? [],
        ]);

        return redirect()->route('automation.index', ['home_id' => $validated['home_id']])
            ->with('success', 'Quy tắc tự động đã được tạo.');
    }

    public function edit(AutomationRule $rule, Request $request): View
    {
        $user = $request->user();
        if (! $rule->home?->isMember($user->id)) {
            abort(403);
        }

        $events = AutomationEngine::EVENTS;
        $actionTypes = AutomationEngine::ACTION_TYPES;
        $categories = ExpenseCategory::where('home_id', $rule->home_id)->orderBy('name')->get();

        return view('automation::edit', compact('rule', 'events', 'actionTypes', 'categories'));
    }

    public function update(AutomationRule $rule, Request $request): RedirectResponse
    {
        if (! $rule->home?->isMember($request->user()->id)) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'trigger_event' => 'required|string|in:'.implode(',', array_keys(AutomationEngine::EVENTS)),
            'is_active' => 'boolean',
            'priority' => 'nullable|integer|min:0|max:999',
            'conditions' => 'nullable|json',
            'actions' => 'required|json',
        ]);

        $rule->update([
            ...$validated,
            'is_active' => $validated['is_active'] ?? true,
            'priority' => $validated['priority'] ?? 0,
            'conditions' => json_decode($validated['conditions'], true) ?? [],
            'actions' => json_decode($validated['actions'], true) ?? [],
        ]);

        return redirect()->route('automation.index', ['home_id' => $rule->home_id])
            ->with('success', 'Quy tắc tự động đã được cập nhật.');
    }

    public function destroy(AutomationRule $rule, Request $request): RedirectResponse
    {
        if (! $rule->home?->isMember($request->user()->id)) {
            abort(403);
        }

        $homeId = $rule->home_id;
        $rule->delete();

        return redirect()->route('automation.index', ['home_id' => $homeId])
            ->with('success', 'Quy tắc đã bị xóa.');
    }

    public function toggle(AutomationRule $rule, Request $request): RedirectResponse
    {
        if (! $rule->home?->isMember($request->user()->id)) {
            abort(403);
        }

        $rule->forceFill(['is_active' => ! $rule->is_active])->save();

        return back()->with('success', $rule->is_active ? 'Quy tắc đã được kích hoạt.' : 'Quy tắc đã bị tạm dừng.');
    }

    public function logs(AutomationRule $rule, Request $request): View
    {
        if (! $rule->home?->isMember($request->user()->id)) {
            abort(403);
        }

        $logs = $rule->logs()->latest('executed_at')->paginate(30);

        return view('automation::logs', compact('rule', 'logs'));
    }
}
