<?php

namespace Modules\Expense\Imports\Parsers;

use Carbon\Carbon;

class VcbParser implements BankParser
{
    /**
     * Detect VCB CSV format by Vietnamese column names.
     */
    public function detect(array $headers): bool
    {
        $lowerHeaders = array_map(fn (string $h) => mb_strtolower(trim($h), 'UTF-8'), $headers);

        $required = ['ngày giao dịch', 'số tiền ghi nợ', 'số tiền ghi có', 'nội dung chi tiết'];

        foreach ($required as $col) {
            if (! in_array($col, $lowerHeaders, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Parse VCB CSV rows into normalized transaction data.
     *
     * Expected columns: Ngày giao dịch, Số tiền ghi nợ, Số tiền ghi có, Nội dung chi tiết
     */
    public function parse(array $rows): array
    {
        $transactions = [];

        foreach ($rows as $row) {
            // Normalize keys to lowercase
            $normalized = [];
            foreach ($row as $key => $value) {
                $normalized[mb_strtolower(trim((string) $key), 'UTF-8')] = trim((string) ($value ?? ''));
            }

            $dateStr = $normalized['ngày giao dịch'] ?? '';
            $debitStr = $normalized['số tiền ghi nợ'] ?? '0';
            $creditStr = $normalized['số tiền ghi có'] ?? '0';
            $description = $normalized['nội dung chi tiết'] ?? '';
            $reference = $normalized['số tham chiếu'] ?? null;

            if (empty($dateStr) || empty($description)) {
                continue;
            }

            $debit = $this->parseAmount($debitStr);
            $credit = $this->parseAmount($creditStr);

            if ($debit > 0) {
                $amount = $debit;
                $type = 'expense';
            } elseif ($credit > 0) {
                $amount = $credit;
                $type = 'income';
            } else {
                continue;
            }

            $date = $this->parseDate($dateStr);

            $transactions[] = [
                'date' => $date->format('Y-m-d'),
                'amount' => $amount,
                'type' => $type,
                'description' => $description,
                'reference' => $reference,
            ];
        }

        return $transactions;
    }

    private function parseAmount(string $value): float
    {
        // Remove VND notation and thousand separators
        $clean = str_replace(['đ', 'vnđ', 'VND', 'vnd', '.', ','], ['', '', '', '', '', '.'], trim($value));
        $clean = preg_replace('/[^0-9.\-]/', '', $clean);

        return is_numeric($clean) ? (float) $clean : 0.0;
    }

    private function parseDate(string $value): Carbon
    {
        // VCB typically uses dd/mm/yyyy
        try {
            return Carbon::createFromFormat('d/m/Y', trim($value)) ?: now();
        } catch (\Throwable) {
            try {
                return Carbon::parse(trim($value));
            } catch (\Throwable) {
                return now();
            }
        }
    }
}
