<?php

namespace Modules\Energy\Services;

use Modules\Device\Models\Device;
use Modules\Energy\Models\EnergyReading;

class AnomalyDetector
{
    /**
     * Detect if a device has abnormal energy consumption compared to its 30-day baseline.
     */
    public function detect(Device $device): ?array
    {
        $recentAvg = (float) EnergyReading::where('device_id', $device->id)
            ->where('recorded_at', '>=', now()->subDays(7))
            ->avg('kwh');

        $baselineAvg = (float) EnergyReading::where('device_id', $device->id)
            ->where('recorded_at', '>=', now()->subDays(37))
            ->where('recorded_at', '<', now()->subDays(7))
            ->avg('kwh');

        if (! $baselineAvg || $baselineAvg <= 0) {
            // Check maintenance schedule instead
            if ($device->last_maintained_at && $device->last_maintained_at->diffInMonths(now()) >= 6) {
                return [
                    'device_id' => $device->id,
                    'device_name' => $device->name,
                    'ratio' => null,
                    'severity' => 'low',
                    'recent_avg_kwh' => round($recentAvg, 4),
                    'baseline_avg_kwh' => round($baselineAvg, 4),
                    'recommendation' => 'Thiết bị chưa được bảo trì hơn 6 tháng. Cân nhắc kiểm tra định kỳ.',
                    'last_maintained_at' => $device->last_maintained_at->format('Y-m-d'),
                ];
            }

            return null;
        }

        $ratio = round($recentAvg / $baselineAvg, 1);

        if ($ratio >= 1.5) {
            $severity = $ratio >= 2.0 ? 'high' : 'medium';

            return [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'ratio' => $ratio,
                'severity' => $severity,
                'recent_avg_kwh' => round($recentAvg, 4),
                'baseline_avg_kwh' => round($baselineAvg, 4),
                'recommendation' => $this->buildRecommendation($device, $severity),
            ];
        }

        // Check maintenance overdue even if ratio is normal
        if ($device->last_maintained_at && $device->last_maintained_at->diffInMonths(now()) >= 6) {
            return [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'ratio' => null,
                'severity' => 'low',
                'recent_avg_kwh' => round($recentAvg, 4),
                'baseline_avg_kwh' => round($baselineAvg, 4),
                'recommendation' => 'Thiết bị chưa được bảo trì hơn 6 tháng. Cân nhắc kiểm tra định kỳ.',
                'last_maintained_at' => $device->last_maintained_at->format('Y-m-d'),
            ];
        }

        return null;
    }

    /**
     * Detect anomalies for all devices in a home.
     */
    public function detectAll(int $homeId): array
    {
        $devices = Device::whereHas('room', fn ($q) => $q->where('home_id', $homeId))
            ->with('deviceType', 'specification')
            ->get();

        $anomalies = [];

        foreach ($devices as $device) {
            $result = $this->detect($device);
            if ($result) {
                $anomalies[] = $result;
            }
        }

        // Sort by severity: high > medium > low
        usort($anomalies, function ($a, $b) {
            $order = ['high' => 3, 'medium' => 2, 'low' => 1];

            return ($order[$b['severity']] ?? 0) <=> ($order[$a['severity']] ?? 0);
        });

        return $anomalies;
    }

    /**
     * Detect hourly spikes by comparing each hour of the last 24h
     * against the 7-day average for the same hour.
     */
    public function detectHourlySpikes(int $homeId): array
    {
        $deviceIds = Device::whereHas('room', fn ($q) => $q->where('home_id', $homeId))->pluck('id');

        if ($deviceIds->isEmpty()) {
            return [];
        }

        $anomalies = [];

        foreach ($deviceIds as $deviceId) {
            $device = Device::with('deviceType')->find($deviceId);
            if (! $device) {
                continue;
            }

            $hourlyData = EnergyReading::where('device_id', $deviceId)
                ->where('recorded_at', '>=', now()->subHours(24))
                ->selectRaw('HOUR(recorded_at) as hour, SUM(kwh) as total')
                ->groupByRaw('HOUR(recorded_at)')
                ->get()
                ->keyBy('hour');

            $baseline = EnergyReading::where('device_id', $deviceId)
                ->where('recorded_at', '>=', now()->subDays(8))
                ->where('recorded_at', '<', now()->subHours(24))
                ->selectRaw('HOUR(recorded_at) as hour, AVG(kwh) as avg_total')
                ->groupByRaw('HOUR(recorded_at)')
                ->get()
                ->keyBy('hour');

            foreach ($hourlyData as $hour => $data) {
                $avg = (float) ($baseline[$hour]->avg_total ?? 0);
                if ($avg > 0 && (float) $data->total > $avg * 2) {
                    $actualKwh = round((float) $data->total, 4);
                    $expectedKwh = round($avg, 4);
                    $spikeRatio = $avg > 0 ? round($actualKwh / $expectedKwh, 1) : null;
                    $spikeSeverity = $spikeRatio !== null && $spikeRatio >= 5.0 ? 'high' : 'medium';

                    $anomalies[] = [
                        'device_id' => $deviceId,
                        'device_name' => $device->name,
                        'hour' => (int) $hour,
                        'hour_label' => sprintf('%02d:00 - %02d:00', (int) $hour, ((int) $hour + 1) % 24),
                        'actual_kwh' => $actualKwh,
                        'expected_kwh' => $expectedKwh,
                        'ratio' => $spikeRatio,
                        'severity' => $spikeSeverity,
                        'recommendation' => "Phát hiện tiêu thụ đột biến lúc {$hour}:00 — cao gấp {$spikeRatio} lần mức trung bình. Kiểm tra thiết bị ngay.",
                    ];
                }
            }
        }

        return $anomalies;
    }

    /**
     * Combined detection for all anomaly types in a home.
     */
    public function detectHomeAnomalies(int $homeId): array
    {
        $deviceAnomalies = $this->detectAll($homeId);
        $spikeAnomalies = $this->detectHourlySpikes($homeId);

        return [
            'device_anomalies' => $deviceAnomalies,
            'hourly_spikes' => $spikeAnomalies,
            'total' => count($deviceAnomalies) + count($spikeAnomalies),
            'checked_at' => now()->toDateTimeString(),
            'home_id' => $homeId,
        ];
    }

    protected function buildRecommendation(Device $device, string $severity): string
    {
        $slug = $device->deviceType?->slug ?? '';
        $deviceName = mb_strtolower($device->name, 'UTF-8');

        $base = match (true) {
            str_contains($deviceName, 'máy lạnh') || str_contains($deviceName, 'điều hòa') || $slug === 'air_conditioner' => 'Máy lạnh tiêu thụ cao hơn bình thường. Vệ sinh lưới lọc và kiểm tra gas.',
            str_contains($deviceName, 'tủ lạnh') || $slug === 'refrigerator' => 'Tủ lạnh hao điện bất thường. Kiểm tra gioăng cửa và dàn nóng.',
            str_contains($deviceName, 'nóng') || str_contains($deviceName, 'heater') || $slug === 'water_heater' => 'Bình nóng lạnh tiêu thụ cao bất thường. Kiểm tra thanh đốt và cặn bám.',
            str_contains($deviceName, 'máy giặt') || $slug === 'washing_machine' => 'Máy giặt tiêu thụ cao hơn bình thường. Kiểm tra lồng giặt và chế độ vận hành.',
            str_contains($deviceName, 'bơm') || $slug === 'pump' => 'Máy bơm hoạt động bất thường. Kiểm tra rò rỉ đường ống hoặc van một chiều.',
            default => $severity === 'high'
                ? 'Thiết bị đang tiêu thụ điện cao hơn nhiều so với mức trung bình. Cân nhắc kiểm tra, bảo trì.'
                : 'Thiết bị đang tiêu thụ điện hơi cao hơn mức trung bình. Cân nhắc kiểm tra.',
        };

        // Calculate extra cost
        $spec = $device->specification;
        if ($spec && $spec->rated_power) {
            $extraKwh = round(((float) $spec->rated_power / 1000) * 24 * 30 * 0.5, 1);
            $extraCost = round($extraKwh * 2500); // ~2.500đ/kWh estimate
            $base .= ' Chi phí tăng thêm ước tính: ~'.number_format($extraCost, 0, ',', '.').'đ/tháng.';
        }

        return $base;
    }
}
