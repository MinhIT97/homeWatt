<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-6 text-center">
        <h2 class="text-2xl font-extrabold font-outfit text-gradient-purple-cyan mb-1">Two-Factor Authentication</h2>
        <p class="text-xs text-slate-400">Please enter the verification code from your authenticator app to continue.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-md bg-red-900/30 border border-red-800 p-4">
            <div class="text-sm text-red-400">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('two-factor.verify') }}" class="space-y-5">
        @csrf

        <!-- Code -->
        <div>
            <x-input-label for="code" value="Authentication Code" class="!text-slate-300" />
            <x-text-input
                id="code"
                class="block mt-1 w-full !bg-slate-900/60 !border-slate-800 !text-slate-100 placeholder-slate-500 focus:!border-primary-500 focus:!ring-primary-500/25"
                type="text"
                name="code"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="6"
                autocomplete="one-time-code"
                autofocus
                required
                placeholder="000000"
            />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
            <p class="mt-1 text-xs text-slate-500">
                Enter the 6-digit code from your authenticator app, or use a recovery code.
            </p>
        </div>

        <div class="pt-2">
            <x-primary-button class="w-full justify-center">
                Verify
            </x-primary-button>
        </div>
    </form>

    <div class="text-center pt-4">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-xs text-slate-400 hover:text-primary-400 transition focus:outline-none">
                Cancel and log out
            </button>
        </form>
    </div>
</x-guest-layout>
