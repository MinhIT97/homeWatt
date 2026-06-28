<?php

namespace Modules\Expense\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Expense\Models\ExpenseBudget;
use Modules\Home\Models\Home;

class BudgetController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $homes = Home::whereHas('members', fn($q) => $q->where('user_id', $user->id))->get();
        
        $selectedHomeId = $request->get('home_id', $homes->first()?->id);
        $selectedMonth = $request->get('month', now()->format('Y-m'));

        $home = Home::findOrFail($selectedHomeId);
        $member = $home->members()->where('user_id', $user->id)->first();
        if (!$member) {
            abort(403);
        }

        // Get all active expense categories
        $categories = ExpenseCategory::all();

        // Get existing budgets for this home and month
        $budgets = ExpenseBudget::where('home_id', $selectedHomeId)
            ->where('month', $selectedMonth)
            ->get();

        // Calculate expenses for each category in this month
        $startOfMonth = Carbon::parse($selectedMonth . '-01')->startOfMonth();
        $endOfMonth = Carbon::parse($selectedMonth . '-01')->endOfMonth();

        $monthlyExpenses = Expense::where('home_id', $selectedHomeId)
            ->where('type', 'expense')
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
            ->get();

        $budgetData = [];
        $totalBudgetLimit = 0;
        $totalBudgetSpending = 0;

        foreach ($categories as $cat) {
            $budget = $budgets->firstWhere('category_id', $cat->id);
            $limit = $budget ? (float) $budget->amount : 0;
            $spending = (float) $monthlyExpenses->where('category_id', $cat->id)->sum('amount');

            if ($limit > 0) {
                $totalBudgetLimit += $limit;
                $totalBudgetSpending += $spending;
            }

            $budgetData[] = [
                'category' => $cat,
                'limit' => $limit,
                'spending' => $spending,
                'percentage' => $limit > 0 ? min(100, round(($spending / $limit) * 100, 1)) : 0,
                'raw_percentage' => $limit > 0 ? ($spending / $limit) * 100 : 0,
                'budget_id' => $budget?->id,
            ];
        }

        // Global/Total budget limit (if any is defined with category_id = null)
        $globalBudget = $budgets->firstWhere('category_id', null);
        $globalLimit = $globalBudget ? (float) $globalBudget->amount : 0;
        $globalSpending = (float) $monthlyExpenses->sum('amount');

        return view('expense::budgets.index', compact(
            'homes',
            'selectedHomeId',
            'selectedMonth',
            'budgetData',
            'totalBudgetLimit',
            'totalBudgetSpending',
            'globalLimit',
            'globalSpending',
            'globalBudget',
            'categories'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'home_id' => ['required', 'exists:homes,id'],
            'category_id' => ['nullable', 'exists:expense_categories,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'month' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $home = Home::findOrFail($validated['home_id']);
        $member = $home->members()->where('user_id', $request->user()->id)->first();
        if (!$member || !$member->canEdit()) {
            abort(403);
        }

        // Save or update budget
        ExpenseBudget::updateOrCreate([
            'home_id' => $validated['home_id'],
            'category_id' => $validated['category_id'],
            'month' => $validated['month'],
        ], [
            'amount' => $validated['amount'],
        ]);

        return back()->with('success', 'Đã thiết lập hạn mức chi tiêu thành công!');
    }

    public function destroy(Request $request, ExpenseBudget $budget): RedirectResponse
    {
        $home = $budget->home;
        $member = $home->members()->where('user_id', $request->user()->id)->first();
        if (!$member || !$member->canEdit()) {
            abort(403);
        }

        $budget->delete();

        return back()->with('success', 'Đã xóa hạn mức chi tiêu thành công!');
    }
}
