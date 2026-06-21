<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">Thêm Thiết Bị Mới</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70">
                <form method="POST" action="{{ route('devices.store') }}" class="p-8 space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="room_id" value="Phòng đặt thiết bị" />
                        <select id="room_id" name="room_id" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5" required>
                            <option value="">Chọn phòng...</option>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}" @selected(old('room_id', $selectedRoomId) == $room->id)>{{ $room->home->name }} / {{ $room->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('room_id')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="name" value="Tên thiết bị" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required placeholder="Ví dụ: Tủ lạnh, Điều hòa..." />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="device_type_id" value="Loại thiết bị" />
                            <select id="device_type_id" name="device_type_id" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5">
                                <option value="">Chọn loại...</option>
                                @foreach($deviceTypes as $type)
                                    <option value="{{ $type->id }}" @selected(old('device_type_id') == $type->id)>{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="brand" value="Thương hiệu" />
                            <x-text-input id="brand" name="brand" type="text" class="mt-1 block w-full" :value="old('brand')" placeholder="LG, Samsung..." />
                        </div>
                        <div>
                            <x-input-label for="model" value="Model / Mã máy" />
                            <x-text-input id="model" name="model" type="text" class="mt-1 block w-full" :value="old('model')" placeholder="Mã máy (nếu có)..." />
                        </div>
                    </div>

                    <div class="pt-6 border-t border-slate-100">
                        <h3 class="font-outfit font-bold text-slate-700 text-sm uppercase tracking-wider border-b border-slate-100 pb-3 mb-4">Thông số công suất (Tùy chọn)</h3>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="rated_power" value="Công suất định mức (W)" />
                                <x-text-input id="rated_power" name="rated_power" type="number" step="0.01" class="mt-1 block w-full" :value="old('rated_power')" placeholder="Ví dụ: 120" />
                            </div>
                            <div>
                                <x-input-label for="max_power" value="Công suất tối đa (W)" />
                                <x-text-input id="max_power" name="max_power" type="number" step="0.01" class="mt-1 block w-full" :value="old('max_power')" placeholder="Ví dụ: 200" />
                            </div>
                            <div>
                                <x-input-label for="standby_power" value="Công suất chờ (W)" />
                                <x-text-input id="standby_power" name="standby_power" type="number" step="0.01" class="mt-1 block w-full" :value="old('standby_power')" placeholder="Ví dụ: 5" />
                            </div>
                            <div>
                                <x-input-label for="voltage" value="Điện áp (V)" />
                                <x-text-input id="voltage" name="voltage" type="number" step="0.1" class="mt-1 block w-full" :value="old('voltage')" placeholder="Ví dụ: 220" />
                            </div>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-slate-100">
                        <h3 class="font-outfit font-bold text-slate-700 text-sm uppercase tracking-wider border-b border-slate-100 pb-3 mb-4">Tần suất sử dụng (Tùy chọn)</h3>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="hours_per_day" value="Số giờ/ngày" />
                                <x-text-input id="hours_per_day" name="hours_per_day" type="number" step="0.5" max="24" class="mt-1 block w-full" :value="old('hours_per_day')" placeholder="Ví dụ: 8" />
                            </div>
                            <div>
                                <x-input-label for="days_per_week" value="Số ngày/tuần" />
                                <x-text-input id="days_per_week" name="days_per_week" type="number" min="1" max="7" class="mt-1 block w-full" :value="old('days_per_week', 7)" />
                            </div>
                            <div>
                                <x-input-label for="duty_cycle" value="Hệ số tải (0-1)" />
                                <x-text-input id="duty_cycle" name="duty_cycle" type="number" step="0.01" min="0" max="1" class="mt-1 block w-full" :value="old('duty_cycle')" placeholder="Mặc định: 1.0" />
                                <p class="text-[11px] text-slate-400 mt-1">1.0 = luôn chạy, 0.5 = chạy 1 nửa thời gian</p>
                            </div>
                            <div>
                                <x-input-label for="season" value="Mùa hoạt động" />
                                <select id="season" name="season" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5 text-sm">
                                    <option value="all">Quanh năm</option>
                                    <option value="summer">Chỉ mùa hè</option>
                                    <option value="winter">Chỉ mùa đông</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 pt-6 border-t border-slate-100">
                        <a href="{{ route('devices.index') }}" class="text-sm text-slate-500 hover:text-slate-800 font-semibold transition">Hủy bỏ</a>
                        <x-primary-button>Tạo thiết bị</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
