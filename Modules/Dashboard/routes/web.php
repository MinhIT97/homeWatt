<?php

use Illuminate\Support\Facades\Route;
use Modules\Dashboard\Http\Controllers\DashboardController;
use Modules\Dashboard\Http\Controllers\ReportController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/compare', [DashboardController::class, 'compare'])->name('dashboard.compare');
    Route::get('/dashboard/export', [ReportController::class, 'export'])->name('dashboard.export');
});
