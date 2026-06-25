<?php

namespace Modules\Device\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Device\Models\Device;
use Modules\Device\Models\DeviceSpecification;

/**
 * @extends Factory<DeviceSpecification>
 */
class DeviceSpecificationFactory extends Factory
{
    protected $model = DeviceSpecification::class;

    public function definition(): array
    {
        $watts = fake()->randomFloat(0, 50, 5000);

        return [
            'device_id' => Device::factory(),
            'rated_power' => $watts,
            'max_power' => $watts * 1.2,
            'standby_power' => fake()->randomFloat(2, 1, 10),
            'voltage' => fake()->randomElement([110, 220, 240]),
            'current' => round($watts / 220, 2),
        ];
    }
}