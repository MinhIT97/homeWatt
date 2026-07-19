<?php

namespace Modules\Expense\Imports\Parsers;

use Carbon\Carbon;

class TechcombankParser implements BankParser
{
    /**
     * Detect Techcombank CSV format by English column names.
     */
    public function detect(array $headers): bool
    {
        $lowerHeaders = array_map(fn (string $h) => mb_strtolower(trim($h), 'UTF-8'), $headers);

        $required = ['transaction date', 'amount', 'description'];

        foreach ($required as $col) {
            if (! in_array($col, $lowerHeaders, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Parse Techcombank CSV rows into normalized transaction data.
     *
     * Expected columns: Transaction Date, Amount, Description, Balance
     * Negative amount = expense, positive = income
     */
    public function parse(array $rows): array
    {
        $transactions = [];

        foreach ($rows as $row) {
            $normalized = [];
            foreach ($row as $key => $value) {
                $normalized[mb_strtolower(trim((string) $key), 'UTF-8')] = trim((string) ($value ?? ''));
            }

            $dateStr = $normalized['transaction date'] ?? '';
            $amountStr = $normalized['amount'] ?? '0';
            $description = $normalized['description'] ?? '';
            $reference = $normalized['reference'] ?? $normalized['transaction id'] ?? null;

            if (empty($dateStr) || empty($description)) {
                continue;
            }

            $rawAmount = $this->parseAmount($amountStr);

            if ($rawAmount == 0) {
                continue;
            }

            // Negative = expense, positive = income
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
        $clean = str_replace(['đ', 'vnđ', 'VND', 'vnd', ','], ['', '', '', '', ''], trim($value));
        $clean = preg_replace('/[^0-9.\-]/', '', $clean);

        return is_numeric($clean) ? (float) $clean : 0.0;
    }

    private function parseDate(string $value): Carbon
    {
        // Techcombank may use dd/mm/yyyy or mm/dd/yyyy or Y-m-d
        $formats = ['d/m/Y', 'm/d/Y', 'Y-m-d', 'd-M-Y', 'M d, Y'];

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
