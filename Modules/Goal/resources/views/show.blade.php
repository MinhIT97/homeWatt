<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('goal.index', ['home_id' => $goal->home_id]) }}" class="text-slate-400 hover:text-slate-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <h2 class="font-extrabold text-xl sm:text-2xl text-slate-900 tracking-tight font-outfit">
                {{ $goal->name }}
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Progress Card -->
            <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6">
                @php
                    $pct = $goal->percentage();
                @endphp
                <div class="flex items-center gap-3 mb-4">
                    <span class="text-3xl">{{ $goal->icon ?: '🎯' }}</span>
                    <div>
                        <span @class([
                            'px-2 py-0.5 rounded-full text-[10px] font-bold',
                            'bg-emerald-100 text-emerald-700' => $goal->status === 'completed',
                            'bg-blue-100 text-blue-700' => $goal->status === 'active',
                            'bg-slate-100 text-slate-500' => $goal->status === 'cancelled',
                        ])>
                            {{ $goal->status === 'completed' ? 'Hoàn thành' : ($goal->status === 'active' ? 'Đang thực hiện' : 'Đã hủy') }}
                        </span>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="flex justify-between text-xs font-bold text-slate-600 mb-2">
                        <span>{{ number_format($goal->current_amount, 0, ',', '.') }} đ</span>
                        <span>{{ $pct }}%</span>
                        <span>{{ number_format($goal->target_amount, 0, ',', '.') }} đ</span>
                    </div>
                    <div class="bg-slate-100 rounded-full h-3 overflow-hidden">
                        <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-cyan-400 transition-all duration-500" style="width: {{ min($pct, 100) }}%"></div>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Loại</p>
                        @php
                            $typeLabels = [
                                'savings' => 'Tiết kiệm',
                                'debt_payoff' => 'Trả nợ',
                                'energy_reduction' => 'Giảm điện',
                                'expense_limit' => 'Hạn mức chi',
                                'income_target' => 'Mục tiêu thu',
                            ];
                        @endphp
                        <p class="text-sm font-bold text-slate-700">{{ $typeLabels[$goal->type] ?? $goal->type }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Bắt đầu</p>
                        <p class="text-sm font-bold text-slate-700">{{ $goal->starts_at->format('d/m/Y') }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Kết thúc</p>
                        <p class="text-sm font-bold text-slate-700">{{ $goal->ends_at->format('d/m/Y') }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Còn lại</p>
                        <p class="text-sm font-bold text-slate-700">{{ number_format(max(0, $goal->target_amount - $goal->current_amount), 0, ',', '.') }} đ</p>
                    </div>
                </div>
            </div>

            <!-- Progress Chart -->
            @if(count($snapshotDates) > 0)
                <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6">
                    <h3 class="font-extrabold text-slate-800 font-outfit text-base mb-4">Biểu đồ tiến độ</h3>
                    <canvas id="goalProgressChart" class="h-64"></canvas>
                </div>
            @endif

            <!-- Snapshots Table -->
            @if($goal->snapshots->isNotEmpty())
                <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6">
                    <h3 class="font-extrabold text-slate-800 font-outfit text-base mb-4">Lịch sử tiến độ</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 text-left">
                                    <th class="pb-2 text-[10px] font-bold text-slate-400 uppercase">Ngày</th>
                                    <th class="pb-2 text-[10px] font-bold text-slate-400 uppercase">Giá trị hiện tại</th>
                                    <th class="pb-2 text-[10px] font-bold text-slate-400 uppercase">Phần trăm</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($goal->snapshots->reverse()->take(30) as $snapshot)
                                    <tr class="border-b border-slate-50">
                                        <td class="py-2 text-slate-700 font-medium">{{ $snapshot->snapshot_date->format('d/m/Y') }}</td>
                                        <td class="py-2 text-slate-700 font-bold">{{ number_format($snapshot->current_amount, 0, ',', '.') }} đ</td>
                                        <td class="py-2">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700">{{ $snapshot->percentage }}%</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Actions -->
            <div class="flex justify-end gap-3">
                <a href="{{ route('goal.edit', $goal->id) }}" class="px-4 py-2.5 border border-slate-200 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition">Chỉnh sửa</a>
                <form method="POST" action="{{ route('goal.destroy', $goal->id) }}" onsubmit="return confirm('Bạn có chắc muốn hủy mục tiêu này?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2.5 border border-red-200 rounded-xl text-xs font-bold text-red-600 hover:bg-red-50 transition">Hủy mục tiêu</button>
                </form>
            </div>
        </div>
    </div>

    @if(count($snapshotDates) > 0)
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const ctx = document.getElementById('goalProgressChart');
                if (ctx) {
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: @json($snapshotDates),
                            datasets: [{
                                label: 'Tiến độ (%)',
                                data: @json($snapshotPercentages),
                                borderColor: '#3B82F6',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                fill: true,
                                tension: 0.4,
                                pointRadius: 3,
                                pointHoverRadius: 5,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: { min: 0, max: 100, ticks: { stepSize: 20 } },
                            },
                            plugins: {
                                legend: { display: false },
                            },
                        },
                    });
                }
            });
        </script>
    @endif
</x-app-layout>
