<?php

use Illuminate\Support\Facades\Route;
use Modules\Wallet\Http\Controllers\WalletController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('wallets', [WalletController::class, 'index'])->name('wallets.index');
    Route::get('wallets/create', [WalletController::class, 'create'])->name('wallets.create');
    Route::post('wallets', [WalletController::class, 'store'])->name('wallets.store');
    Route::get('wallets/{wallet}', [WalletController::class, 'show'])->name('wallets.show');
    Route::get('wallets/{wallet}/edit', [WalletController::class, 'edit'])->name('wallets.edit');
    Route::put('wallets/{wallet}', [WalletController::class, 'update'])->name('wallets.update');
    Route::patch('wallets/{wallet}', [WalletController::class, 'update']);
    Route::delete('wallets/{wallet}', [WalletController::class, 'destroy'])->name('wallets.destroy');
    Route::post('wallets/{wallet}/archive', [WalletController::class, 'archive'])->name('wallets.archive');
    Route::post('wallets/{wallet}/restore', [WalletController::class, 'restore'])->name('wallets.restore');
});
