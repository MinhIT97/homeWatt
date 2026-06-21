<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">Chỉnh Sửa: {{ $home->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70">
                <form method="POST" action="{{ route('homes.update', $home) }}" class="p-8 space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="name" value="Tên ngôi nhà" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $home->name)" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="address" value="Địa chỉ" />
                        <x-text-input id="address" name="address" type="text" class="mt-1 block w-full" :value="old('address', $home->address)" />
                        <x-input-error :messages="$errors->get('address')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="timezone" value="Múi giờ" />
                            <select id="timezone" name="timezone" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5">
                                <option value="UTC" @selected(old('timezone', $home->timezone) === 'UTC')>UTC</option>
                                <option value="Asia/Ho_Chi_Minh" @selected(old('timezone', $home->timezone) === 'Asia/Ho_Chi_Minh')>Asia/Ho Chi Minh (GMT+7)</option>
                            </select>
                        </div>

                        <div>
                            <x-input-label for="status" value="Trạng thái" />
                            <select id="status" name="status" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5">
                                <option value="active" @selected(old('status', $home->status) === 'active')>Active</option>
                                <option value="inactive" @selected(old('status', $home->status) === 'inactive')>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 pt-4 border-t border-slate-100">
                        <a href="{{ route('homes.show', $home) }}" class="text-sm text-slate-500 hover:text-slate-800 font-semibold transition">Hủy bỏ</a>
                        <x-primary-button>Lưu thay đổi</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
