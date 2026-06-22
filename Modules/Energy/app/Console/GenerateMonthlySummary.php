<?php

namespace Modules\Energy\Console;

use Illuminate\Console\Command;
use Modules\Device\Models\Device;
use Modules\Energy\Models\MonthlyEnergySummary;

class GenerateMonthlySummary extends Command
{
    protected $signature = 'energy:summarize {year?} {month?}';

    protected $description = 'Generate monthly energy summaries for all homes';

    public function handle(): int
    {
        $year = $this->argument('year') ?? now()->year;
        $month = $this->argument('month') ?? now()->month;

        $devices = Device::with(['room.home', 'energyReadings', 'energyEstimates'])->get();

        $summaries = [];

        foreach ($devices as $device) {
            if (! $device->room || ! $device->room->home) {
                continue;
            }

            $homeId = $device->room->home->id;
            $roomId = $device->room->id;

            $readings = $device->energyReadings()
                ->whereYear('recorded_at', $year)
                ->whereMonth('recorded_at', $month)
                ->get();

            $estimates = $device->energyEstimates()
                ->where('period_type', 'monthly')
                ->whereYear('period_start', $year)
                ->whereMonth('period_start', $month)
                ->get();

            $totalKwh = $readings->sum('kwh') + $estimates->sum('estimated_kwh');
            $estimatedCost = $estimates->sum('estimated_cost');

            $summaries[] = [
                'home_id' => $homeId,
                'room_id' => $roomId,
                'device_id' => $device->id,
                'year' => $year,
                'month' => $month,
                'total_kwh' => $totalKwh,
                'estimated_cost' => $estimatedCost,
                'reading_count' => $readings->count(),
                'estimate_count' => $estimates->count(),
                'metadata' => json_encode([
                    'device_name' => $device->name,
                    'generated_at' => now()->toIso8601String(),
                ]),
            ];
        }

        MonthlyEnergySummary::upsert(
            $summaries,
            ['home_id', 'room_id', 'device_id', 'year', 'month'],
            ['total_kwh', 'estimated_cost', 'reading_count', 'estimate_count', 'metadata'],
        );

        $this->info('Generated '.count($summaries)." summaries for {$year}-{$month}.");

        return self::SUCCESS;
    }
}
