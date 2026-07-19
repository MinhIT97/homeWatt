<?php

use Illuminate\Support\Facades\Route;
use Modules\Automation\Http\Controllers\AutomationRuleController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('automation', [AutomationRuleController::class, 'index'])->name('automation.index');
    Route::get('automation/create', [AutomationRuleController::class, 'create'])->name('automation.create');
    Route::post('automation', [AutomationRuleController::class, 'store'])->name('automation.store');
    Route::get('automation/{rule}/edit', [AutomationRuleController::class, 'edit'])->name('automation.edit');
    Route::put('automation/{rule}', [AutomationRuleController::class, 'update'])->name('automation.update');
    Route::delete('automation/{rule}', [AutomationRuleController::class, 'destroy'])->name('automation.destroy');
    Route::post('automation/{rule}/toggle', [AutomationRuleController::class, 'toggle'])->name('automation.toggle');
    Route::get('automation/{rule}/logs', [AutomationRuleController::class, 'logs'])->name('automation.logs');
});
