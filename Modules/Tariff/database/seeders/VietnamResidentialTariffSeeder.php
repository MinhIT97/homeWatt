<?php

use Illuminate\Database\Seeder;
use Modules\Tariff\Models\TariffPlan;
use Modules\Tariff\Models\TariffTier;

class VietnamResidentialTariffSeeder extends Seeder
{
    public function run(): void
    {
        $plan = TariffPlan::create([
            'name' => 'Vietnam Residential (EVN)',
            'provider' => 'EVN',
            'region' => 'Vietnam',
            'type' => 'residential',
            'effective_from' => '2024-10-11',
            'status' => 'active',
        ]);

        $tiers = [
            ['tier_number' => 1, 'limit_kwh' => 50, 'rate' => 1806, 'tax_percent' => 10],
            ['tier_number' => 2, 'limit_kwh' => 50, 'rate' => 1866, 'tax_percent' => 10],
            ['tier_number' => 3, 'limit_kwh' => 100, 'rate' => 2167, 'tax_percent' => 10],
            ['tier_number' => 4, 'limit_kwh' => 100, 'rate' => 2729, 'tax_percent' => 10],
            ['tier_number' => 5, 'limit_kwh' => 100, 'rate' => 3050, 'tax_percent' => 10],
            ['tier_number' => 6, 'limit_kwh' => null, 'rate' => 3151, 'tax_percent' => 10],
        ];

        foreach ($tiers as $tier) {
            TariffTier::create([...$tier, 'tariff_plan_id' => $plan->id]);
        }
    }
}
