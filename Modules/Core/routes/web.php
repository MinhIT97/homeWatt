<?php

use Illuminate\Support\Facades\Route;

Route::get('/up', function () {
    return response()->json(['status' => 'ok', 'release' => config('app.release')]);
});

Route::get('/version', function () {
    return response()->json([
        'name' => config('app.name'),
        'release' => config('app.release'),
        'php' => PHP_VERSION,
        'laravel' => app()->version(),
    ]);
});

Route::middleware(['web'])->group(function () {
    Route::get('/', function () {
        return view('core::welcome');
    })->name('home');
});
