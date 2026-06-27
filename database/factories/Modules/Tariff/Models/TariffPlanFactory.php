<?php

namespace Database\Factories\Modules\Tariff\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tariff\Models\TariffPlan;

/**
 * @extends Factory<TariffPlan>
 */
class TariffPlanFactory extends Factory
{
    protected $model = TariffPlan::class;

    public function definition(): array
    {
        return [
            'name' => 'EVN Residential '.fake()->numberBetween(1, 99),
            'provider' => 'EVN',
            'region' => fake()->randomElement(['hn', 'sg', 'dn', 'hp', 'ct']),
            'type' => 'residential',
            'effective_from' => '2025-01-01',
            'effective_to' => null,
            'status' => 'active',
            'is_system' => false,
        ];
    }

    public function system(): static
    {
        return $this->state(fn () => [
            'is_system' => true,
            'name' => 'EVN Residential System',
        ]);
    }
}
