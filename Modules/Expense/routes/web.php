<?php

use Illuminate\Support\Facades\Route;
use Modules\Expense\Http\Controllers\ExpenseCategoryController;
use Modules\Expense\Http\Controllers\ExpenseController;
use Modules\Expense\Http\Controllers\ExpenseReportController;
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
    Route::get('expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
    Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store');
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
    Route::get('reports/monthly', [ExpenseReportController::class, 'monthly'])->name('reports.monthly');
    Route::get('reports/category', [ExpenseReportController::class, 'byCategory'])->name('reports.category');
});
