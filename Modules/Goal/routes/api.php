<?php

use Illuminate\Support\Facades\Route;
use Modules\Goal\Http\Controllers\GoalController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('goals', GoalController::class)->names('goal');
});
