<?php

namespace Database\Factories\Modules\Expense\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Home\Models\Home;

/**
 * @extends Factory<ExpenseCategory>
 */
class ExpenseCategoryFactory extends Factory
{
    protected $model = ExpenseCategory::class;

    public function definition(): array
    {
        return [
            'home_id' => Home::factory(),
            'name' => fake()->randomElement(['Ăn uống', 'Đi lại', 'Mua sắm']),
            'type' => ExpenseCategory::TYPE_EXPENSE,
            'icon' => '🍜',
            'color' => '#f97316',
            'is_system' => false,
            'sort_order' => 0,
        ];
    }

    public function income(): static
    {
        return $this->state(fn () => ['type' => ExpenseCategory::TYPE_INCOME, 'name' => 'Lương']);
    }

    public function system(): static
    {
        return $this->state(fn () => ['is_system' => true]);
    }
}
