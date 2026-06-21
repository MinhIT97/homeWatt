<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70">
                <div class="p-8 text-slate-800 font-medium">
                    <p class="text-lg mb-1 font-bold font-outfit text-primary-650">Chào mừng bạn!</p>
                    <p class="text-sm text-slate-500">Bạn đã đăng nhập thành công vào hệ thống HomeWatt.</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
