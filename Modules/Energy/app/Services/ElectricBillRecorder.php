<?php

namespace Modules\Energy\Services;

use Carbon\Carbon;
use Modules\Energy\Models\EnergyBill;
use Modules\Expense\Models\Expense;

class ElectricBillRecorder
{
    public function recordFromScan(Expense $expense, array $scanResult): EnergyBill
    {
        $period = $this->normalizeBillingPeriod($scanResult['billing_month'] ?? null);

        return EnergyBill::create([
            'home_id' => $expense->home_id,
            'expense_id' => $expense->id,
            'user_id' => $expense->user_id,
            'provider' => $scanResult['merchant'] ?? 'EVN',
            'customer_name' => $scanResult['customer_name'] ?? null,
            'customer_code' => $scanResult['customer_code'] ?? null,
            'billing_period' => $period['label'],
            'period_start' => $period['start'],
            'period_end' => $period['end'],
            'old_index' => $scanResult['old_index'] ?? null,
            'new_index' => $scanResult['new_index'] ?? null,
            'kwh' => $scanResult['kwh'] ?? null,
            'amount' => $scanResult['amount'],
            'currency' => $expense->currency,
            'source' => 'telegram_ai',
            'raw_payload' => $scanResult,
            'scanned_at' => now(),
        ]);
    }

    private function normalizeBillingPeriod(?string $billingMonth): array
    {
        $label = $billingMonth ? trim($billingMonth) : now()->format('m/Y');

        foreach (['m/Y', 'm-Y', 'Y-m', 'Y/m'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $label);

                if ($date !== false) {
                    $start = $date->copy()->startOfMonth();

                    return [
                        'label' => $start->format('m/Y'),
                        'start' => $start->toDateString(),
                        'end' => $start->copy()->endOfMonth()->toDateString(),
                    ];
                }
            } catch (\Throwable) {
                // Try the next known month format.
            }
        }

        return [
            'label' => mb_substr($label, 0, 20),
            'start' => null,
            'end' => null,
        ];
    }
}
