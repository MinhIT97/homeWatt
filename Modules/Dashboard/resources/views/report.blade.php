<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>{{ __('dashboard.report_title') }} — {{ $home->name }} {{ $month }}/{{ $year }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; padding: 20px; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        .sub { color: #64748b; margin-bottom: 20px; }
        .kpi { display: flex; gap: 16px; margin-bottom: 24px; }
        .kpi-card { flex: 1; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; text-align: center; }
        .kpi-value { font-size: 22px; font-weight: 800; }
        .kpi-label { font-size: 10px; color: #94a3b8; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th { text-align: left; font-size: 10px; color: #94a3b8; text-transform: uppercase; padding: 8px 6px; border-bottom: 2px solid #e2e8f0; }
        td { padding: 8px 6px; border-bottom: 1px solid #f1f5f9; }
        .footer { text-align: center; color: #94a3b8; font-size: 10px; margin-top: 32px; border-top: 1px solid #e2e8f0; padding-top: 12px; }
    </style>
</head>
<body>
    <h1>{{ __('dashboard.report_heading') }}</h1>
    <p class="sub">{{ $home->name }} — {{ __('dashboard.month_prefix') }} {{ $month }}/{{ $year }}</p>

    <div class="kpi">
        <div class="kpi-card">
            <div class="kpi-value">{{ number_format($monthlyKwh, 1) }} kWh</div>
            <div class="kpi-label">{{ __('dashboard.total_consumption') }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-value">{{ number_format($monthlyCost) }} {{ __('common.vnd_currency') }}</div>
            <div class="kpi-label">{{ __('dashboard.estimated_bill') }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-value">{{ $readings->count() }}</div>
            <div class="kpi-label">{{ __('dashboard.readings_count') }}</div>
        </div>
    </div>

    @if($readings->isNotEmpty())
        <h3>{{ __('dashboard.recorded_readings') }}</h3>
        <table>
            <thead>
                <tr>
                    <th>{{ __('device.table_device') }}</th>
                    <th>{{ __('dashboard.table_time') }}</th>
                    <th>{{ __('dashboard.table_watt') }}</th>
                    <th>{{ __('dashboard.table_kwh') }}</th>
                    <th>{{ __('dashboard.table_source') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($readings as $r)
                <tr>
                    <td>{{ $r->device?->name }}</td>
                    <td>{{ $r->recorded_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $r->watts ? number_format($r->watts, 1) : '—' }}</td>
                    <td>{{ $r->kwh ? number_format($r->kwh, 3) : '—' }}</td>
                    <td>{{ $r->source }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($estimates->isNotEmpty())
        <h3>{{ __('dashboard.estimated_consumption') }}</h3>
        <table>
            <thead>
                <tr>
                    <th>{{ __('device.table_device') }}</th>
                    <th>{{ __('dashboard.table_method') }}</th>
                    <th>{{ __('dashboard.table_est_kwh') }}</th>
                    <th>{{ __('common.cost') }}</th>
                    <th>{{ __('dashboard.table_confidence') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($estimates as $e)
                <tr>
                    <td>{{ $e->device?->name }}</td>
                    <td>{{ $e->method }}</td>
                    <td>{{ number_format($e->estimated_kwh, 1) }}</td>
                    <td>{{ number_format($e->estimated_cost) }} {{ __('common.vnd_currency') }}</td>
                    <td>{{ round($e->confidence * 100) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        {{ __('common.exported_by') }} — {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
