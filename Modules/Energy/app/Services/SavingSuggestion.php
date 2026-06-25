<?php

namespace Modules\Energy\Services;

class SavingSuggestion
{
    public function analyze($devices, float $monthlyCost): array
    {
        $suggestions = [];
        $totalPotentialSaving = 0;

        $avgKwhCost = $this->getAverageKwhCost($monthlyCost, $devices);

        foreach ($devices as $device) {
            $spec = $device->specification;
            $profile = $device->usageProfile;
            $type = $device->deviceType;

            if (! $spec || ! $spec->rated_power) {
                continue;
            }

            $hoursPerDay = $profile?->hours_per_day ?? ($type?->default_duty_cycle ? 24 : 8);
            $daysPerWeek = $profile?->days_per_week ?? 7;
            $dutyCycle = $profile?->duty_cycle ?? $type?->default_duty_cycle ?? 1.0;
            $watts = (float) $spec->rated_power;

            $currentMonthlyKwh = ($watts * $hoursPerDay * $daysPerWeek * 4.33 * $dutyCycle) / 1000;

            // Check for high-usage devices where reducing hours would help
            if ($hoursPerDay >= 10 && $watts > 500) {
                $reducedHours = max(4, $hoursPerDay - 4);
                $reducedKwh = ($watts * $reducedHours * $daysPerWeek * 4.33 * $dutyCycle) / 1000;
                $savedKwh = max(0, $currentMonthlyKwh - $reducedKwh);
                $savedCost = $savedKwh * $avgKwhCost;

                if ($savedKwh > 5) {
                    $suggestions[] = [
                        'icon' => $this->icon($device->name),
                        'title' => __('dashboard.suggest_reduce_hours')." {$device->name}",
                        'detail' => "{$device->name} ".__('dashboard.suggest_reduce_hours_detail', ['hoursPerDay' => $hoursPerDay, 'reducedHours' => $reducedHours, 'savedCost' => number_format($savedCost)]),
                        'saving_kwh' => round($savedKwh, 1),
                        'saving_cost' => round($savedCost),
                        'priority' => $savedKwh > 20 ? 'high' : 'medium',
                    ];
                    $totalPotentialSaving += $savedCost;
                }
            }

            // Check for devices that could use duty cycle optimization
            if ($dutyCycle >= 0.8 && $watts > 200 && $hoursPerDay >= 6) {
                $optimizedDutyCycle = max(0.3, $dutyCycle - 0.3);
                $optimizedKwh = ($watts * $hoursPerDay * $daysPerWeek * 4.33 * $optimizedDutyCycle) / 1000;
                $savedKwh = max(0, $currentMonthlyKwh - $optimizedKwh);
                $savedCost = $savedKwh * $avgKwhCost;

                if ($savedKwh > 5) {
                    $suggestions[] = [
                        'icon' => $this->icon($device->name),
                        'title' => __('dashboard.suggest_optimize_cycle')." {$device->name}",
                        'detail' => "{$device->name} ".__('dashboard.suggest_optimize_cycle_detail', ['savedCost' => number_format($savedCost)]),
                        'saving_kwh' => round($savedKwh, 1),
                        'saving_cost' => round($savedCost),
                        'priority' => 'medium',
                    ];
                    $totalPotentialSaving += $savedCost;
                }
            }

            // Seasonal advice
            $name = strtolower($device->name);
            $month = (int) now()->format('n');

            if ((str_contains($name, 'máy lạnh') || str_contains($name, 'ac') || str_contains($name, 'air')) && $watts > 1000) {
                if ($month >= 6 && $month <= 8) {
                    $seasonalSaving = max(0, $currentMonthlyKwh * 0.12);
                    $suggestions[] = [
                        'icon' => '❄️',
                        'title' => __('dashboard.suggest_ac_temp'),
                        'detail' => __('dashboard.suggest_ac_temp_detail'),
                        'saving_kwh' => round($seasonalSaving, 1),
                        'saving_cost' => round($seasonalSaving * $avgKwhCost),
                        'priority' => 'high',
                    ];
                    break; // Only add this once
                }
            }
        }

        if ($monthlyCost > 500000 && $totalPotentialSaving > 0) {
            $suggestions[] = [
                'icon' => '📊',
                'title' => __('dashboard.suggest_total_potential'),
                'detail' => __('dashboard.suggest_total_potential_detail', ['saving' => number_format($totalPotentialSaving), 'pct' => round(($totalPotentialSaving / max($monthlyCost, 1)) * 100)]),
                'saving_kwh' => $avgKwhCost > 0 ? round($totalPotentialSaving / $avgKwhCost, 1) : 0,
                'saving_cost' => $totalPotentialSaving,
                'priority' => 'high',
            ];
        }

        return $suggestions;
    }

    /**
     * Estimate the average cost per kWh from monthly cost + total estimated kWh.
     * Falls back to a config-defined default when input is invalid.
     */
    protected function getAverageKwhCost(float $monthlyCost, $devices): float
    {
        $totalKwh = 0;
        foreach ($devices as $device) {
            $spec = $device->specification;
            $profile = $device->usageProfile;
            $type = $device->deviceType;

            if (! $spec || ! $spec->rated_power) {
                continue;
            }

            $hoursPerDay = $profile?->hours_per_day ?? ($type?->default_duty_cycle ? 24 : 8);
            $daysPerWeek = $profile?->days_per_week ?? 7;
            $dutyCycle = $profile?->duty_cycle ?? $type?->default_duty_cycle ?? 1.0;
            $watts = (float) $spec->rated_power;

            $totalKwh += ($watts * $hoursPerDay * $daysPerWeek * 4.33 * $dutyCycle) / 1000;
        }

        if ($totalKwh > 0 && $monthlyCost > 0) {
            return round($monthlyCost / $totalKwh, 2);
        }

        return (float) config('energy.default_kwh_cost', 2500);
    }

    private function icon(string $name): string
    {
        $n = strtolower($name);

        return match (true) {
            str_contains($n, 'máy lạnh') || str_contains($n, 'ac') || str_contains($n, 'air') => '❄️',
            str_contains($n, 'tủ lạnh') || str_contains($n, 'fridge') => '🥬',
            str_contains($n, 'máy giặt') || str_contains($n, 'washer') => '🧺',
            str_contains($n, 'nóng') || str_contains($n, 'heater') => '🔥',
            str_contains($n, 'tivi') || str_contains($n, 'tv') => '📺',
            str_contains($n, 'đèn') || str_contains($n, 'light') => '💡',
            str_contains($n, 'quạt') || str_contains($n, 'fan') => '🌀',
            str_contains($n, 'bơm') || str_contains($n, 'pump') => '💧',
            default => '🔌',
        };
    }
}
