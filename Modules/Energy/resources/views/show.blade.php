<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">{{ __('energy.show_title') }} #{{ $reading->id }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 overflow-hidden">
                <div class="px-6 py-4.5 border-b border-slate-100 font-bold text-slate-800 font-outfit bg-slate-50/40">{{ __('energy.reading_info') }}</div>
                <div class="p-6 space-y-4">
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-500">{{ __('energy.select_device') }}</span>
                        <span class="text-sm font-bold text-slate-800">{{ $reading->device?->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-500">{{ __('dashboard.table_time') }}</span>
                        <span class="text-sm font-bold text-slate-800">{{ $reading->recorded_at?->format('d/m/Y H:i') }}</span>
                    </div>
                    @if($reading->watts)
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-500">{{ __('energy.power_watt') }}</span>
                        <span class="text-sm font-bold text-slate-800">{{ number_format($reading->watts, 1) }}</span>
                    </div>
                    @endif
                    @if($reading->kwh)
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-500">{{ __('energy.energy_kwh') }}</span>
                        <span class="text-sm font-bold text-slate-800">{{ number_format($reading->kwh, 3) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-500">{{ __('common.source') }}</span>
                        <span class="px-2 py-0.5 rounded text-xs font-semibold uppercase border bg-primary-50 text-primary-600 border-primary-150">{{ __('energy.source_'.$reading->source) }}</span>
                    </div>
                </div>
            </div>

            <div class="mt-6 text-center">
                <a href="{{ route('energy.index') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-800">{{ __('energy.back_to_list') }}</a>
            </div>
        </div>
    </div>
</x-app-layout>
