<?php

namespace Tests\Feature\Expense;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Expense\Models\Transfer;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;
use Modules\Wallet\Models\Wallet;
use Tests\TestCase;

class BudgetSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function setupHome(User $owner, string $name): Home
    {
        $home = new Home(['name' => $name]);
        $home->forceFill(['owner_id' => $owner->id])->save();

        $membership = HomeMember::create([
            'home_id' => $home->id,
            'user_id' => $owner->id,
        ]);
        $membership->assignRole('owner');

        return $home;
    }

    private function createExpenseCategory(Home $home, string $name): ExpenseCategory
    {
        return ExpenseCategory::create([
            'home_id' => $home->id,
            'name' => $name,
            'type' => 'expense',
            'is_system' => true,
        ]);
    }

    public function test_budget_index_only_shows_categories_for_selected_home(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();

        $homeA = $this->setupHome($ownerA, 'Home A');
        $homeB = $this->setupHome($ownerB, 'Home B');

        $this->createExpenseCategory($homeA, 'Groceries Home A');
        $this->createExpenseCategory($homeB, 'Secret Category Home B');

        $response = $this->actingAs($ownerA)->get(route('budgets.index', [
            'home_id' => $homeA->id,
            'month' => now()->format('Y-m'),
        ]));

        $response->assertOk();
        $response->assertSee('Groceries Home A');
        $response->assertDontSee('Secret Category Home B');
    }

    public function test_budget_store_rejects_category_from_another_home(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();

        $homeA = $this->setupHome($ownerA, 'Home A');
        $homeB = $this->setupHome($ownerB, 'Home B');

        $categoryB = $this->createExpenseCategory($homeB, 'Other Home Category');

        $response = $this->actingAs($ownerA)->post(route('budgets.store'), [
            'home_id' => $homeA->id,
            'category_id' => $categoryB->id,
            'amount' => 1000000,
            'month' => now()->format('Y-m'),
        ]);

        $response->assertSessionHasErrors('category_id');
        $this->assertDatabaseMissing('expense_budgets', [
            'home_id' => $homeA->id,
            'category_id' => $categoryB->id,
        ]);
    }

    public function test_budget_spending_excludes_wallet_transfer_legs(): void
    {
        $owner = User::factory()->create();
        $home = $this->setupHome($owner, 'Home A');
        $category = $this->createExpenseCategory($home, 'Groceries Home A');

        $fromWallet = Wallet::create([
            'home_id' => $home->id,
            'name' => 'Cash',
            'type' => 'cash',
            'opening_balance' => 1000000,
            'balance' => 1000000,
            'currency' => 'VND',
        ]);

        $toWallet = Wallet::create([
            'home_id' => $home->id,
            'name' => 'Bank',
            'type' => 'bank',
            'opening_balance' => 0,
            'balance' => 0,
            'currency' => 'VND',
        ]);

        $transfer = Transfer::create([
            'home_id' => $home->id,
            'from_wallet_id' => $fromWallet->id,
            'to_wallet_id' => $toWallet->id,
            'user_id' => $owner->id,
            'amount' => 250000,
            'fee' => 0,
            'currency' => 'VND',
            'description' => 'Move money',
            'occurred_at' => now(),
        ]);

        Expense::create([
            'home_id' => $home->id,
            'wallet_id' => $fromWallet->id,
            'category_id' => $category->id,
            'user_id' => $owner->id,
            'type' => 'expense',
            'amount' => 250000,
            'currency' => 'VND',
            'occurred_at' => now(),
            'transfer_id' => $transfer->id,
        ]);

        $response = $this->actingAs($owner)->get(route('budgets.index', [
            'home_id' => $home->id,
            'month' => now()->format('Y-m'),
        ]));

        $response->assertOk();
        $response->assertViewHas('globalSpending', 0.0);
        $response->assertViewHas('budgetData', function (array $budgetData) use ($category) {
            $row = collect($budgetData)->first(fn ($item) => $item['category']->id === $category->id);

            return $row && $row['spending'] === 0.0;
        });
    }
}
