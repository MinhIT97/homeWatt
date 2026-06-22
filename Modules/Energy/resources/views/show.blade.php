<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">Chi Tiết Số Đo #{{ $reading->id }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 overflow-hidden">
                <div class="px-6 py-4.5 border-b border-slate-100 font-bold text-slate-800 font-outfit bg-slate-50/40">Thông tin số đo</div>
                <div class="p-6 space-y-4">
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-500">Thiết bị</span>
                        <span class="text-sm font-bold text-slate-800">{{ $reading->device?->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-500">Thời gian</span>
                        <span class="text-sm font-bold text-slate-800">{{ $reading->recorded_at?->format('d/m/Y H:i') }}</span>
                    </div>
                    @if($reading->watts)
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-500">Công suất (W)</span>
                        <span class="text-sm font-bold text-slate-800">{{ number_format($reading->watts, 1) }}</span>
                    </div>
                    @endif
                    @if($reading->kwh)
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-500">Sản lượng (kWh)</span>
                        <span class="text-sm font-bold text-slate-800">{{ number_format($reading->kwh, 3) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-500">Nguồn</span>
                        <span class="px-2 py-0.5 rounded text-xs font-semibold uppercase border bg-primary-50 text-primary-600 border-primary-150">{{ $reading->source }}</span>
                    </div>
                </div>
            </div>

            <div class="mt-6 text-center">
                <a href="{{ route('energy.index') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-800">Quay lại danh sách</a>
            </div>
        </div>
    </div>
</x-app-layout>
