<?php

namespace Modules\Expense\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Expense\Models\Transfer;
use Modules\Wallet\Models\Wallet;

class ExpenseReportExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithStyles, WithColumnWidths
{
    public function __construct(
        private int $homeId,
        private int $year,
        private int $month,
    ) {}

    public function title(): string
    {
        return sprintf('Báo cáo %04d-%02d', $this->year, $this->month);
    }

    public function headings(): array
    {
        return [
            'Loại',
            'Danh mục',
            'Số tiền',
            'Mô tả',
            'Ví',
            'Ngày giao dịch',
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 12, 'B' => 25, 'C' => 18, 'D' => 40, 'E' => 22, 'F' => 16];
    }

    public function styles(Worksheet $sheet): void
    {
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:F1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');
    }

    public function array(): array
    {
        $start = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $debtCatIds = ExpenseCategory::where('home_id', $this->homeId)
            ->whereNull('deleted_at')
            ->whereIn('category_group', ExpenseCategory::DEBT_GROUPS)
            ->pluck('id');

        $expenses = Expense::where('home_id', $this->homeId)
            ->whereNull('transfer_id')
            ->whereNotIn('category_id', $debtCatIds)
            ->whereBetween('occurred_at', [$start, $end])
            ->with(['category', 'wallet'])
            ->orderBy('occurred_at')
            ->get();

        $data = [];
        foreach ($expenses as $e) {
            $data[] = [
                $e->type === 'income' ? 'Thu nhập' : 'Chi tiêu',
                $e->category?->name ?? '—',
                (float) $e->amount,
                $e->description ?: '—',
                $e->wallet?->name ?? '—',
                $e->occurred_at?->format('d/m/Y H:i') ?? '—',
            ];
        }

        // Summary rows
        $data[] = ['', '', '', '', '', ''];
        $totalIncome = $expenses->where('type', 'income')->sum('amount');
        $totalExpense = $expenses->where('type', 'expense')->sum('amount');

        $data[] = ['TỔNG THU NHẬP', '', (float) $totalIncome, '', '', ''];
        $data[] = ['TỔNG CHI TIÊU', '', (float) $totalExpense, '', '', ''];
        $data[] = ['CHÊNH LỆCH', '', (float) $totalIncome - (float) $totalExpense, '', '', ''];

        // Transfer summary
        $transferVolume = (float) Transfer::where('home_id', $this->homeId)->whereBetween('occurred_at', [$start, $end])->sum('amount');
        $data[] = ['', '', '', '', '', ''];
        $data[] = ['TỔNG CHUYỂN VÍ', '', $transferVolume, '', '', ''];

        // Wallet balances
        $wallets = Wallet::where('home_id', $this->homeId)->where('is_archived', false)->get();
        $data[] = ['', '', '', '', '', ''];
        foreach ($wallets as $w) {
            $data[] = ['SỐ DƯ VÍ: ' . $w->name, '', (float) $w->netBalance(), $w->type, '', ''];
        }

        return $data;
    }
}
