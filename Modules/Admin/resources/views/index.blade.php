<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 font-outfit leading-tight">Bảng Điều Khiển Admin</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Stats cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="glass-panel rounded-2xl border border-slate-200/60 p-5 shadow-sm bg-white/70 hover:scale-[1.02] transition duration-200">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tổng ngôi nhà</p>
                    <p class="text-3xl font-extrabold text-gradient-purple-cyan font-outfit mt-1">{{ $stats['total_homes'] }}</p>
                </div>
                <div class="glass-panel rounded-2xl border border-slate-200/60 p-5 shadow-sm bg-white/70 hover:scale-[1.02] transition duration-200">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Loại thiết bị</p>
                    <p class="text-3xl font-extrabold text-primary-650 font-outfit mt-1">{{ $stats['total_device_types'] }}</p>
                </div>
                <div class="glass-panel rounded-2xl border border-slate-200/60 p-5 shadow-sm bg-white/70 hover:scale-[1.02] transition duration-200">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Biểu giá điện</p>
                    <p class="text-3xl font-extrabold text-accent-650 font-outfit mt-1">{{ $stats['total_tariff_plans'] }}</p>
                </div>
                <div class="glass-panel rounded-2xl border border-slate-200/60 p-5 shadow-sm bg-white/70 hover:scale-[1.02] transition duration-200">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Phân tích AI hôm nay</p>
                    <p class="text-3xl font-extrabold text-emerald-650 font-outfit mt-1">{{ $stats['ai_usage_today'] }}</p>
                </div>
            </div>

            <!-- AI Analyses list -->
            <div class="glass-panel rounded-2xl border border-slate-200/60 shadow-sm bg-white/70 overflow-hidden">
                <div class="px-6 py-4.5 border-b border-slate-100 bg-slate-50/40">
                    <h3 class="font-bold text-slate-850 font-outfit">Lịch sử phân tích AI gần đây</h3>
                </div>
                @if($recentAnalyses->isEmpty())
                    <div class="p-8 text-slate-500 text-sm text-center">Chưa có hoạt động phân tích AI nào được ghi nhận.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50/80">
                                <tr>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Người dùng</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Trạng thái</th>
                                    <th class="px-6 py-3.5 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Chi phí AI</th>
                                    <th class="px-6 py-3.5 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Thời gian</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($recentAnalyses as $a)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-6 py-4 text-sm font-bold text-slate-850">{{ $a->user?->name }}</td>
                                        <td class="px-6 py-4">
                                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold border capitalize
                                                @if($a->status === 'completed') bg-green-50 text-green-700 border-green-200
                                                @elseif($a->status === 'failed') bg-red-50 text-red-700 border-red-200
                                                @else bg-amber-50 text-amber-700 border-amber-200
                                                @endif">
                                                {{ $a->status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-semibold text-slate-700 text-right">
                                            ${{ number_format($a->result?->cost ?? 0, 6) }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-500 font-semibold text-right">
                                            {{ $a->created_at->format('Y-m-d H:i') }}
                                        </td>
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
