<?php

namespace Modules\Expense\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Expense\Http\Requests\StoreExpenseCategoryRequest;
use Modules\Expense\Http\Requests\UpdateExpenseCategoryRequest;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Home\Models\Home;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->user()->id;

        $categories = ExpenseCategory::whereHas('home.members', fn ($q) => $q->where('user_id', $userId))
            ->whereNull('parent_id')
            ->with(['home', 'children'])
            ->orderBy('home_id')
            ->orderBy('type')
            ->orderBy('sort_order')
            ->get();

        return view('expense::category.index', compact('categories'));
    }

    public function create(Request $request): View
    {
        $homes = $request->user()
            ->homeMembers()
            ->with('home')
            ->whereIn('role', ['owner', 'manager'])
            ->get()
            ->pluck('home');

        $parentCategories = ExpenseCategory::whereHas('home.members', fn ($q) => $q->where('user_id', $request->user()->id))
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        return view('expense::category.create', compact('homes', 'parentCategories'));
    }

    public function store(StoreExpenseCategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', [ExpenseCategory::class, Home::findOrFail($request->validated('home_id'))]);

        $category = ExpenseCategory::create([
            ...$request->validated(),
            'is_system' => false,
            'sort_order' => $request->validated('sort_order') ?? 99,
        ]);

        AuditLogger::log('expense.category_created', [
            'category_id' => $category->id,
            'home_id' => $category->home_id,
        ]);

        return redirect()->route('categories.index')
            ->with('success', __('expense.category_created'));
    }

    public function edit(Request $request, ExpenseCategory $category): View
    {
        $this->authorize('update', $category);

        $parentCategories = ExpenseCategory::where('home_id', $category->home_id)
            ->whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->orderBy('sort_order')
            ->get();

        return view('expense::category.edit', compact('category', 'parentCategories'));
    }

    public function update(UpdateExpenseCategoryRequest $request, ExpenseCategory $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $category->update($request->validated());

        AuditLogger::log('expense.category_updated', [
            'category_id' => $category->id,
        ]);

        return redirect()->route('categories.index')
            ->with('success', __('expense.category_updated'));
    }

    public function destroy(Request $request, ExpenseCategory $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        $categoryId = $category->id;
        $category->delete();

        AuditLogger::log('expense.category_deleted', [
            'category_id' => $categoryId,
        ]);

        return redirect()->route('categories.index')
            ->with('success', __('expense.category_deleted'));
    }
}
