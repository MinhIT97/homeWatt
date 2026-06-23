<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="text-xl font-bold font-outfit text-gradient-purple-cyan mb-1">{{ __('auth.verify_email') }}</h2>
        <p class="text-xs text-slate-400 leading-relaxed px-2">
            {{ __('auth.verify_email_message') }}
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-xs text-green-600 bg-green-50/80 border border-green-200 rounded-xl px-4 py-3 shadow-sm">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-6 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ __('Resend Verification Email') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="text-xs text-slate-400 hover:text-slate-200 hover:underline transition focus:outline-none">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>

