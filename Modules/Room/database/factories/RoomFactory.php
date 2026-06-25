<?php

namespace Modules\Room\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Home\Models\Home;
use Modules\Room\Models\Room;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'home_id' => Home::factory(),
            'name' => fake()->randomElement([
                'Living Room',
                'Phòng khách',
                'Bedroom',
                'Phòng ngủ',
                'Kitchen',
                'Bếp',
            ]),
            'type' => fake()->randomElement(['living_room', 'bedroom', 'kitchen', 'bathroom', 'other']),
            'floor' => fake()->numberBetween(1, 3),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}