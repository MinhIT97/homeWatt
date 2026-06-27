<?php

namespace Modules\Expense\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Expense\Http\Requests\StoreExpenseRequest;
use Modules\Expense\Http\Requests\UpdateExpenseRequest;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Expense\Services\ExpenseService;
use Modules\Home\Models\Home;
use Modules\Wallet\Models\Wallet;

class ExpenseController extends Controller
{
    public function __construct(private readonly ExpenseService $expenseService) {}

    public function index(Request $request): View
    {
        $userId = $request->user()->id;

        $query = Expense::whereHas('home.members', fn ($q) => $q->where('user_id', $userId))
            ->with(['wallet', 'category', 'user']);

        // Filters
        if ($homeId = $request->get('home_id')) {
            $query->where('home_id', $homeId);
        }
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }
        if ($categoryId = $request->get('category_id')) {
            $query->where('category_id', $categoryId);
        }
        if ($walletId = $request->get('wallet_id')) {
            $query->where('wallet_id', $walletId);
        }
        if ($from = $request->get('from')) {
            $query->whereDate('occurred_at', '>=', $from);
        }
        if ($to = $request->get('to')) {
            $query->whereDate('occurred_at', '<=', $to);
        }

        $expenses = $query->latest('occurred_at')->paginate(20);

        // For filter dropdowns
        $homes = Home::whereHas('members', fn ($q) => $q->where('user_id', $userId))->get();
        $categories = ExpenseCategory::whereHas('home.members', fn ($q) => $q->where('user_id', $userId))
            ->orderBy('type')
            ->orderBy('sort_order')
            ->get();
        $wallets = Wallet::whereHas('home.members', fn ($q) => $q->where('user_id', $userId))
            ->where('is_archived', false)
            ->get();

        return view('expense::index', compact(
            'expenses', 'homes', 'categories', 'wallets'
        ));
    }

    public function create(Request $request): View
    {
        $userId = $request->user()->id;
        $homes = Home::whereHas('members', fn ($q) => $q->where('user_id', $userId)
            ->whereIn('role', ['owner', 'manager']))->get();
        $selectedHomeId = $request->get('home_id') ?: $homes->first()?->id;

        $categories = $selectedHomeId
            ? ExpenseCategory::where('home_id', $selectedHomeId)->whereNull('parent_id')->with('children')->orderBy('sort_order')->get()
            : collect();
        $wallets = $selectedHomeId
            ? Wallet::where('home_id', $selectedHomeId)->where('is_archived', false)->get()
            : collect();

        $expenseCats = $categories->where('type', 'expense')->whereNotIn('name', ['Cho vay', 'Trả nợ']);
        $incomeCats = $categories->where('type', 'income')->whereNotIn('name', ['Đi vay', 'Thu nợ']);
        $debtCats = $categories->filter(fn($c) => in_array($c->name, ['Cho vay', 'Trả nợ', 'Đi vay', 'Thu nợ']));

        return view('expense::create', compact(
            'homes', 'selectedHomeId', 'categories', 'wallets', 'expenseCats', 'incomeCats', 'debtCats'
        ));
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $expense = $this->expenseService->createExpense($request->validated(), $request->user());

        AuditLogger::log('expense.created', [
            'expense_id' => $expense->id,
            'home_id' => $expense->home_id,
            'amount' => $expense->amount,
            'type' => $expense->type,
        ]);

        return redirect()->route('expenses.show', $expense)
            ->with('success', __('expense.created'));
    }

    public function show(Request $request, Expense $expense): View
    {
        $this->authorize('view', $expense);

        $expense->load(['wallet', 'category', 'user', 'transfer']);

        return view('expense::show', compact('expense'));
    }

    public function edit(Request $request, Expense $expense): View
    {
        $this->authorize('update', $expense);

        $expense->load(['wallet', 'category']);
        $categories = ExpenseCategory::where('home_id', $expense->home_id)->whereNull('parent_id')->with('children')->orderBy('sort_order')->get();
        $wallets = Wallet::where('home_id', $expense->home_id)->where('is_archived', false)->get();

        $expenseCats = $categories->where('type', 'expense')->whereNotIn('name', ['Cho vay', 'Trả nợ']);
        $incomeCats = $categories->where('type', 'income')->whereNotIn('name', ['Đi vay', 'Thu nợ']);
        $debtCats = $categories->filter(fn($c) => in_array($c->name, ['Cho vay', 'Trả nợ', 'Đi vay', 'Thu nợ']));

        return view('expense::edit', compact('expense', 'categories', 'wallets', 'expenseCats', 'incomeCats', 'debtCats'));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->authorize('update', $expense);

        $this->expenseService->updateExpense($expense, $request->validated());

        AuditLogger::log('expense.updated', [
            'expense_id' => $expense->id,
            'home_id' => $expense->home_id,
        ]);

        return redirect()->route('expenses.show', $expense)
            ->with('success', __('expense.updated'));
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        $this->authorize('delete', $expense);

        $expenseId = $expense->id;
        $homeId = $expense->home_id;

        $this->expenseService->deleteExpense($expense);

        AuditLogger::log('expense.deleted', [
            'expense_id' => $expenseId,
            'home_id' => $homeId,
        ]);

        return redirect()->route('expenses.index')
            ->with('success', __('expense.deleted'));
    }
}
