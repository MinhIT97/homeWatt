<?php

namespace Database\Factories\Modules\Energy\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Device\Models\Device;
use Modules\Energy\Models\EnergyReading;

/**
 * @extends Factory<EnergyReading>
 */
class EnergyReadingFactory extends Factory
{
    protected $model = EnergyReading::class;

    public function definition(): array
    {
        return [
            'device_id' => Device::factory(),
            'recorded_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'watts' => fake()->randomFloat(2, 10, 2000),
            'kwh' => fake()->randomFloat(4, 0.01, 5),
            'source' => fake()->randomElement(['manual', 'measured', 'ai']),
            'measurement_type' => fake()->randomElement(['instant', 'cumulative']),
            'interval_minutes' => fake()->randomElement([1, 5, 15, 60]),
        ];
    }

    public function measured(): static
    {
        return $this->state(fn () => ['source' => 'measured']);
    }
}
