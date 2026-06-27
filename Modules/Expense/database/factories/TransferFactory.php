<?php

namespace Database\Factories\Modules\Expense\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Expense\Models\Transfer;
use Modules\Home\Models\Home;
use Modules\Wallet\Models\Wallet;

/**
 * @extends Factory<Transfer>
 */
class TransferFactory extends Factory
{
    protected $model = Transfer::class;

    public function definition(): array
    {
        $home = Home::factory()->create();
        $from = Wallet::factory()->create(['home_id' => $home->id]);
        $to = Wallet::factory()->create(['home_id' => $home->id]);

        return [
            'home_id' => $home->id,
            'from_wallet_id' => $from->id,
            'to_wallet_id' => $to->id,
            'user_id' => User::factory(),
            'amount' => fake()->randomFloat(2, 50000, 5000000),
            'fee' => 0,
            'currency' => 'VND',
            'description' => fake()->optional()->sentence(),
            'occurred_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
