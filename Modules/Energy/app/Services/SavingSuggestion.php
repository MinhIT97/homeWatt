<?php

namespace Modules\Energy\Services;

use Modules\Device\Models\Device;

class SavingSuggestion
{
    public function analyze($devices, float $monthlyCost): array
    {
        $suggestions = [];
        $totalPotentialSaving = 0;

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
                $savedKwh = $currentMonthlyKwh - $reducedKwh;
                $savedCost = $savedKwh * 2500; // Approx VND per kWh

                if ($savedKwh > 5) {
                    $suggestions[] = [
                        'icon' => $this->icon($device->name),
                        'title' => "Giảm giờ dùng {$device->name}",
                        'detail' => "Đang chạy {$hoursPerDay}h/ngày. Nếu giảm xuống {$reducedHours}h/ngày, tiết kiệm ~" . number_format($savedCost) . 'đ/tháng.',
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
                $savedKwh = $currentMonthlyKwh - $optimizedKwh;
                $savedCost = $savedKwh * 2500;

                if ($savedKwh > 5) {
                    $suggestions[] = [
                        'icon' => $this->icon($device->name),
                        'title' => "Tối ưu chu kỳ {$device->name}",
                        'detail' => "{$device->name} không cần chạy liên tục. Giảm chu kỳ hoạt động có thể tiết kiệm ~" . number_format($savedCost) . 'đ/tháng.',
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
                    $suggestions[] = [
                        'icon' => '❄️',
                        'title' => 'Đặt máy lạnh 26-28°C',
                        'detail' => 'Mỗi độ tăng nhiệt độ tiết kiệm ~3% điện. Đặt 26°C thay vì 22°C tiết kiệm ~12% chi phí làm mát.',
                        'saving_kwh' => round($currentMonthlyKwh * 0.12, 1),
                        'saving_cost' => round($currentMonthlyKwh * 0.12 * 2500),
                        'priority' => 'high',
                    ];
                    break; // Only add this once
                }
            }
        }

        if ($monthlyCost > 500000 && $totalPotentialSaving > 0) {
            $suggestions[] = [
                'icon' => '📊',
                'title' => 'Tổng tiềm năng tiết kiệm',
                'detail' => 'Thực hiện tất cả gợi ý trên có thể tiết kiệm ~' . number_format($totalPotentialSaving) . 'đ/tháng (khoảng ' . round(($totalPotentialSaving / max($monthlyCost, 1)) * 100) . '% hóa đơn).',
                'saving_kwh' => round($totalPotentialSaving / 2500, 1),
                'saving_cost' => $totalPotentialSaving,
                'priority' => 'high',
            ];
        }

        return $suggestions;
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
