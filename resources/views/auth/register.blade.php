<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="text-2xl font-extrabold font-outfit text-gradient-purple-cyan mb-1">Tạo Tài Khoản</h2>
        <p class="text-xs text-slate-400">Khởi đầu hành trình quản lý năng lượng thông minh</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" class="!text-slate-300" />
            <x-text-input id="name" class="block mt-1 w-full !bg-slate-900/60 !border-slate-800 !text-slate-100 placeholder-slate-500 focus:!border-primary-500 focus:!ring-primary-500/25" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" class="!text-slate-300" />
            <x-text-input id="email" class="block mt-1 w-full !bg-slate-900/60 !border-slate-800 !text-slate-100 placeholder-slate-500 focus:!border-primary-500 focus:!ring-primary-500/25" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" class="!text-slate-300" />

            <x-text-input id="password" class="block mt-1 w-full !bg-slate-900/60 !border-slate-800 !text-slate-100 placeholder-slate-500 focus:!border-primary-500 focus:!ring-primary-500/25"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

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

        <div class="flex items-center justify-between pt-2">
            <a class="text-xs text-slate-400 hover:text-primary-400 transition focus:outline-none" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button>
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

