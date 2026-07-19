<?php

namespace Modules\Expense\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseCategory;

class ReceiptController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $homeIds = $user->homeMembers()->pluck('home_id');

        $query = Expense::whereIn('home_id', $homeIds)
            ->whereNotNull('media_id')
            ->with(['media', 'category', 'wallet'])
            ->latest('occurred_at');

        // Date range filter
        if ($request->filled('from')) {
            $query->whereDate('occurred_at', '>=', $request->get('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('occurred_at', '<=', $request->get('to'));
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->get('category_id'));
        }

        $receipts = $query->paginate(20)->withQueryString();

        $categories = ExpenseCategory::whereIn('home_id', $homeIds)
            ->where('type', 'expense')
            ->orderBy('name')
            ->get();

        return view('expense::receipts.index', compact('receipts', 'categories'));
    }
}
