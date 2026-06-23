<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">Thêm Phòng Mới</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70">
                <form method="POST" action="{{ route('rooms.store') }}" class="p-8 space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="home_id" value="Chọn ngôi nhà" />
                        <select id="home_id" name="home_id" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5" required>
                            <option value="">Chọn ngôi nhà...</option>
                            @foreach($homes as $home)
                                <option value="{{ $home->id }}" @selected(old('home_id', $selectedHomeId) == $home->id)>{{ $home->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('home_id')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="name" value="Tên phòng" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required placeholder="Ví dụ: Phòng khách, Phòng ngủ..." />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="type" value="Loại phòng" />
                            <select id="type" name="type" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5">
                                @foreach(\Modules\Room\Models\Room::TYPES as $key => $label)
                                    <option value="{{ $key }}" @selected(old('type') == $key)>{{ __("room.types.{$key}") }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <x-input-label for="floor" value="Tầng" />
                        <x-text-input id="floor" name="floor" type="number" class="mt-1 block w-full" :value="old('floor')" placeholder="Mặc định: 1" />
                    </div>

                    <div class="flex items-center justify-end gap-4 pt-4 border-t border-slate-100">
                        <a href="{{ url()->previous() }}" class="text-sm text-slate-500 hover:text-slate-800 font-semibold transition">Hủy bỏ</a>
                        <x-primary-button>Tạo phòng</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
