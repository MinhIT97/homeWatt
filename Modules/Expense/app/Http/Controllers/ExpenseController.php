<?php

namespace Modules\Expense\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Expense\Http\Requests\StoreExpenseRequest;
use Modules\Expense\Http\Requests\UpdateExpenseRequest;
use Modules\Expense\Imports\BankStatementImport;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Expense\Services\ExpenseService;
use Modules\Expense\Services\ExpenseSplitService;
use Modules\Home\Models\Home;
use Modules\Wallet\Models\Wallet;

class ExpenseController extends Controller
{
    public function __construct(
        private readonly ExpenseService $expenseService,
        private readonly ExpenseSplitService $splitService,
    ) {}

    public function index(Request $request): View
    {
        $userId = $request->user()->id;
        $homeIds = Home::whereHas('members', fn ($q) => $q->where('user_id', $userId))->pluck('id');

        $query = Expense::whereHas('home.members', fn ($q) => $q->where('user_id', $userId))
            ->whereNull('transfer_id')
            ->with(['wallet', 'category', 'user']);

        // Time range period filters
        $period = $request->get('period', 'all');
        $dateVal = $request->get('date', now()->format('Y-m-d'));
        $monthVal = $request->get('month', now()->format('Y-m'));
        $yearVal = (int) $request->get('year', now()->format('Y'));

        if ($period === 'day') {
            $query->whereDate('occurred_at', $dateVal);
        } elseif ($period === 'month') {
            try {
                $carbonMonth = Carbon::createFromFormat('Y-m', $monthVal);
            } catch (\Exception $e) {
                $carbonMonth = now();
                $monthVal = $carbonMonth->format('Y-m');
            }
            $query->whereYear('occurred_at', $carbonMonth->year)->whereMonth('occurred_at', $carbonMonth->month);
        } elseif ($period === 'year') {
            $query->whereYear('occurred_at', $yearVal);
        }

        // Other filters
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

        $debtCategoryIds = ExpenseCategory::whereIn('home_id', $homeIds)
            ->whereIn('category_group', ExpenseCategory::DEBT_GROUPS)
            ->pluck('id');

        // Calculate totals for the filtered results, keeping debt flows separate from real income/spending.
        $totalIncome = (float) (clone $query)
            ->where('type', 'income')
            ->whereNotIn('category_id', $debtCategoryIds)
            ->sum('amount');
        $totalSpent = (float) (clone $query)
            ->where('type', 'expense')
            ->whereNotIn('category_id', $debtCategoryIds)
            ->sum('amount');
        $totalDebtIn = (float) (clone $query)
            ->where('type', 'income')
            ->whereIn('category_id', $debtCategoryIds)
            ->sum('amount');
        $totalDebtOut = (float) (clone $query)
            ->where('type', 'expense')
            ->whereIn('category_id', $debtCategoryIds)
            ->sum('amount');

        $expenses = $query->latest('occurred_at')->paginate(20);

        // Group the paginated page collection by date
        $groupedExpenses = $expenses->getCollection()->groupBy(fn ($e) => $e->occurred_at?->format('Y-m-d') ?? now()->format('Y-m-d'));

        // For filter dropdowns
        $homes = Home::whereHas('members', fn ($q) => $q->where('user_id', $userId))->get();
        $categories = ExpenseCategory::whereHas('home.members', fn ($q) => $q->where('user_id', $userId))
            ->orderBy('type')
            ->orderBy('sort_order')
            ->get();
        $wallets = Wallet::whereHas('home.members', fn ($q) => $q->where('user_id', $userId))
            ->where('is_archived', false)
            ->get();
        $quickSelectedHomeId = (int) ($request->get('home_id') ?: $homes->first()?->id);

        return view('expense::index', compact(
            'expenses', 'groupedExpenses', 'totalIncome', 'totalSpent', 'totalDebtIn', 'totalDebtOut', 'homes', 'categories', 'wallets', 'quickSelectedHomeId',
            'period', 'dateVal', 'monthVal', 'yearVal'
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

        $members = $selectedHomeId
            ? Home::with(['members.user'])->find($selectedHomeId)?->members ?? collect()
            : collect();

        $expenseCats = $categories->where('type', 'expense')->whereNotIn('category_group', ExpenseCategory::DEBT_GROUPS);
        $incomeCats = $categories->where('type', 'income')->whereNotIn('category_group', ExpenseCategory::DEBT_GROUPS);
        $debtCats = $categories->whereIn('category_group', ExpenseCategory::DEBT_GROUPS);

        // Handle PWA share target: pre-fill form with shared content
        $sharedDescription = $request->get('description', '');
        $sharedNotes = $request->get('notes', '');
        $sharedFiles = (int) $request->get('shared_files', 0);
        $isShared = $request->get('source') === 'share';
        $isPwa = $request->get('source') === 'pwa';

        // Handle direct file upload from share target (if POST from sw.js redirect didn't apply)
        $hasReceipts = $request->hasFile('receipts');

        return view('expense::create', compact(
            'homes', 'selectedHomeId', 'categories', 'wallets', 'members', 'expenseCats', 'incomeCats', 'debtCats',
            'sharedDescription', 'sharedNotes', 'isShared', 'isPwa', 'hasReceipts', 'sharedFiles'
        ));
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $expense = $this->expenseService->createExpense($request->validated(), $request->user());

        // Handle bill splitting if splits were submitted
        $splits = $request->input('splits', []);
        if (! empty($splits)) {
            $validSplits = array_filter($splits, fn ($s) => ! empty($s['user_id']) && (float) ($s['amount'] ?? 0) > 0);
            if (! empty($validSplits)) {
                $this->splitService->split($expense, $validSplits, $request->user());
            }
        }

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

        $expenseCats = $categories->where('type', 'expense')->whereNotIn('category_group', ExpenseCategory::DEBT_GROUPS);
        $incomeCats = $categories->where('type', 'income')->whereNotIn('category_group', ExpenseCategory::DEBT_GROUPS);
        $debtCats = $categories->whereIn('category_group', ExpenseCategory::DEBT_GROUPS);

        return view('expense::edit', compact('expense', 'categories', 'wallets', 'expenseCats', 'incomeCats', 'debtCats'));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->authorize('update', $expense);

        $this->expenseService->updateExpense($expense, $request->validated());

        return redirect()->route('expenses.show', $expense)
            ->with('success', __('expense.updated'));
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        $this->authorize('delete', $expense);

        $this->expenseService->deleteExpense($expense);

        return redirect()->route('expenses.index')
            ->with('success', __('expense.deleted'));
    }

    public function importForm(Request $request): View
    {
        $userId = $request->user()->id;
        $homes = Home::whereHas('members', fn ($q) => $q->where('user_id', $userId)
            ->whereIn('role', ['owner', 'manager']))->get();

        return view('expense::import', compact('homes'));
    }

    public function importPreview(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
            'home_id' => 'required|integer|exists:homes,id',
        ]);

        $user = $request->user();
        $homeId = (int) $request->input('home_id');

        // Verify user has access to home
        $home = Home::whereHas('members', fn ($q) => $q->where('user_id', $user->id))->findOrFail($homeId);

        $file = $request->file('file');
        $tempPath = $file->store('imports', 'local');

        try {
            $importer = app(BankStatementImport::class);
            $result = $importer->preview(Storage::path($tempPath), $homeId);

            // Load wallet and category lists for the preview dropdown
            $wallets = Wallet::where('home_id', $homeId)->where('is_archived', false)->get();
            $categories = ExpenseCategory::where('home_id', $homeId)->orderBy('type')->orderBy('sort_order')->get();

            return response()->json([
                'ok' => true,
                'parser' => $result['parser'],
                'transactions' => $result['transactions'],
                'errors' => $result['errors'],
                'wallets' => $wallets->map(fn ($w) => ['id' => $w->id, 'name' => $w->name]),
                'categories' => $categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'type' => $c->type]),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        } finally {
            // Clean up temp file
            if (Storage::exists($tempPath)) {
                Storage::delete($tempPath);
            }
        }
    }

    public function importStore(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
            'home_id' => 'required|integer|exists:homes,id',
            'mappings' => 'nullable|array',
            'mappings.*.wallet_id' => 'nullable|integer|exists:wallets,id',
            'mappings.*.category_id' => 'nullable|integer|exists:expense_categories,id',
            'mappings.*.type' => 'nullable|in:income,expense',
        ]);

        $user = $request->user();
        $homeId = (int) $request->input('home_id');

        // Verify user has access to home
        Home::whereHas('members', fn ($q) => $q->where('user_id', $user->id))->findOrFail($homeId);

        $file = $request->file('file');
        $tempPath = $file->store('imports', 'local');

        try {
            $importer = app(BankStatementImport::class);
            $result = $importer->import(Storage::path($tempPath), $homeId, $user);

            $message = "Đã nhập thành công {$result['success']} giao dịch.";
            if (! empty($result['errors'])) {
                $errorCount = count($result['errors']);
                $message .= " Có {$errorCount} lỗi khi nhập.";
            }

            return redirect()->route('expenses.index', ['home_id' => $homeId])
                ->with(empty($result['errors']) ? 'success' : 'warning', $message);
        } catch (\Throwable $e) {
            return redirect()->route('expenses.import')
                ->with('error', 'Lỗi nhập file: '.$e->getMessage());
        } finally {
            if (Storage::exists($tempPath)) {
                Storage::delete($tempPath);
            }
        }
    }
}
