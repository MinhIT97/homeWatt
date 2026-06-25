<?php

namespace Modules\Device\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Device\Models\Device;
use Modules\Device\Models\DeviceType;
use Modules\Room\Models\Room;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'device_type_id' => null,
            'name' => fake()->randomElement([
                'Air Conditioner',
                'Refrigerator',
                'Washing Machine',
                'TV',
                'Light',
                'Fan',
            ]),
            'brand' => fake()->randomElement(['Samsung', 'LG', 'Panasonic', 'Daikin', 'Sony']),
            'model' => fake()->bothify('Model-####'),
            'serial' => fake()->bothify('SN########'),
            'purchased_at' => fake()->dateTimeBetween('-3 years', 'now'),
        ];
    }

    public function withType(?DeviceType $type = null): static
    {
        return $this->state(fn () => [
            'device_type_id' => $type?->id ?? DeviceType::factory(),
        ]);
    }
}