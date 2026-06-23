<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ __('device.create_title') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70">
                <form method="POST" action="{{ route('devices.store') }}" class="p-8 space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="room_id" value="{{ __('device.room_label') }}" />
                        <select id="room_id" name="room_id" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5" required>
                            <option value="">{{ __('device.select_room') }}</option>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}" @selected(old('room_id', $selectedRoomId) == $room->id)>{{ $room->home->name }} / {{ $room->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('room_id')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="name" value="{{ __('device.name_label') }}" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required placeholder="{{ __('device.name_placeholder') }}" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="device_type_id" value="{{ __('device.type_label') }}" />
                            <select id="device_type_id" name="device_type_id" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5">
                                <option value="">{{ __('device.select_type') }}</option>
                                @foreach($deviceTypes as $type)
                                    <option value="{{ $type->id }}" @selected(old('device_type_id') == $type->id)>{{ $type->display_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="brand" value="{{ __('device.brand_label') }}" />
                            <x-text-input id="brand" name="brand" type="text" class="mt-1 block w-full" :value="old('brand')" placeholder="{{ __('device.brand_placeholder') }}" />
                        </div>
                        <div>
                            <x-input-label for="model" value="{{ __('device.model_label') }}" />
                            <x-text-input id="model" name="model" type="text" class="mt-1 block w-full" :value="old('model')" placeholder="{{ __('device.model_placeholder') }}" />
                        </div>
                    </div>

                    <div class="pt-6 border-t border-slate-100">
                        <h3 class="font-outfit font-bold text-slate-700 text-sm uppercase tracking-wider border-b border-slate-100 pb-3 mb-4">{{ __('device.power_specs') }}</h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="rated_power" value="{{ __('device.rated_power') }}" />
                                <x-text-input id="rated_power" name="rated_power" type="number" step="0.01" class="mt-1 block w-full" :value="old('rated_power')" placeholder="{{ __('device.rated_power_placeholder') }}" />
                            </div>
                            <div>
                                <x-input-label for="max_power" value="{{ __('device.max_power') }}" />
                                <x-text-input id="max_power" name="max_power" type="number" step="0.01" class="mt-1 block w-full" :value="old('max_power')" placeholder="{{ __('device.max_power_placeholder') }}" />
                            </div>
                            <div>
                                <x-input-label for="standby_power" value="{{ __('device.standby_power') }}" />
                                <x-text-input id="standby_power" name="standby_power" type="number" step="0.01" class="mt-1 block w-full" :value="old('standby_power')" placeholder="{{ __('device.standby_power_placeholder') }}" />
                            </div>
                            <div>
                                <x-input-label for="voltage" value="{{ __('common.voltage') }} (V)" />
                                <x-text-input id="voltage" name="voltage" type="number" step="0.1" class="mt-1 block w-full" :value="old('voltage')" placeholder="{{ __('device.voltage_placeholder') }}" />
                            </div>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-slate-100">
                        <h3 class="font-outfit font-bold text-slate-700 text-sm uppercase tracking-wider border-b border-slate-100 pb-3 mb-4">{{ __('device.usage_frequency') }}</h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="hours_per_day" value="{{ __('device.hours_per_day') }}" />
                                <x-text-input id="hours_per_day" name="hours_per_day" type="number" step="0.5" max="24" class="mt-1 block w-full" :value="old('hours_per_day')" placeholder="{{ __('device.hours_per_day_placeholder') }}" />
                            </div>
                            <div>
                                <x-input-label for="days_per_week" value="{{ __('device.days_per_week') }}" />
                                <x-text-input id="days_per_week" name="days_per_week" type="number" min="1" max="7" class="mt-1 block w-full" :value="old('days_per_week', 7)" />
                            </div>
                            <div>
                                <x-input-label for="duty_cycle" value="{{ __('device.load_factor') }}" />
                                <x-text-input id="duty_cycle" name="duty_cycle" type="number" step="0.01" min="0" max="1" class="mt-1 block w-full" :value="old('duty_cycle')" placeholder="{{ __('device.load_factor_placeholder') }}" />
                                <p class="text-[11px] text-slate-400 mt-1">{{ __('device.load_factor_desc') }}</p>
                            </div>
                            <div>
                                <x-input-label for="season" value="{{ __('device.season') }}" />
                                <select id="season" name="season" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5 text-sm">
                                    <option value="all">{{ __('device.season_all_year') }}</option>
                                    <option value="summer">{{ __('device.season_summer') }}</option>
                                    <option value="winter">{{ __('device.season_winter') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 pt-6 border-t border-slate-100">
                        <a href="{{ route('devices.index') }}" class="text-sm text-slate-500 hover:text-slate-800 font-semibold transition">{{ __('common.cancel') }}</a>
                        <x-primary-button>{{ __('device.create_button') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
