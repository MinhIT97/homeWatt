<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="text-xl font-bold font-outfit text-gradient-purple-cyan mb-1">{{ __('auth.reset_password') }}</h2>
        <p class="text-xs text-slate-400">{{ __('auth.reset_password_message') }}</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" class="!text-slate-300" />
            <x-text-input id="email" class="block mt-1 w-full !bg-slate-900/60 !border-slate-800 !text-slate-100 placeholder-slate-500 focus:!border-primary-500 focus:!ring-primary-500/25" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" class="!text-slate-300" />
            <x-text-input id="password" class="block mt-1 w-full !bg-slate-900/60 !border-slate-800 !text-slate-100 placeholder-slate-500 focus:!border-primary-500 focus:!ring-primary-500/25" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="!text-slate-300" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full !bg-slate-900/60 !border-slate-800 !text-slate-100 placeholder-slate-500 focus:!border-primary-500 focus:!ring-primary-500/25"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="pt-2">
            <x-primary-button class="w-full justify-center">
                {{ __('Reset Password') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

