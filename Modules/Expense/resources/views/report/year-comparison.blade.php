<x-expense::report.layout :home="$home" :homes="$homes">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @if(!$home)
        <div class="text-center py-20"><p class="text-slate-500 dark:text-slate-400">Vui lòng chọn một ngôi nhà.</p></div>
    @else
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            @foreach($report['years'] as $year => $data)
                <x-stat-card
                    label="Năm {{ $year }}"
                    :value="$data['net']"
                    icon="📅"
                    :trend="$loop->first ? null : round((($data['net'] - $report['years'][$loop->index > 0 ? $year-1 : $year]['net']) / max(abs($report['years'][$loop->index > 0 ? $year-1 : $year]['net']), 1)) * 100, 1)"
                    :trendLabel="$loop->first ? null : 'vs năm trước'" />
            @endforeach
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 mb-6">
            <h3 class="font-bold text-slate-800 dark:text-slate-100 mb-4">So sánh chi tiêu theo tháng qua các năm</h3>
            <canvas id="comparisonChart" height="100"></canvas>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
            <h3 class="font-bold text-slate-800 dark:text-slate-100 mb-4">Lũy kế thu nhập - chi tiêu</h3>
            <canvas id="cumulativeChart" height="100"></canvas>
        </div>

        <script>
        const yearColors = ['#3B82F6','#10B981','#F59E0B'];
        const years = {!! json_encode(array_keys($report['years'])) !!};

        // Monthly comparison chart
        new Chart(document.getElementById('comparisonChart'), {
            type: 'bar',
            data: {
                labels: {!! json_encode($report['labels']) !!},
                datasets: years.map((year, i) => ({
                    label: 'Chi tiêu ' + year,
                    data: {!! json_encode(array_values(array_map(fn($d) => $d['expense'] ?? 0, array_values($report['years'])[0]['monthly'] ?? []))) !!}.map((_, monthIdx) => {
                        const yData = {!! json_encode(array_values($report['years'])) !!};
                        return yData[i]?.monthly?.[monthIdx + 1]?.expense || 0;
                    }),
                    backgroundColor: yearColors[i] + '99',
                    borderColor: yearColors[i],
                    borderWidth: 1,
                    borderRadius: 4,
                }))
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'top' } },
                scales: { y: { ticks: { callback: v => (v/1000000).toFixed(0) + 'tr' } } }
            }
        });

        // Cumulative net chart
        const cumDatasets = years.map((year, i) => {
            let cum = 0;
            const data = [];
            const yData = {!! json_encode(array_values($report['years'])) !!}[i];
            if (!yData?.monthly) return { label: year, data: [] };
            for (let m = 1; m <= 12; m++) {
                const net = (yData.monthly[m]?.income || 0) - (yData.monthly[m]?.expense || 0);
                cum += net;
                data.push(Math.round(cum));
            }
            return { label: 'Lũy kế ' + year, data, borderColor: yearColors[i], fill: false, tension: 0.3 };
        });

        new Chart(document.getElementById('cumulativeChart'), {
            type: 'line',
            data: { labels: {!! json_encode($report['labels']) !!}, datasets: cumDatasets },
            options: {
                responsive: true,
                plugins: { legend: { position: 'top' } },
                scales: { y: { ticks: { callback: v => (v/1000000).toFixed(1) + 'tr' } } }
            }
        });
        </script>
    @endif
</x-expense::report.layout>
