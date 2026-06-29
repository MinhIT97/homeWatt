<?php

namespace Tests\Feature\Expense;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;
use Modules\Wallet\Models\Wallet;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    private function setupHomeWithWallet(User $owner, float $openingBalance = 1000000): array
    {
        $home = new Home(['name' => 'Test Home']);
        $home->forceFill(['owner_id' => $owner->id])->save();
        $m = HomeMember::create(['home_id' => $home->id, 'user_id' => $owner->id]);
        $m->assignRole('owner');
        $wallet = Wallet::create([
            'home_id' => $home->id,
            'name' => 'Test Wallet',
            'type' => 'cash',
            'opening_balance' => $openingBalance,
            'balance' => $openingBalance,
            'currency' => 'VND',
        ]);
        $category = ExpenseCategory::create([
            'home_id' => $home->id,
            'name' => 'Food',
            'type' => 'expense',
            'is_system' => true,
        ]);

        return compact('home', 'wallet', 'category');
    }

    public function test_user_can_create_expense(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'wallet' => $wallet, 'category' => $cat] = $this->setupHomeWithWallet($user);

        $response = $this->actingAs($user)->post(route('expenses.store'), [
            'home_id' => $home->id,
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'type' => 'expense',
            'amount' => 100000,
            'currency' => 'VND',
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('expenses', [
            'home_id' => $home->id,
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'amount' => '100000.00',
            'type' => 'expense',
        ]);
    }

    public function test_expense_updates_wallet_balance(): void
    {
        $user = User::factory()->create();
        ['wallet' => $wallet] = $this->setupHomeWithWallet($user, 1000000);

        $cat = ExpenseCategory::where('home_id', $wallet->home_id)->first();

        $this->actingAs($user)->post(route('expenses.store'), [
            'home_id' => $wallet->home_id,
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'type' => 'expense',
            'amount' => 200000,
            'currency' => 'VND',
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $wallet->refresh();
        $this->assertEquals(800000.0, (float) $wallet->balance);
    }

    public function test_income_increases_balance(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'wallet' => $wallet] = $this->setupHomeWithWallet($user, 100000);

        $cat = ExpenseCategory::create([
            'home_id' => $home->id,
            'name' => 'Salary',
            'type' => 'income',
            'is_system' => true,
        ]);

        $this->actingAs($user)->post(route('expenses.store'), [
            'home_id' => $home->id,
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'type' => 'income',
            'amount' => 500000,
            'currency' => 'VND',
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $wallet->refresh();
        $this->assertEquals(600000.0, (float) $wallet->balance);
    }

    public function test_user_cannot_create_expense_in_other_home(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();
        ['home' => $homeA, 'wallet' => $walletA, 'category' => $catA] = $this->setupHomeWithWallet($ownerA);

        $response = $this->actingAs($ownerB)->post(route('expenses.store'), [
            'home_id' => $homeA->id,
            'wallet_id' => $walletA->id,
            'category_id' => $catA->id,
            'type' => 'expense',
            'amount' => 100,
            'currency' => 'VND',
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $response->assertForbidden();
    }

    public function test_negative_amount_rejected(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'wallet' => $wallet, 'category' => $cat] = $this->setupHomeWithWallet($user);

        $response = $this->actingAs($user)->post(route('expenses.store'), [
            'home_id' => $home->id,
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'type' => 'expense',
            'amount' => -100,
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $response->assertSessionHasErrors('amount');
    }

    public function test_expense_factory_creates_valid_expense(): void
    {
        $expense = Expense::factory()->create();

        $this->assertNotNull($expense->id);
        $this->assertGreaterThan(0, (float) $expense->amount);
        $this->assertNotNull($expense->wallet);
        $this->assertNotNull($expense->category);
    }

    public function test_signed_amount_returns_correct_sign(): void
    {
        $expense = Expense::factory()->create(['type' => 'expense', 'amount' => 100]);
        $this->assertEquals(-100.0, $expense->signedAmount());

        $income = Expense::factory()->create(['type' => 'income', 'amount' => 100]);
        $this->assertEquals(100.0, $income->signedAmount());
    }

    public function test_delete_expense_reverses_wallet_balance(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'wallet' => $wallet, 'category' => $cat] = $this->setupHomeWithWallet($user, 1000000);

        $response = $this->actingAs($user)->post(route('expenses.store'), [
            'home_id' => $home->id,
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'type' => 'expense',
            'amount' => 300000,
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $expense = Expense::first();
        $this->assertEquals(700000.0, (float) $wallet->fresh()->balance);

        $this->actingAs($user)->delete(route('expenses.destroy', $expense));
        $this->assertEquals(1000000.0, (float) $wallet->fresh()->balance);
    }

    public function test_user_can_view_expenses_index_with_time_filters(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'wallet' => $wallet, 'category' => $cat] = $this->setupHomeWithWallet($user);

        // Create an expense for today
        Expense::factory()->create([
            'home_id' => $home->id,
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'type' => 'expense',
            'amount' => 50000,
            'occurred_at' => now(),
        ]);

        // Create an income for last month
        Expense::factory()->create([
            'home_id' => $home->id,
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'type' => 'income',
            'amount' => 120000,
            'occurred_at' => now()->subMonth(),
        ]);

        // Test period = all
        $response = $this->actingAs($user)->get(route('expenses.index', ['period' => 'all']));
        $response->assertOk();
        $response->assertViewHas('totalSpent', 50000.0);
        $response->assertViewHas('totalIncome', 120000.0);

        // Test period = day (today)
        $response = $this->actingAs($user)->get(route('expenses.index', [
            'period' => 'day',
            'date' => now()->format('Y-m-d'),
        ]));
        $response->assertOk();
        $response->assertViewHas('totalSpent', 50000.0);
        $response->assertViewHas('totalIncome', 0.0);

        // Test period = month (this month)
        $response = $this->actingAs($user)->get(route('expenses.index', [
            'period' => 'month',
            'month' => now()->format('Y-m'),
        ]));
        $response->assertOk();
        $response->assertViewHas('totalSpent', 50000.0);
        $response->assertViewHas('totalIncome', 0.0);

        // Test period = month (last month)
        $response = $this->actingAs($user)->get(route('expenses.index', [
            'period' => 'month',
            'month' => now()->subMonth()->format('Y-m'),
        ]));
        $response->assertOk();
        $response->assertViewHas('totalSpent', 0.0);
        $response->assertViewHas('totalIncome', 120000.0);
    }
}
