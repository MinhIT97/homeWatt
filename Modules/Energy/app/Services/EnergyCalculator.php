<?php

namespace Modules\Energy\Services;

use Modules\Device\Models\Device;
use Modules\Device\Models\DeviceUsageProfile;
use Modules\Energy\Models\EnergyEstimate;
use Modules\Tariff\Models\TariffPlan;

class EnergyCalculator
{
    /**
     * Calculate monthly kWh for a device.
     *
     * Formula (continuous): kWh = watts × hours/day × days/month ÷ 1000
     * Formula (duty-cycle): kWh = watts × hours/day × days/month × duty_cycle ÷ 1000
     * Formula (measured):   kWh = sum of readings for the period
     */
    public function estimateMonthly(Device $device, int $year, int $month, ?TariffPlan $tariffPlan = null): EnergyEstimate
    {
        $device->load(['specification', 'usageProfile', 'energyReadings']);

        $spec = $device->specification;
        $profile = $device->usageProfile;

        $watts = $this->determineWatts($device);
        $method = $this->determineMethod($device);
        $confidence = $this->calculateConfidence($device, $method);

        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        $kwh = match ($method) {
            'measured' => $this->calculateFromReadings($device, $year, $month),
            'duty_cycle' => $this->calculateDutyCycle($watts, $profile, $daysInMonth),
            default => $this->calculateContinuous($watts, $profile, $daysInMonth),
        };

        $inputSnapshot = [
            'watts' => $watts,
            'method' => $method,
            'profile' => $profile ? [
                'hours_per_day' => $profile->hours_per_day,
                'days_per_week' => $profile->days_per_week,
                'duty_cycle' => $profile->duty_cycle,
                'source' => $profile->source,
            ] : null,
            'specification' => $spec ? [
                'rated_power' => $spec->rated_power,
                'max_power' => $spec->max_power,
                'standby_power' => $spec->standby_power,
            ] : null,
        ];

        $cost = $tariffPlan ? $this->calculateCost($kwh, $tariffPlan) : null;

        $range = $this->estimateRange($kwh, $confidence);

        return new EnergyEstimate([
            'device_id' => $device->id,
            'period_type' => 'monthly',
            'period_start' => "{$year}-{$month}-01",
            'period_end' => "{$year}-{$month}-{$daysInMonth}",
            'method' => $method,
            'estimated_kwh' => $kwh,
            'estimated_cost' => $cost,
            'confidence' => $confidence,
            'lower_range_kwh' => $range['lower'],
            'upper_range_kwh' => $range['upper'],
            'input_snapshot' => $inputSnapshot,
            'tariff_plan_id' => $tariffPlan?->id,
        ]);
    }

    protected function determineWatts(Device $device): float
    {
        $spec = $device->specification;

        if ($spec?->rated_power) {
            return (float) $spec->rated_power;
        }

        return 0;
    }

    protected function determineMethod(Device $device): string
    {
        $hasReadings = $device->energyReadings->isNotEmpty();
        if ($hasReadings) {
            return 'measured';
        }

        $profile = $device->usageProfile;
        if ($profile && $profile->duty_cycle !== null && $profile->duty_cycle < 1.0) {
            return 'duty_cycle';
        }

        return 'continuous';
    }

    protected function calculateContinuous(float $watts, ?DeviceUsageProfile $profile, int $daysInMonth): float
    {
        $hoursPerDay = $profile?->hours_per_day ?? 24;
        $daysPerWeek = $profile?->days_per_week ?? 7;
        $daysPerMonth = ($daysPerWeek / 7) * $daysInMonth;

        return ($watts * $hoursPerDay * $daysPerMonth) / 1000;
    }

    protected function calculateDutyCycle(float $watts, ?DeviceUsageProfile $profile, int $daysInMonth): float
    {
        $hoursPerDay = $profile?->hours_per_day ?? 24;
        $daysPerWeek = $profile?->days_per_week ?? 7;
        $dutyCycle = $profile?->duty_cycle ?? 1.0;
        $daysPerMonth = ($daysPerWeek / 7) * $daysInMonth;

        return ($watts * $hoursPerDay * $daysPerMonth * $dutyCycle) / 1000;
    }

    protected function calculateFromReadings(Device $device, int $year, int $month): float
    {
        return $device->energyReadings()
            ->whereYear('recorded_at', $year)
            ->whereMonth('recorded_at', $month)
            ->sum('kwh');
    }

    protected function calculateConfidence(Device $device, string $method): float
    {
        return match ($method) {
            'measured' => 0.90,
            'duty_cycle' => 0.60,
            'continuous' => $device->specification?->rated_power ? 0.50 : 0.20,
        };
    }

    protected function calculateCost(float $kwh, TariffPlan $tariffPlan): float
    {
        $tariffPlan->load('tiers');
        $tiers = $tariffPlan->tiers->sortBy('tier_number');

        $remainingKwh = $kwh;
        $totalCost = 0.0;

        foreach ($tiers as $tier) {
            $tierKwh = $tier->limit_kwh
                ? min($remainingKwh, (float) $tier->limit_kwh)
                : $remainingKwh;

            $tierCost = $tierKwh * (float) $tier->rate;
            $tax = $tierCost * ((float) $tier->tax_percent / 100);
            $totalCost += $tierCost + $tax + (float) $tier->surcharge;

            $remainingKwh -= $tierKwh;
            if ($remainingKwh <= 0) {
                break;
            }
        }

        return round($totalCost, 2);
    }

    protected function estimateRange(float $kwh, float $confidence): array
    {
        $margin = (1 - $confidence) * 0.5;

        return [
            'lower' => round($kwh * (1 - $margin), 4),
            'upper' => round($kwh * (1 + $margin), 4),
        ];
    }
}
