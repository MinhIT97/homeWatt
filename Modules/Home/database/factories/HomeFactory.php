<?php

namespace Modules\Home\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Home\Models\Home;

/**
 * @extends Factory<Home>
 */
class HomeFactory extends Factory
{
    protected $model = Home::class;

    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'name' => fake()->randomElement([
                'Nhà chính',
                'Home',
                'Căn hộ',
                'Villa',
            ]).' '.fake()->lastName(),
            'address' => fake()->streetAddress(),
            'timezone' => 'Asia/Ho_Chi_Minh',
            'currency' => 'VND',
        ];
    }
}