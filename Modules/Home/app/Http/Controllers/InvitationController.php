<?php

namespace Modules\Home\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Home\Models\HomeInvitation;

class InvitationController extends Controller
{
    public function show(Request $request, string $token): View
    {
        $invitation = HomeInvitation::where('token', $token)
            ->with(['home', 'inviter'])
            ->firstOrFail();

        $expired = $invitation->isExpired();
        $fullyUsed = $invitation->isFullyUsed();
        $valid = $invitation->isValid();

        return view('home::accept-invite', compact('invitation', 'expired', 'fullyUsed', 'valid'));
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $user = $request->user();

        $invitation = HomeInvitation::where('token', $token)->firstOrFail();

        if (! $invitation->isValid()) {
            $message = $invitation->isExpired()
                ? 'Lời mời này đã hết hạn.'
                : 'Lời mời này đã đạt số lượt sử dụng tối đa.';

            return redirect()->route('dashboard')
                ->with('error', $message);
        }

        $home = $invitation->home;

        // Check if user is already a member
        if ($home->isMember($user->id)) {
            return redirect()->route('homes.show', $home)
                ->with('info', 'Bạn đã là thành viên của nhà này.');
        }

        // Create membership
        $membership = $home->members()->create([
            'user_id' => $user->id,
        ]);
        $membership->assignRole($invitation->role);

        // Increment use count
        $invitation->increment('use_count');

        return redirect()->route('homes.show', $home)
            ->with('success', 'Bạn đã tham gia nhà '.$home->name.' với vai trò '.$this->roleLabel($invitation->role).'.');
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            'manager' => 'Quản lý',
            'member' => 'Thành viên',
            'viewer' => 'Người xem',
            default => $role,
        };
    }
}
