<?php

use Illuminate\Support\Facades\Route;
use Modules\Expense\Http\Controllers\ExpenseCategoryController;
use Modules\Expense\Http\Controllers\ExpenseController;
use Modules\Expense\Http\Controllers\TransferController;

use Modules\Expense\Http\Controllers\TelegramWebhookController;

Route::post('v1/telegram/webhook', [TelegramWebhookController::class, 'handle'])
    ->name('telegram.webhook');

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('expenses', ExpenseController::class)->names('expenses');
    Route::apiResource('categories', ExpenseCategoryController::class)->except(['show'])->names('categories');
    Route::apiResource('transfers', TransferController::class)->only(['index', 'store', 'show', 'destroy'])->names('transfers');
});
