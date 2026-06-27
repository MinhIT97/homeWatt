<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ __('energy.title') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50/80 border border-green-200 text-green-700 rounded-xl text-sm font-medium shadow-sm">{{ session('success') }}</div>
            @endif

            <div class="grid md:grid-cols-2 gap-6">
                <!-- Record Reading Form -->
                <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 overflow-hidden flex flex-col">
                    <div class="px-6 py-4.5 border-b border-slate-100 font-bold text-slate-800 font-outfit bg-slate-50/40">{{ __('energy.record_reading') }}</div>
                    <form method="POST" action="{{ route('energy.store') }}" class="p-6 space-y-4 flex-1 flex flex-col justify-between">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <x-input-label for="device_id" value="{{ __('energy.select_device') }}" />
                                <select id="device_id" name="device_id" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-850 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5 text-sm" required>
                                    <option value="">{{ __('energy.select_device_option') }}</option>
                                    @foreach($devices as $d)
                                        <option value="{{ $d->id }}" data-power="{{ $d->specification?->rated_power }}" @selected(request('device_id') == $d->id)>{{ $d->name }} ({{ $d->room->home->name }} / {{ $d->room->name }})</option>
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
                        </div>
                        <div class="pt-4 border-t border-slate-100 mt-6">
                            <x-primary-button class="w-full justify-center">{{ __('energy.save_reading') }}</x-primary-button>
                        </div>
                    </form>
                </div>

                <!-- Calculate Estimate Form -->
                <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="px-6 py-4.5 border-b border-slate-100 font-bold text-slate-800 font-outfit bg-slate-50/40">{{ __('energy.estimate_title') }}</div>
                        <form method="POST" action="{{ route('energy.calculate') }}" class="p-6 space-y-4">
                            @csrf
                            <div>
                                <x-input-label for="calc_device_id" value="{{ __('energy.select_device') }}" />
                                <select id="calc_device_id" name="device_id" class="mt-1 block w-full bg-white/80 border border-slate-300 rounded-xl shadow-sm text-slate-850 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition duration-150 py-2.5 px-3.5 text-sm" required>
                                    <option value="">{{ __('energy.select_device_option') }}</option>
                                    @foreach($devices as $d)
                                        <option value="{{ $d->id }}" @selected(request('device_id') == $d->id)>{{ $d->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="year" value="{{ __('energy.year') }}" />
                                    <x-text-input id="year" name="year" type="number" class="mt-1 block w-full" required value="{{ now()->year }}" />
                                </div>
                                <div>
                                    <x-input-label for="month" value="{{ __('energy.month') }}" />
                                    <x-text-input id="month" name="month" type="number" min="1" max="12" class="mt-1 block w-full" required value="{{ now()->month }}" />
                                </div>
                            </div>
                            <div class="pt-4 border-t border-slate-100 mt-6">
                                <x-primary-button class="w-full justify-center">{{ __('energy.calculate_estimate') }}</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Recent Estimates -->
            <div class="mt-8 glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 overflow-hidden">
                <div class="px-6 py-4.5 border-b border-slate-100 font-bold text-slate-850 font-outfit bg-slate-50/40">{{ __('energy.recent_estimates') }}</div>
                @if($estimates->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50/80">
                                <tr>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('energy.table_device') }}</th>
                                    <th class="px-6 py-3.5 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('energy.table_period') }}</th>
                                    <th class="px-6 py-3.5 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('energy.table_est_kwh') }}</th>
                                    <th class="px-6 py-3.5 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('energy.table_method') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($estimates as $e)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-6 py-4 text-sm font-bold text-slate-850">{{ $e->device?->name }}</td>
                                        <td class="px-6 py-4 text-sm font-semibold text-slate-600 text-right">{{ $e->period_start?->format('Y-m') }}</td>
                                        <td class="px-6 py-4 text-sm font-bold text-accent-650 text-right">{{ number_format($e->estimated_kwh, 1) }} kWh</td>
                                        <td class="px-6 py-4 text-right">
                                            <span class="px-2 py-0.5 rounded text-xs font-semibold uppercase border bg-primary-50 text-primary-600 border-primary-150">
                                                {{ $e->method }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-8 text-slate-500 text-sm text-center">{{ __('energy.no_estimates') }}</div>
                @endif
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const deviceSelect = document.getElementById('device_id');
            const wattsInput = document.getElementById('watts');

            function updateWatts() {
                const selectedOption = deviceSelect.options[deviceSelect.selectedIndex];
                const power = selectedOption ? selectedOption.getAttribute('data-power') : '';
                if (power) {
                    wattsInput.value = power;
                } else {
                    wattsInput.value = '';
                }
            }

            if (deviceSelect && wattsInput) {
                deviceSelect.addEventListener('change', updateWatts);
                // Run on initial load as well in case of pre-selected values
                updateWatts();
            }
        });
    </script>
</x-app-layout>
