<?php

namespace Modules\Energy\Console;

use Illuminate\Console\Command;
use Modules\Energy\Models\MonthlyEnergySummary;
use Modules\Home\Models\Home;

class CheckThresholds extends Command
{
    protected $signature = 'energy:check-thresholds';

    protected $description = 'Check home energy thresholds and log alerts';

    public function handle(): int
    {
        $homes = Home::whereNotNull('monthly_kwh_threshold')->get();
        $now = now();
        $alerts = [];

        foreach ($homes as $home) {
            $currentKwh = MonthlyEnergySummary::where('home_id', $home->id)
                ->where('year', $now->year)
                ->where('month', $now->month)
                ->sum('total_kwh');

            $threshold = (float) $home->monthly_kwh_threshold;

            if ($currentKwh > $threshold) {
                $overBy = $currentKwh - $threshold;
                $pctOver = round(($overBy / $threshold) * 100);

                $alerts[] = [
                    'home_id' => $home->id,
                    'home_name' => $home->name,
                    'current_kwh' => round($currentKwh, 1),
                    'threshold' => $threshold,
                    'over_by' => round($overBy, 1),
                    'pct_over' => $pctOver,
                ];
            }
        }

        if (count($alerts) > 0) {
            $this->warn(count($alerts).' home(s) exceeded their monthly threshold:');
            foreach ($alerts as $a) {
                $this->line("  {$a['home_name']}: {$a['current_kwh']} kWh (threshold: {$a['threshold']} kWh, +{$a['pct_over']}%)");
            }
        } else {
            $this->info('All homes are within their monthly thresholds.');
        }

        return self::SUCCESS;
    }
}
