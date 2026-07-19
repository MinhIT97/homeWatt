<?php

namespace Modules\Expense\Imports\Parsers;

use Carbon\Carbon;

class MomoParser implements BankParser
{
    /**
     * Detect Momo CSV format by Vietnamese column names.
     */
    public function detect(array $headers): bool
    {
        $lowerHeaders = array_map(fn (string $h) => mb_strtolower(trim($h), 'UTF-8'), $headers);

        $required = ['ngày', 'số tiền', 'nội dung'];

        foreach ($required as $col) {
            if (! in_array($col, $lowerHeaders, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Parse Momo CSV rows into normalized transaction data.
     *
     * Expected columns: Ngày, Số tiền, Nội dung
     * Amount may include +/- sign; positive = income, negative = expense
     */
    public function parse(array $rows): array
    {
        $transactions = [];

        foreach ($rows as $row) {
            $normalized = [];
            foreach ($row as $key => $value) {
                $normalized[mb_strtolower(trim((string) $key), 'UTF-8')] = trim((string) ($value ?? ''));
            }

            $dateStr = $normalized['ngày'] ?? $normalized['ngay'] ?? '';
            $amountStr = $normalized['số tiền'] ?? $normalized['so tien'] ?? '0';
            $description = $normalized['nội dung'] ?? $normalized['noi dung'] ?? '';
            $reference = $normalized['mã giao dịch'] ?? $normalized['ma giao dich'] ?? null;

            if (empty($dateStr) || empty($description)) {
                continue;
            }

            $rawAmount = $this->parseAmount($amountStr);

            if ($rawAmount == 0) {
                continue;
            }

            // Momo: positive = received (income), negative = sent (expense)
            $type = $rawAmount < 0 ? 'expense' : 'income';
            $amount = abs($rawAmount);

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
        // Remove "đ", "VND", and thousand separators
        $clean = str_replace(['đ', 'vnđ', 'VND', 'vnd', ','], ['', '', '', '', ''], trim($value));
        // Replace Vietnamese dot thousand separator, but keep decimal dot
        // If there are multiple dots, the last one is decimal
        if (substr_count($clean, '.') > 1) {
            $parts = explode('.', $clean);
            $decimal = array_pop($parts);
            $clean = implode('', $parts).'.'.$decimal;
        }
        $clean = preg_replace('/[^0-9.\-]/', '', $clean);

        return is_numeric($clean) ? (float) $clean : 0.0;
    }

    private function parseDate(string $value): Carbon
    {
        $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y'];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, trim($value));
                if ($date) {
                    return $date;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            return Carbon::parse(trim($value));
        } catch (\Throwable) {
            return now();
        }
    }
}
