<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ __('energy.create_title') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 overflow-hidden">
                <div class="px-6 py-4.5 border-b border-slate-100 font-bold text-slate-800 font-outfit bg-slate-50/40">{{ __('energy.new_reading') }}</div>
                <form method="POST" action="{{ route('energy.store') }}" class="p-6 space-y-5">
                    @csrf

                    <div>
                        <x-input-label for="device_id" value="{{ __('energy.select_device') }}" />
                        <select id="device_id" name="device_id" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5 text-sm" required>
                            <option value="">{{ __('energy.select_device_option') }}</option>
                            @foreach($devices as $d)
                                <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->room->home->name }} / {{ $d->room->name }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="watts" value="{{ __('energy.power_watt') }}" />
                            <x-text-input id="watts" name="watts" type="number" step="0.01" class="mt-1 block w-full" placeholder="{{ __('energy.power_placeholder') }}" />
                        </div>
                        <div>
                            <x-input-label for="kwh" value="{{ __('energy.energy_kwh') }}" />
                            <x-text-input id="kwh" name="kwh" type="number" step="0.001" class="mt-1 block w-full" placeholder="{{ __('energy.energy_placeholder') }}" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="source" value="{{ __('energy.data_source') }}" />
                        <select id="source" name="source" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5 text-sm" required>
                            <option value="manual">{{ __('energy.source_manual') }}</option>
                            <option value="measured">{{ __('energy.source_measured') }}</option>
                            <option value="ai">{{ __('energy.source_ai') }}</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label for="recorded_at" value="{{ __('energy.recorded_at') }}" />
                        <x-text-input id="recorded_at" name="recorded_at" type="datetime-local" class="mt-1 block w-full" required value="{{ now()->format('Y-m-d\TH:i') }}" />
                    </div>

                    <div class="pt-4 border-t border-slate-100">
                        <x-primary-button class="w-full justify-center">{{ __('energy.save_reading') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
