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

    public function test_expense_category_type_must_match_transaction_type(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'wallet' => $wallet, 'category' => $expenseCategory] = $this->setupHomeWithWallet($user);

        $response = $this->actingAs($user)->post(route('expenses.store'), [
            'home_id' => $home->id,
            'wallet_id' => $wallet->id,
            'category_id' => $expenseCategory->id,
            'type' => 'income',
            'amount' => 500000,
            'currency' => 'VND',
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $response->assertSessionHasErrors('category_id');
        $this->assertDatabaseMissing('expenses', [
            'home_id' => $home->id,
            'wallet_id' => $wallet->id,
            'category_id' => $expenseCategory->id,
            'type' => 'income',
            'amount' => '500000.00',
        ]);
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

    public function test_user_cannot_move_expense_to_wallet_or_category_from_other_home(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();

        ['home' => $homeA, 'wallet' => $walletA, 'category' => $catA] = $this->setupHomeWithWallet($ownerA);
        ['wallet' => $walletB, 'category' => $catB] = $this->setupHomeWithWallet($ownerB);

        $expense = Expense::create([
            'home_id' => $homeA->id,
            'wallet_id' => $walletA->id,
            'category_id' => $catA->id,
            'user_id' => $ownerA->id,
            'type' => 'expense',
            'amount' => 100000,
            'currency' => 'VND',
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($ownerA)->patch(route('expenses.update', $expense), [
            'wallet_id' => $walletB->id,
            'category_id' => $catB->id,
        ]);

        $response->assertSessionHasErrors(['wallet_id', 'category_id']);
        $expense->refresh();
        $this->assertSame($walletA->id, $expense->wallet_id);
        $this->assertSame($catA->id, $expense->category_id);
    }

    public function test_user_cannot_update_expense_to_mismatched_type_and_category(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'wallet' => $wallet, 'category' => $expenseCategory] = $this->setupHomeWithWallet($user);

        $expense = Expense::create([
            'home_id' => $home->id,
            'wallet_id' => $wallet->id,
            'category_id' => $expenseCategory->id,
            'user_id' => $user->id,
            'type' => 'expense',
            'amount' => 100000,
            'currency' => 'VND',
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($user)->patch(route('expenses.update', $expense), [
            'type' => 'income',
        ]);

        $response->assertSessionHasErrors('category_id');
        $expense->refresh();
        $this->assertSame('expense', $expense->type);
        $this->assertSame($expenseCategory->id, $expense->category_id);
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

    public function test_expenses_index_excludes_transfer_legs_from_totals_and_list(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'wallet' => $wallet, 'category' => $cat] = $this->setupHomeWithWallet($user);

        $toWallet = Wallet::create([
            'home_id' => $home->id,
            'name' => 'Bank Wallet',
            'type' => 'bank',
            'opening_balance' => 0,
            'balance' => 0,
            'currency' => 'VND',
        ]);

        Expense::factory()->create([
            'home_id' => $home->id,
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'type' => 'income',
            'amount' => 120000,
            'occurred_at' => now(),
        ]);

        $transfer = Transfer::create([
            'home_id' => $home->id,
            'from_wallet_id' => $wallet->id,
            'to_wallet_id' => $toWallet->id,
            'user_id' => $user->id,
            'amount' => 50000,
            'fee' => 0,
            'currency' => 'VND',
            'description' => 'Chuyển ví',
            'occurred_at' => now(),
        ]);

        Expense::factory()->create([
            'home_id' => $home->id,
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'type' => 'expense',
            'amount' => 50000,
            'occurred_at' => now(),
            'transfer_id' => $transfer->id,
        ]);

        Expense::factory()->create([
            'home_id' => $home->id,
            'wallet_id' => $toWallet->id,
            'category_id' => $cat->id,
            'type' => 'income',
            'amount' => 50000,
            'occurred_at' => now(),
            'transfer_id' => $transfer->id,
        ]);

        $response = $this->actingAs($user)->get(route('expenses.index', ['period' => 'all']));

        $response->assertOk();
        $response->assertViewHas('totalIncome', 120000.0);
        $response->assertViewHas('totalSpent', 0.0);
        $response->assertViewHas('expenses', fn ($expenses) => $expenses->total() === 1);
    }
}
