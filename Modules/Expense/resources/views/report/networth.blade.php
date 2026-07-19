<x-expense::report.layout :home="$home" :homes="$homes">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @if(!$home)
        <div class="text-center py-20"><p class="text-slate-500 dark:text-slate-400">Vui lòng chọn một ngôi nhà.</p></div>
    @else
        <div class="flex items-center gap-3 mb-6">
            <form method="GET" class="flex items-center gap-2">
                <input type="hidden" name="home_id" value="{{ $home->id }}">
                <select name="months" onchange="this.form.submit()" class="bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-lg text-sm px-3 py-1.5 font-semibold">
                    <option value="6" @selected($months == 6)>6 tháng</option>
                    <option value="12" @selected($months == 12)>12 tháng</option>
                    <option value="24" @selected($months == 24)>24 tháng</option>
                </select>
            </form>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
            <x-stat-card label="Tài sản hiện tại" :value="$report['latestBalance']" icon="💰" />
            <x-stat-card label="Thay đổi" :value="abs($report['change'])" icon="{{ $report['change'] >= 0 ? '📈' : '📉' }}" :trend="$report['changePct']" :trendLabel="$report['change'] >= 0 ? 'tăng' : 'giảm'" />
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
            <h3 class="font-bold text-slate-800 dark:text-slate-100 mb-4">Xu hướng tài sản ({{ $months }} tháng)</h3>
            <canvas id="networthChart" height="100"></canvas>
        </div>

        <script>
        new Chart(document.getElementById('networthChart'), {
            type: 'line',
            data: {
                labels: {!! json_encode($report['labels']) !!},
                datasets: [{
                    label: 'Tài sản ròng',
                    data: {!! json_encode($report['balanceSeries']) !!},
                    borderColor: '#6366F1',
                    backgroundColor: 'rgba(99,102,241,0.15)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'top' } },
                scales: { y: { ticks: { callback: v => (v/1000000).toFixed(1) + 'tr' } } }
            }
        });
        </script>
    @endif
</x-expense::report.layout>
