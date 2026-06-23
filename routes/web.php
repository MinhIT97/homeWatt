<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/up', function () {
    return response()->json(['status' => 'ok']);
})->name('health');

Route::get('/version', function () {
    return response()->json([
        'application' => config('app.name'),
        'release' => config('app.release', 'unknown'),
    ])->withHeaders([
        'Cache-Control' => 'no-store, no-cache, must-revalidate',
    ]);
})->name('version');

Route::get('/', function () {
    return view('core::welcome');
})->name('home');

Route::get('/offline', function () {
    return view('errors.offline');
})->name('offline');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'vi'])) {
        session(['locale' => $locale]);
    }

    return redirect()->back();
})->name('lang.switch');

require __DIR__.'/auth.php';
