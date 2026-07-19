<?php

use Illuminate\Support\Facades\Route;
use Modules\Goal\Http\Controllers\GoalController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('goals', [GoalController::class, 'index'])->name('goal.index');
    Route::get('goals/create', [GoalController::class, 'create'])->name('goal.create');
    Route::post('goals', [GoalController::class, 'store'])->name('goal.store');
    Route::get('goals/{goal}', [GoalController::class, 'show'])->name('goal.show');
    Route::get('goals/{goal}/edit', [GoalController::class, 'edit'])->name('goal.edit');
    Route::put('goals/{goal}', [GoalController::class, 'update'])->name('goal.update');
    Route::patch('goals/{goal}', [GoalController::class, 'update']);
    Route::delete('goals/{goal}', [GoalController::class, 'destroy'])->name('goal.destroy');
});
