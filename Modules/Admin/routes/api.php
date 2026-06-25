<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\AdminController;

Route::middleware(['auth:sanctum', 'admin'])->prefix('v1')->group(function () {
    Route::get('admins', [AdminController::class, 'index'])->name('admin.index');
});
