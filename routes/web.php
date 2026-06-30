<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Http;
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

Route::get('/telegram-test-info', function () {
    $token = config('services.telegram.bot_token');
    $secret = config('services.telegram.webhook_secret');

    if (! $token) {
        return response()->json([
            'status' => 'error',
            'message' => 'TELEGRAM_BOT_TOKEN is not configured',
        ]);
    }

    $botInfo = Http::get("https://api.telegram.org/bot{$token}/getMe")->json();
    $webhookInfo = Http::get("https://api.telegram.org/bot{$token}/getWebhookInfo")->json();

    return response()->json([
        'status' => 'ok',
        'has_secret' => ! empty($secret),
        'secret_length' => strlen($secret),
        'bot_info' => $botInfo,
        'webhook_info' => $webhookInfo,
    ]);
});

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
    Route::post('/profile/telegram/code', [ProfileController::class, 'generateTelegramCode'])->name('profile.telegram.code');
    Route::delete('/profile/telegram', [ProfileController::class, 'unlinkTelegram'])->name('profile.telegram.unlink');
});

Route::get('lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'vi'])) {
        session(['locale' => $locale]);
    }

    return redirect()->back();
})->name('lang.switch');

require __DIR__.'/auth.php';
