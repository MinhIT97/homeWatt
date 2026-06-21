<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-6 text-center">
        <h2 class="text-2xl font-extrabold font-outfit text-gradient-purple-cyan mb-1">Đăng Nhập</h2>
        <p class="text-xs text-slate-400">Quản lý và tối ưu điện năng gia đình bạn</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" class="!text-slate-300" />
            <x-text-input id="email" class="block mt-1 w-full !bg-slate-900/60 !border-slate-800 !text-slate-100 placeholder-slate-500 focus:!border-primary-500 focus:!ring-primary-500/25" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" class="!text-slate-300" />

            <x-text-input id="password" class="block mt-1 w-full !bg-slate-900/60 !border-slate-800 !text-slate-100 placeholder-slate-500 focus:!border-primary-500 focus:!ring-primary-500/25"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between mt-2">
            <label for="remember_me" class="inline-flex items-center cursor-pointer">
                <input id="remember_me" type="checkbox" class="rounded border-slate-800 bg-slate-900 text-primary-600 shadow-sm focus:ring-primary-500/30 focus:ring-offset-slate-950 cursor-pointer" name="remember">
                <span class="ms-2 text-xs text-slate-300 select-none">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-xs text-slate-400 hover:text-primary-400 transition focus:outline-none" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <div class="pt-2">
            <x-primary-button class="w-full justify-center">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

