<x-expense::report.layout :home="$home" :homes="$homes">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @if(!$home)
        <div class="text-center py-20">
            <p class="text-4xl mb-3">💸</p>
            <p class="text-slate-500 dark:text-slate-400">Vui lòng chọn một ngôi nhà để xem dòng tiền.</p>
        </div>
    @else
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <form method="GET" class="flex items-center gap-2">
                    <input type="hidden" name="home_id" value="{{ $home->id }}">
                    <select name="year" onchange="this.form.submit()" class="bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-lg text-sm px-3 py-1.5 font-semibold">
                        @for($y = now()->year; $y >= now()->year - 5; $y--)
                            <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                        @endfor
                    </select>
                    <select name="view" onchange="this.form.submit()" class="bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-lg text-sm px-3 py-1.5 font-semibold">
                        <option value="monthly" @selected($view == 'monthly')>Theo tháng</option>
                        <option value="daily" @selected($view == 'daily')>Theo ngày</option>
                    </select>
                </form>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <x-stat-card label="Tổng thu" :value="$report['totalIncome']" icon="🟢" />
            <x-stat-card label="Tổng chi" :value="$report['totalExpense']" icon="🔴" />
            <x-stat-card label="Lũy kế ròng" :value="$report['cumulativeNet']" icon="{{ $report['cumulativeNet'] >= 0 ? '📈' : '📉' }}" />
            <x-stat-card label="Chuyển ví" :value="$report['transferVolume']" icon="🔁" />
        </div>

        {{-- Cash Flow Chart --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 mb-6">
            <h3 class="font-bold text-slate-800 dark:text-slate-100 mb-4">Biểu đồ dòng tiền {{ $year }}</h3>
            <canvas id="cashflowChart" height="100"></canvas>
        </div>

        {{-- Data Table --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-700/50 text-left">
                            <th class="px-4 py-3 font-semibold text-slate-600 dark:text-slate-400">{{ $view == 'daily' ? 'Ngày' : 'Tháng' }}</th>
                            <th class="px-4 py-3 font-semibold text-green-600 text-right">Thu nhập</th>
                            <th class="px-4 py-3 font-semibold text-red-600 text-right">Chi tiêu</th>
                            <th class="px-4 py-3 font-semibold text-right">Chênh lệch</th>
                            <th class="px-4 py-3 font-semibold text-right">Lũy kế</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $cumulative = 0; @endphp
                        @foreach($report['data'] as $period => $d)
                            @php $cumulative += $d['net']; @endphp
                            <tr class="border-t border-slate-100 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/30">
                                <td class="px-4 py-2.5 font-medium text-slate-700 dark:text-slate-300">{{ $period }}</td>
                                <td class="px-4 py-2.5 text-right text-green-600 dark:text-green-400 font-medium">{{ number_format($d['income'], 0, ',', '.') }}</td>
                                <td class="px-4 py-2.5 text-right text-red-600 dark:text-red-400 font-medium">{{ number_format($d['expense'], 0, ',', '.') }}</td>
                                <td class="px-4 py-2.5 text-right font-semibold {{ $d['net'] >= 0 ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                                    {{ $d['net'] >= 0 ? '+' : '' }}{{ number_format($d['net'], 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-semibold text-slate-700 dark:text-slate-300">{{ number_format($cumulative, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <script>
        new Chart(document.getElementById('cashflowChart'), {
            type: 'line',
            data: {
                labels: {!! json_encode($report['labels']) !!},
                datasets: [
                    {
                        label: 'Thu nhập',
                        data: {!! json_encode($report['incomeSeries']) !!},
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16,185,129,0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 3,
                    },
                    {
                        label: 'Chi tiêu',
                        data: {!! json_encode($report['expenseSeries']) !!},
                        borderColor: '#EF4444',
                        backgroundColor: 'rgba(239,68,68,0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 3,
                    },
                    {
                        label: 'Lũy kế ròng',
                        data: {!! json_encode($report['netSeries']) !!},
                        borderColor: '#6366F1',
                        borderWidth: 2,
                        borderDash: [4, 4],
                        fill: false,
                        tension: 0.3,
                        pointRadius: 2,
                        yAxisID: 'y1',
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: { intersect: false, mode: 'index' },
                plugins: { legend: { position: 'top' } },
                scales: {
                    y: {
                        type: 'linear',
                        position: 'left',
                        ticks: { callback: v => (v/1000000).toFixed(1) + 'tr' }
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        ticks: { callback: v => (v/1000000).toFixed(1) + 'tr' }
                    }
                }
            }
        });
        </script>
    @endif
</x-expense::report.layout>
