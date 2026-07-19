<x-expense.report.layout :home="$home" :homes="$homes">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @if(!$home)
        <div class="text-center py-20"><p class="text-slate-500 dark:text-slate-400">Vui lòng chọn một ngôi nhà.</p></div>
    @else
        <div class="flex items-center gap-3 mb-6">
            <form method="GET" class="flex items-center gap-2">
                <input type="hidden" name="home_id" value="{{ $home->id }}">
                <select name="year" onchange="this.form.submit()" class="bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-lg text-sm px-3 py-1.5 font-semibold">
                    @for($y = now()->year; $y >= now()->year - 5; $y--)
                        <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                    @endfor
                </select>
                <select name="type" onchange="this.form.submit()" class="bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-lg text-sm px-3 py-1.5 font-semibold">
                    <option value="expense" @selected($type == 'expense')>Chi tiêu</option>
                    <option value="income" @selected($type == 'income')>Thu nhập</option>
                </select>
            </form>
        </div>

        <x-stat-card label="{{ $type == 'expense' ? 'Tổng chi tiêu' : 'Tổng thu nhập' }} năm {{ $year }}" :value="$report['year_total']" icon="📈" />

        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 mt-6">
            <h3 class="font-bold text-slate-800 dark:text-slate-100 mb-4">Xu hướng {{ $type == 'expense' ? 'chi tiêu' : 'thu nhập' }} theo danh mục — {{ $year }}</h3>
            <canvas id="trendChart" height="120"></canvas>
        </div>

        <script>
        const colors = ['#3B82F6','#10B981','#F59E0B','#8B5CF6','#EC4899','#06B6D4','#F97316','#6366F1'];
        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: {!! json_encode($report['labels']) !!},
                datasets: [
                    @foreach($report['datasets'] as $catId => $ds)
                    {
                        label: '{{ $ds['label'] }}',
                        data: {!! json_encode(array_values($ds['data'])) !!},
                        borderColor: '{{ $ds['color'] }}',
                        backgroundColor: '{{ $ds['color'] }}22',
                        fill: false,
                        tension: 0.3,
                        pointRadius: 2,
                    },
                    @endforeach
                    {
                        label: 'TỔNG',
                        data: {!! json_encode(array_values($report['monthly_totals'])) !!},
                        borderColor: '#1E293B',
                        borderWidth: 2.5,
                        fill: false,
                        tension: 0.3,
                        pointRadius: 3,
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 16 } } },
                scales: { y: { ticks: { callback: v => (v/1000000).toFixed(1) + 'tr' } } }
            }
        });
        </script>
    @endif
</x-expense.report.layout>
