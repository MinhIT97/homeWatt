<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">Biểu Giá Điện & Thuế</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 overflow-hidden">
                <div class="px-6 py-4.5 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-0 bg-slate-50/40">
                    <h3 class="font-bold text-slate-850 font-outfit">Tất cả biểu giá</h3>
                    <a href="{{ route('tariff.create') }}" class="inline-flex items-center justify-center px-4 py-2 border border-primary-300 text-primary-600 hover:bg-primary-50 rounded-xl text-sm font-semibold transition w-full sm:w-auto text-center">+ Thêm biểu giá</a>
                </div>
                @if($plans->isEmpty())
                    <div class="p-8 text-slate-500 text-sm text-center">Chưa có biểu giá điện nào được tạo.</div>
                @else
                    <!-- Mobile Card List View -->
                    <div class="block sm:hidden divide-y divide-slate-100">
                        @foreach($plans as $plan)
                            <a href="{{ route('tariff.show', $plan) }}" class="block p-4 hover:bg-slate-50/50 transition">
                                <div class="flex justify-between items-start mb-2 gap-2">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="text-sm font-bold text-primary-600 font-outfit">{{ $plan->name }}</span>
                                        @if($plan->is_system)
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-500 border border-slate-200">Mẫu</span>
                                        @endif
                                    </div>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200 shrink-0">{{ $plan->provider ?? '—' }}</span>
                                </div>
                                <div class="flex justify-between items-center text-xs text-slate-500 mt-1">
                                    <span class="font-medium flex items-center gap-1 text-slate-650">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                        {{ $plan->tiers->count() }} bậc
                                    </span>
                                    <span class="font-semibold text-slate-450">{{ $plan->effective_from->format('Y-m-d') }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    <!-- Desktop Table View -->
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50/80">
                                <tr>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Tên biểu giá</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Nhà cung cấp</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Số bậc/bậc giá</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Ngày hiệu lực</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($plans as $plan)
                                    <tr class="hover:bg-slate-50/50 cursor-pointer transition" onclick="location.href='{{ route('tariff.show', $plan) }}'">
                                        <td class="px-6 py-4 text-sm font-bold text-primary-600 hover:text-primary-850">
                                            <div class="flex items-center gap-2">
                                                <span>{{ $plan->name }}</span>
                                                @if($plan->is_system)
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">Mẫu hệ thống</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-650 font-medium">{{ $plan->provider ?? '—' }}</td>
                                        <td class="px-6 py-4 text-sm text-slate-650 font-semibold">{{ $plan->tiers->count() }} bậc</td>
                                        <td class="px-6 py-4 text-sm text-slate-500 font-semibold">{{ $plan->effective_from->format('Y-m-d') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
