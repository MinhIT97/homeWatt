<?php

namespace Database\Factories\Modules\Wallet\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Home\Models\Home;
use Modules\Wallet\Models\Wallet;

/**
 * @extends Factory<Wallet>
 */
class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition(): array
    {
        return [
            'home_id' => Home::factory(),
            'name' => fake()->randomElement([
                'Ví tiền mặt',
                'Cash Wallet',
                'Vietcombank',
                'Techcombank',
                'MoMo',
                'Tiết kiệm',
            ]),
            'type' => Wallet::TYPE_CASH,
            'currency' => 'VND',
            'balance' => 0,
            'opening_balance' => fake()->randomFloat(2, 0, 5000000),
            'icon' => '💰',
            'color' => '#10b981',
            'description' => fake()->optional()->sentence(),
            'is_archived' => false,
            'sort_order' => 0,
        ];
    }

    public function cash(): static
    {
        return $this->state(fn () => [
            'type' => Wallet::TYPE_CASH,
            'name' => 'Ví tiền mặt',
            'icon' => '💵',
        ]);
    }

    public function bank(): static
    {
        return $this->state(fn () => [
            'type' => Wallet::TYPE_BANK,
            'name' => 'Ngân hàng',
            'icon' => '🏦',
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['is_archived' => true]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => [
            'home_id' => Home::factory()->create(['owner_id' => $user->id])->id,
        ]);
    }
}
