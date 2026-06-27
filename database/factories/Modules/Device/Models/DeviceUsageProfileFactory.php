<?php

namespace Database\Factories\Modules\Device\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Device\Models\Device;
use Modules\Device\Models\DeviceUsageProfile;

/**
 * @extends Factory<DeviceUsageProfile>
 */
class DeviceUsageProfileFactory extends Factory
{
    protected $model = DeviceUsageProfile::class;

    public function definition(): array
    {
        return [
            'device_id' => Device::factory(),
            'hours_per_day' => fake()->randomFloat(1, 1, 24),
            'days_per_week' => fake()->numberBetween(1, 7),
            'duty_cycle' => fake()->randomFloat(2, 0.3, 1.0),
            'season' => fake()->randomElement(['all', 'summer', 'winter']),
            'source' => 'manual',
        ];
    }
}
