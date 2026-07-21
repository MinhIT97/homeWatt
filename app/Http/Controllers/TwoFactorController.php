<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactorService,
    ) {}

    /**
     * Show the 2FA setup page with QR code and manual key.
     */
    public function setup(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return view('profile.partials.two-factor-form', [
                'user' => $user,
                'enabled' => true,
            ]);
        }

        $secret = $this->twoFactorService->generateSecret();
        $qrCodeUrl = $this->twoFactorService->generateQrCodeUrl($user, $secret);

        // Store the secret temporarily in session so we can verify after setup
        session(['two_factor_temp_secret' => $secret]);

        return view('profile.partials.two-factor-form', [
            'user' => $user,
            'enabled' => false,
            'secret' => $secret,
            'qrCodeUrl' => $qrCodeUrl,
        ]);
    }

    /**
     * Verify the TOTP code and enable 2FA for the user.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $secret = session('two_factor_temp_secret');

        if (! $secret) {
            return back()->withErrors(['code' => 'Session expired. Please start setup again.']);
        }

        if (! $this->twoFactorService->verify($secret, $request->input('code'))) {
            return back()->withErrors(['code' => 'The verification code is invalid. Please try again.']);
        }

        $recoveryCodes = $this->twoFactorService->enable($user, $secret);

        // Mark as confirmed since they just verified
        $this->twoFactorService->confirm($user);

        // Clear temp secret
        session()->forget('two_factor_temp_secret');

        return back()->with([
            'status' => 'two-factor-enabled',
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    /**
     * Disable 2FA for the authenticated user (requires password confirmation).
     */
    public function destroy(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($request->input('password'), $user->password)) {
            return back()->withErrors(['password' => 'The provided password is incorrect.']);
        }

        $this->twoFactorService->disable($user);

        return back()->with('status', 'two-factor-disabled');
    }

    /**
     * Show the 2FA challenge form (shown after password login if 2FA is enabled).
     */
    public function challenge(): View
    {
        return view('auth.two-factor-challenge');
    }

    /**
     * Verify the 2FA code during the login challenge flow.
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        /** @var User $user */
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $code = $request->input('code');

        // Try TOTP code first
        if ($this->twoFactorService->verify($user->two_factor_secret, $code)) {
            session(['auth.two_factor_verified' => true]);

            return redirect()->intended(route('dashboard', absolute: false));
        }

        // Try recovery code
        if ($this->twoFactorService->verifyRecoveryCode($user, $code)) {
            session(['auth.two_factor_verified' => true]);

            return redirect()->intended(route('dashboard', absolute: false));
        }

        return back()->withErrors([
            'code' => 'The verification code is invalid.',
        ]);
    }
}
