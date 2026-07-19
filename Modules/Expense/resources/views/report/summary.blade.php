<x-expense.report.layout :home="$home" :homes="$homes">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @if(!$home)
        <div class="text-center py-20">
            <p class="text-4xl mb-3">📊</p>
            <p class="text-slate-500 dark:text-slate-400">Vui lòng chọn một ngôi nhà để xem báo cáo.</p>
        </div>
    @else
        @php
            $netIncome = $report['totalIncome'] - $report['totalExpense'];
            $savingsRate = $report['totalIncome'] > 0 ? round(($netIncome / $report['totalIncome']) * 100, 1) : 0;
        @endphp

        {{-- Key Metrics --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <x-stat-card label="Thu nhập" :value="$report['totalIncome']" icon="🟢" />
            <x-stat-card label="Chi tiêu" :value="$report['totalExpense']" icon="🔴" />
            <x-stat-card label="Chênh lệch" :value="$netIncome" icon="{{ $netIncome >= 0 ? '✅' : '⚠️' }}" :trend="$report['totalIncome'] > 0 ? $savingsRate : null" trendLabel="tỷ lệ tiết kiệm" />
            <x-stat-card label="Chuyển ví" :value="$report['transferVolume']" icon="🔁" />
            <x-stat-card label="Số dư" :value="$report['totalBalance']" icon="💰" />
        </div>

        {{-- Debt Summary --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-3 text-center">
                <p class="text-xs text-amber-600 dark:text-amber-400 font-semibold">Cho vay</p>
                <p class="text-lg font-bold text-amber-700 dark:text-amber-300">{{ number_format($report['debtGiven'], 0, ',', '.') }} đ</p>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-3 text-center">
                <p class="text-xs text-green-600 dark:text-green-400 font-semibold">Thu nợ</p>
                <p class="text-lg font-bold text-green-700 dark:text-green-300">{{ number_format($report['debtReceived'], 0, ',', '.') }} đ</p>
            </div>
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3 text-center">
                <p class="text-xs text-slate-500 font-semibold">Số ví</p>
                <p class="text-lg font-bold text-slate-700 dark:text-slate-300">{{ $report['walletCount'] }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3 text-center">
                <p class="text-xs text-slate-500 font-semibold">Tổng giao dịch</p>
                <p class="text-lg font-bold text-slate-700 dark:text-slate-300">{{ $report['incomeByCategory']->sum('count') + $report['expenseByCategory']->sum('count') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            {{-- Income by Category Pie --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
                <h3 class="font-bold text-slate-800 dark:text-slate-100 mb-4">Thu nhập theo danh mục</h3>
                @if($report['incomeByCategory']->isNotEmpty())
                    <canvas id="incomeChart" height="200"></canvas>
                @else
                    <p class="text-slate-400 text-sm text-center py-8">Chưa có thu nhập trong tháng</p>
                @endif
            </div>

            {{-- Expense by Category Pie --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
                <h3 class="font-bold text-slate-800 dark:text-slate-100 mb-4">Chi tiêu theo danh mục</h3>
                @if($report['expenseByCategory']->isNotEmpty())
                    <canvas id="expenseChart" height="200"></canvas>
                @else
                    <p class="text-slate-400 text-sm text-center py-8">Chưa có chi tiêu trong tháng</p>
                @endif
            </div>
        </div>

        {{-- Daily Chart --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 mb-6">
            <h3 class="font-bold text-slate-800 dark:text-slate-100 mb-4">Chi tiêu hàng ngày (tháng {{ sprintf('%02d', $month) }})</h3>
            <canvas id="dailyChart" height="80"></canvas>
        </div>

        {{-- Top Expenses Table --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
                <h3 class="font-bold text-slate-800 dark:text-slate-100 mb-3">🔴 Chi tiêu lớn nhất</h3>
                <div class="space-y-2">
                    @foreach($report['topExpenses'] as $e)
                        <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-700 last:border-0">
                            <div class="flex items-center gap-2 min-w-0">
                                <span>{{ $e->category?->icon ?? '📝' }}</span>
                                <span class="text-sm text-slate-700 dark:text-slate-300 truncate">{{ $e->description ?: $e->category?->name }}</span>
                            </div>
                            <span class="text-sm font-bold text-red-600 dark:text-red-400 shrink-0 ml-2">{{ number_format((float)$e->amount, 0, ',', '.') }} đ</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
                <h3 class="font-bold text-slate-800 dark:text-slate-100 mb-3">🟢 Thu nhập lớn nhất</h3>
                <div class="space-y-2">
                    @foreach($report['topIncomes'] as $e)
                        <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-700 last:border-0">
                            <div class="flex items-center gap-2 min-w-0">
                                <span>{{ $e->category?->icon ?? '💰' }}</span>
                                <span class="text-sm text-slate-700 dark:text-slate-300 truncate">{{ $e->description ?: $e->category?->name }}</span>
                            </div>
                            <span class="text-sm font-bold text-green-600 dark:text-green-400 shrink-0 ml-2">{{ number_format((float)$e->amount, 0, ',', '.') }} đ</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const colors = ['#3B82F6','#10B981','#F59E0B','#8B5CF6','#EC4899','#06B6D4','#F97316','#6366F1','#84CC16','#14B8A6'];

            @if($report['incomeByCategory']->isNotEmpty())
            new Chart(document.getElementById('incomeChart'), {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode($report['incomeByCategory']->pluck('category.name')) !!},
                    datasets: [{
                        data: {!! json_encode($report['incomeByCategory']->pluck('total')->map(fn($v) => (float)$v)->values()) !!},
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: { responsive: true, cutout: '65%', plugins: { legend: { position: 'bottom', labels: { padding: 12, usePointStyle: true } } } }
            });
            @endif

            @if($report['expenseByCategory']->isNotEmpty())
            new Chart(document.getElementById('expenseChart'), {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode($report['expenseByCategory']->pluck('category.name')) !!},
                    datasets: [{
                        data: {!! json_encode($report['expenseByCategory']->pluck('total')->map(fn($v) => (float)$v)->values()) !!},
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: { responsive: true, cutout: '65%', plugins: { legend: { position: 'bottom', labels: { padding: 12, usePointStyle: true } } } }
            });
            @endif

            {{-- Daily bar chart --}}
            new Chart(document.getElementById('dailyChart'), {
                type: 'bar',
                data: {
                    labels: {!! json_encode(range(1, $report['daysInMonth'])) !!},
                    datasets: [
                        {
                            label: 'Thu nhập',
                            data: {!! json_encode(array_map(fn($d) => $d['income'], $report['dailyData'])) !!},
                            backgroundColor: '#10B981',
                            borderRadius: 4,
                        },
                        {
                            label: 'Chi tiêu',
                            data: {!! json_encode(array_map(fn($d) => $d['expense'], $report['dailyData'])) !!},
                            backgroundColor: '#EF4444',
                            borderRadius: 4,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'top' } },
                    scales: {
                        x: { stacked: false, grid: { display: false } },
                        y: { stacked: false, ticks: { callback: v => (v/1000).toFixed(0) + 'k' } }
                    }
                }
            });
        });
        </script>
    @endif
</x-expense.report.layout>
