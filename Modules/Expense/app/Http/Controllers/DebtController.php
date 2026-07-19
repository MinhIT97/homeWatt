<?php

namespace Modules\Expense\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Expense\Models\ExpenseSplit;
use Modules\Expense\Services\ExpenseSplitService;
use Modules\Home\Models\Home;

class DebtController extends Controller
{
    public function __construct(
        private readonly ExpenseSplitService $splitService
    ) {}

    /**
     * Display debts overview for the authenticated user.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $homes = Home::whereHas('members', fn ($q) => $q->where('user_id', $user->id))->get();

        $selectedHomeId = (int) ($request->get('home_id') ?: ($homes->first()?->id ?? 0));

        $debts = [];
        $summary = [];
        $homeMembers = collect();

        if ($selectedHomeId) {
            $debts = $this->splitService->getDebts($selectedHomeId, $user);
            $summary = $this->splitService->getHomeSummary($selectedHomeId);

            $home = Home::with(['members.user'])->find($selectedHomeId);
            $homeMembers = $home ? $home->members : collect();
        }

        // Calculate net balance for current user
        $owesTotal = (float) $debts['owes']->sum(fn (ExpenseSplit $s) => $s->remaining());
        $owedToYouTotal = (float) $debts['owed_to_you']->sum(fn (ExpenseSplit $s) => $s->remaining());
        $netBalance = $owedToYouTotal - $owesTotal;

        return view('expense::debts.index', compact(
            'homes',
            'selectedHomeId',
            'debts',
            'summary',
            'homeMembers',
            'owesTotal',
            'owedToYouTotal',
            'netBalance',
        ));
    }

    /**
     * Mark a debt split as settled.
     */
    public function settle(ExpenseSplit $split, Request $request): RedirectResponse
    {
        $user = $request->user();

        // Only the payer (creditor) or the ower (debtor) can settle
        if ((int) $split->paid_by !== (int) $user->id && (int) $split->owed_by !== (int) $user->id) {
            abort(403, 'You are not part of this debt.');
        }

        $this->splitService->settle($split);

        return redirect()->route('debts.index', ['home_id' => $split->home_id])
            ->with('success', __('expense.debt_settled'));
    }
}
