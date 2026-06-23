<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="text-xl font-bold font-outfit text-gradient-purple-cyan mb-1">{{ __('auth.confirm_password') }}</h2>
        <p class="text-xs text-slate-400 leading-relaxed px-2">
            {{ __('auth.confirm_password_message') }}
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" class="!text-slate-300" />

            <x-text-input id="password" class="block mt-1 w-full !bg-slate-900/60 !border-slate-800 !text-slate-100 placeholder-slate-500 focus:!border-primary-500 focus:!ring-primary-500/25"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="pt-2">
            <x-primary-button class="w-full justify-center">
                {{ __('Confirm') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

