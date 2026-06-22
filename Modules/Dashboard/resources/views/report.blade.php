<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Báo Cáo Tiền Điện — {{ $home->name }} {{ $month }}/{{ $year }}</title>
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
    <h1>Báo Cáo Tiền Điện</h1>
    <p class="sub">{{ $home->name }} — Tháng {{ $month }}/{{ $year }}</p>

    <div class="kpi">
        <div class="kpi-card">
            <div class="kpi-value">{{ number_format($monthlyKwh, 1) }} kWh</div>
            <div class="kpi-label">Tổng tiêu thụ</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-value">{{ number_format($monthlyCost) }} đ</div>
            <div class="kpi-label">Ước tính tiền điện</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-value">{{ $readings->count() }}</div>
            <div class="kpi-label">Lần đo</div>
        </div>
    </div>

    @if($readings->isNotEmpty())
        <h3>Số đo đã ghi nhận</h3>
        <table>
            <thead>
                <tr>
                    <th>Thiết bị</th>
                    <th>Thời gian</th>
                    <th>Watt</th>
                    <th>kWh</th>
                    <th>Nguồn</th>
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
        <h3>Ước tính tiêu thụ</h3>
        <table>
            <thead>
                <tr>
                    <th>Thiết bị</th>
                    <th>Phương pháp</th>
                    <th>kWh ước tính</th>
                    <th>Chi phí</th>
                    <th>Độ tin cậy</th>
                </tr>
            </thead>
            <tbody>
                @foreach($estimates as $e)
                <tr>
                    <td>{{ $e->device?->name }}</td>
                    <td>{{ $e->method }}</td>
                    <td>{{ number_format($e->estimated_kwh, 1) }}</td>
                    <td>{{ number_format($e->estimated_cost) }} đ</td>
                    <td>{{ round($e->confidence * 100) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        Xuất bởi HomeWatt — {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
