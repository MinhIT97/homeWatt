<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Báo cáo HomeWatt - {{ $home->name }} - {{ sprintf('%04d-%02d', $year, $month) }}</title>
    <style>
        @page { margin: 15mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; line-height: 1.5; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #3B82F6; padding-bottom: 12px; }
        .header h1 { font-size: 20px; color: #1e293b; margin: 0 0 4px; }
        .header p { color: #64748b; margin: 0; font-size: 10px; }
        .metrics { margin-bottom: 20px; }
        .metrics table { width: 100%; border-collapse: collapse; }
        .metrics td { padding: 10px 14px; border: 1px solid #e2e8f0; text-align: center; width: 25%; }
        .metrics .label { font-size: 9px; color: #64748b; text-transform: uppercase; }
        .metrics .value { font-size: 16px; font-weight: bold; }
        .metrics .positive { color: #10B981; }
        .metrics .negative { color: #EF4444; }
        h2 { font-size: 14px; color: #334155; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px; margin: 20px 0 10px; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 10px; }
        table.data th { background: #f1f5f9; padding: 8px 10px; text-align: left; font-weight: bold; color: #475569; border-bottom: 2px solid #e2e8f0; }
        table.data td { padding: 6px 10px; border-bottom: 1px solid #f1f5f9; }
        table.data .amount { text-align: right; font-weight: bold; }
        table.data .income { color: #10B981; }
        table.data .expense { color: #EF4444; }
        .footer { margin-top: 30px; text-align: center; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>BÁO CÁO TÀI CHÍNH</h1>
        <p>{{ $home->name }} — Tháng {{ sprintf('%02d', $month) }}/{{ $year }} — Xuất lúc {{ $generatedAt }}</p>
    </div>

    <div class="metrics">
        <table>
            <tr>
                <td>
                    <div class="label">Thu nhập</div>
                    <div class="value positive">{{ number_format($report['totalIncome'], 0, ',', '.') }} đ</div>
                </td>
                <td>
                    <div class="label">Chi tiêu</div>
                    <div class="value negative">{{ number_format($report['totalExpense'], 0, ',', '.') }} đ</div>
                </td>
                <td>
                    <div class="label">Chênh lệch</div>
                    <div class="value {{ $report['totalIncome'] - $report['totalExpense'] >= 0 ? 'positive' : 'negative' }}">
                        {{ $report['totalIncome'] - $report['totalExpense'] >= 0 ? '+' : '' }}{{ number_format($report['totalIncome'] - $report['totalExpense'], 0, ',', '.') }} đ
                    </div>
                </td>
                <td>
                    <div class="label">Số dư</div>
                    <div class="value">{{ number_format($report['totalBalance'], 0, ',', '.') }} đ</div>
                </td>
            </tr>
        </table>
    </div>

    <h2>Thu nhập theo danh mục</h2>
    @if($report['incomeByCategory']->isNotEmpty())
        <table class="data">
            <tr><th>Danh mục</th><th>Số giao dịch</th><th class="amount">Tổng</th><th class="amount">Tỷ trọng</th></tr>
            @foreach($report['incomeByCategory'] as $row)
                <tr>
                    <td>{{ $row->category?->icon }} {{ $row->category?->name }}</td>
                    <td>{{ $row->count }}</td>
                    <td class="amount income">{{ number_format((float)$row->total, 0, ',', '.') }} đ</td>
                    <td class="amount">{{ $report['totalIncome'] > 0 ? round(((float)$row->total / $report['totalIncome']) * 100, 1) : 0 }}%</td>
                </tr>
            @endforeach
        </table>
    @else
        <p style="color:#94a3b8">Chưa có thu nhập trong tháng.</p>
    @endif

    <h2>Chi tiêu theo danh mục</h2>
    @if($report['expenseByCategory']->isNotEmpty())
        <table class="data">
            <tr><th>Danh mục</th><th>Số giao dịch</th><th class="amount">Tổng</th><th class="amount">Tỷ trọng</th></tr>
            @foreach($report['expenseByCategory'] as $row)
                <tr>
                    <td>{{ $row->category?->icon }} {{ $row->category?->name }}</td>
                    <td>{{ $row->count }}</td>
                    <td class="amount expense">{{ number_format((float)$row->total, 0, ',', '.') }} đ</td>
                    <td class="amount">{{ $report['totalExpense'] > 0 ? round(((float)$row->total / $report['totalExpense']) * 100, 1) : 0 }}%</td>
                </tr>
            @endforeach
        </table>
    @else
        <p style="color:#94a3b8">Chưa có chi tiêu trong tháng.</p>
    @endif

    <h2>Top 5 chi tiêu lớn nhất</h2>
    @if($report['topExpenses']->isNotEmpty())
        <table class="data">
            <tr><th>Mô tả</th><th>Danh mục</th><th>Ngày</th><th class="amount">Số tiền</th></tr>
            @foreach($report['topExpenses']->take(5) as $e)
                <tr>
                    <td>{{ $e->description ?: '—' }}</td>
                    <td>{{ $e->category?->name }}</td>
                    <td>{{ $e->occurred_at?->format('d/m/Y') }}</td>
                    <td class="amount expense">{{ number_format((float)$e->amount, 0, ',', '.') }} đ</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if($categoryReport && $categoryReport['rows']->isNotEmpty())
    <h2>Chi tiết danh mục ({{ $categoryReport['from'] }} — {{ $categoryReport['to'] }})</h2>
    <table class="data">
        <tr><th>Danh mục</th><th>Loại</th><th>Số giao dịch</th><th class="amount">Tổng</th></tr>
        @foreach($categoryReport['rows'] as $row)
            <tr>
                <td>{{ $row->icon ?? '📝' }} {{ $row->name }}</td>
                <td>{{ $row->type === 'income' ? 'Thu nhập' : 'Chi tiêu' }}</td>
                <td>{{ $row->count }}</td>
                <td class="amount {{ $row->type === 'income' ? 'income' : 'expense' }}">{{ number_format((float)$row->total, 0, ',', '.') }} đ</td>
            </tr>
        @endforeach
    </table>
    @endif

    <div class="footer">
        <p>Báo cáo được tạo tự động bởi HomeWatt — {{ $generatedAt }}</p>
        <p>Dữ liệu có thể thay đổi khi có giao dịch mới. Đây không phải báo cáo tài chính chính thức.</p>
    </div>
</body>
</html>
