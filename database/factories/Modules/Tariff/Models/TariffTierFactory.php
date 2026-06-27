<?php

namespace Database\Factories\Modules\Tariff\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tariff\Models\TariffPlan;
use Modules\Tariff\Models\TariffTier;

/**
 * @extends Factory<TariffTier>
 */
class TariffTierFactory extends Factory
{
    protected $model = TariffTier::class;

    public function definition(): array
    {
        return [
            'tariff_plan_id' => TariffPlan::factory(),
            'tier_number' => fake()->numberBetween(1, 6),
            'limit_kwh' => fake()->randomElement([50, 100, 200, 300, 400, null]),
            'rate' => fake()->randomFloat(0, 1500, 3500),
            'tax_percent' => 10,
            'surcharge' => 0,
        ];
    }
}
