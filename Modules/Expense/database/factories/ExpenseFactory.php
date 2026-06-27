<?php

namespace Database\Factories\Modules\Expense\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Home\Models\Home;
use Modules\Wallet\Models\Wallet;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        $home = Home::factory()->create();
        $category = ExpenseCategory::factory()->create(['home_id' => $home->id]);
        $wallet = Wallet::factory()->create(['home_id' => $home->id]);

        return [
            'home_id' => $home->id,
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'user_id' => User::factory(),
            'type' => Expense::TYPE_EXPENSE,
            'amount' => fake()->randomFloat(2, 10000, 1000000),
            'currency' => 'VND',
            'description' => fake()->sentence(3),
            'occurred_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function income(): static
    {
        return $this->state(fn () => ['type' => Expense::TYPE_INCOME]);
    }
}
