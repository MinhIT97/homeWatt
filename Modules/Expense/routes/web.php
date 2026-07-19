<?php

use Illuminate\Support\Facades\Route;
use Modules\Expense\Http\Controllers\BudgetController;
use Modules\Expense\Http\Controllers\ExpenseCategoryController;
use Modules\Expense\Http\Controllers\ExpenseController;
use Modules\Expense\Http\Controllers\ExpenseReportController;
use Modules\Expense\Http\Controllers\QuickEntryController;
use Modules\Expense\Http\Controllers\ReceiptController;
use Modules\Expense\Http\Controllers\DebtController;
use Modules\Expense\Http\Controllers\TransferController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    // Categories
    Route::get('categories', [ExpenseCategoryController::class, 'index'])->name('categories.index');
    Route::get('categories/create', [ExpenseCategoryController::class, 'create'])->name('categories.create');
    Route::post('categories', [ExpenseCategoryController::class, 'store'])->name('categories.store');
    Route::get('categories/{category}/edit', [ExpenseCategoryController::class, 'edit'])->name('categories.edit');
    Route::put('categories/{category}', [ExpenseCategoryController::class, 'update'])->name('categories.update');
    Route::patch('categories/{category}', [ExpenseCategoryController::class, 'update']);
    Route::delete('categories/{category}', [ExpenseCategoryController::class, 'destroy'])->name('categories.destroy');

    // Expenses
    Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('expenses/quick/preview', [QuickEntryController::class, 'preview'])->name('expenses.quick.preview');
    Route::post('expenses/quick/store', [QuickEntryController::class, 'store'])->name('expenses.quick.store');
    Route::get('expenses/quick/templates', [QuickEntryController::class, 'templates'])->name('expenses.quick.templates');
    Route::get('expenses/quick/recurring', [QuickEntryController::class, 'recurring'])->name('expenses.quick.recurring');
    Route::post('expenses/quick/recurring', [QuickEntryController::class, 'storeRecurring'])->name('expenses.quick.recurring.store');
    Route::delete('expenses/quick/recurring/{recurring}', [QuickEntryController::class, 'destroyRecurring'])->name('expenses.quick.recurring.destroy');
    Route::get('expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
    Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    // Bank Statement Import (must be before {expense} to avoid route conflicts)
    Route::get('expenses/import', [ExpenseController::class, 'importForm'])->name('expenses.import');
    Route::post('expenses/import/preview', [ExpenseController::class, 'importPreview'])->name('expenses.import.preview');
    Route::post('expenses/import', [ExpenseController::class, 'importStore'])->name('expenses.import.store');
    Route::get('expenses/{expense}', [ExpenseController::class, 'show'])->name('expenses.show');
    Route::get('expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
    Route::put('expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
    Route::patch('expenses/{expense}', [ExpenseController::class, 'update']);
    Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

    // Transfers
    Route::get('transfers', [TransferController::class, 'index'])->name('transfers.index');
    Route::get('transfers/create', [TransferController::class, 'create'])->name('transfers.create');
    Route::post('transfers', [TransferController::class, 'store'])->name('transfers.store');
    Route::get('transfers/{transfer}', [TransferController::class, 'show'])->name('transfers.show');
    Route::delete('transfers/{transfer}', [TransferController::class, 'destroy'])->name('transfers.destroy');

    // Reports
    Route::get('reports', [ExpenseReportController::class, 'summary'])->name('reports.index');
    Route::get('reports/summary', [ExpenseReportController::class, 'summary'])->name('reports.summary');
    Route::get('reports/monthly', [ExpenseReportController::class, 'monthly'])->name('reports.monthly');
    Route::get('reports/category', [ExpenseReportController::class, 'byCategory'])->name('reports.category');
    Route::get('reports/cashflow', [ExpenseReportController::class, 'cashflow'])->name('reports.cashflow');
    Route::get('reports/trend', [ExpenseReportController::class, 'trend'])->name('reports.trend');
    Route::get('reports/year-comparison', [ExpenseReportController::class, 'yearComparison'])->name('reports.year-comparison');
    Route::get('reports/networth', [ExpenseReportController::class, 'networth'])->name('reports.networth');

    // Report Exports
    Route::get('reports/export/pdf', [ExpenseReportController::class, 'exportPdfForm'])->name('reports.export.pdf-form');
    Route::get('reports/export/pdf/download', [ExpenseReportController::class, 'exportPdf'])->name('reports.export.pdf');
    Route::get('reports/export/excel', [ExpenseReportController::class, 'exportExcel'])->name('reports.export.excel');

    // Budgets
    Route::get('budgets', [BudgetController::class, 'index'])->name('budgets.index');
    Route::post('budgets', [BudgetController::class, 'store'])->name('budgets.store');
    Route::delete('budgets/{budget}', [BudgetController::class, 'destroy'])->name('budgets.destroy');

    // Receipt Gallery
    Route::get('receipts', [ReceiptController::class, 'index'])->name('receipts.index');

    // Shared Expenses / Debts
    Route::get('debts', [DebtController::class, 'index'])->name('debts.index');
    Route::post('debts/{split}/settle', [DebtController::class, 'settle'])->name('debts.settle');
});
