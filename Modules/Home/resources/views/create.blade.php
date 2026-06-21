<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">Thêm Ngôi Nhà Mới</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70">
                <form method="POST" action="{{ route('homes.store') }}" class="p-8 space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="name" value="Tên ngôi nhà" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus placeholder="Ví dụ: Nhà riêng, Chung cư, Văn phòng..." />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="address" value="Địa chỉ (tùy chọn)" />
                        <x-text-input id="address" name="address" type="text" class="mt-1 block w-full" :value="old('address')" placeholder="Số nhà, tên đường, quận/huyện..." />
                        <x-input-error :messages="$errors->get('address')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="timezone" value="Múi giờ" />
                            <select id="timezone" name="timezone" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5">
                                <option value="Asia/Ho_Chi_Minh" selected>Asia/Ho Chi Minh (GMT+7)</option>
                                <option value="Asia/Bangkok">Asia/Bangkok (GMT+7)</option>
                                <option value="UTC">UTC</option>
                            </select>
                        </div>

                        <div>
                            <x-input-label for="currency" value="Tiền tệ" />
                            <select id="currency" name="currency" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5">
                                <option value="VND" selected>VND - Vietnam Dong</option>
                                <option value="USD">USD - US Dollar</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 pt-4 border-t border-slate-100">
                        <a href="{{ route('homes.index') }}" class="text-sm text-slate-500 hover:text-slate-800 font-semibold transition">Hủy bỏ</a>
                        <x-primary-button>Tạo ngôi nhà</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
