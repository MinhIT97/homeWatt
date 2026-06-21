<?php

use Illuminate\Support\Facades\Route;
use Modules\AI\Http\Controllers\AiAnalysisController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('/ai/analyses', [AiAnalysisController::class, 'index'])->name('ai.analyses.index');
    Route::get('/ai/analyses/create', [AiAnalysisController::class, 'create'])->name('ai.analyses.create');
    Route::post('/ai/analyses', [AiAnalysisController::class, 'store'])->name('ai.analyses.store');
    Route::get('/ai/analyses/{analysis}', [AiAnalysisController::class, 'show'])->name('ai.analyses.show');
    Route::post('/ai/extractions/{extraction}/confirm', [AiAnalysisController::class, 'confirm'])->name('ai.extractions.confirm');
});
