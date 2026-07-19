<?php

use Illuminate\Support\Facades\Route;
use Modules\Home\Http\Controllers\HomeController;
use Modules\Home\Http\Controllers\InvitationController;

// Public invite routes (no auth required to view, but accept requires auth)
Route::get('invite/{token}', [InvitationController::class, 'show'])->name('invite.show');
Route::post('invite/{token}/accept', [InvitationController::class, 'accept'])->middleware(['web', 'auth'])->name('invite.accept');

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::resource('homes', HomeController::class);

    Route::get('/homes/{home}/members', [HomeController::class, 'members'])
        ->name('homes.members');

    Route::post('/homes/{home}/invite', [HomeController::class, 'invite'])
        ->name('homes.invite');

    Route::delete('/homes/{home}/members/{member}', [HomeController::class, 'removeMember'])
        ->name('homes.members.remove');

    // Invitation link management
    Route::get('homes/{home}/invite-link', [HomeController::class, 'inviteForm'])->name('homes.invite.form');
    Route::post('homes/{home}/invite-link', [HomeController::class, 'createInvitation'])->name('homes.invite.create');
    Route::delete('invitations/{invitation}', [HomeController::class, 'revokeInvitation'])->name('homes.invite.revoke');
});
