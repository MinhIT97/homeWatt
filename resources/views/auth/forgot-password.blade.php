<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="text-xl font-bold font-outfit text-gradient-purple-cyan mb-1">Khôi Phục Mật Khẩu</h2>
        <p class="text-xs text-slate-400 leading-relaxed px-2">
            Nhập email của bạn và chúng tôi sẽ gửi liên kết để đặt lại mật khẩu mới.
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" class="!text-slate-300" />
            <x-text-input id="email" class="block mt-1 w-full !bg-slate-900/60 !border-slate-800 !text-slate-100 placeholder-slate-500 focus:!border-primary-500 focus:!ring-primary-500/25" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="pt-2">
            <x-primary-button class="w-full justify-center">
                Gửi liên kết khôi phục
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

