<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Expense\Models\Transfer;
use Modules\Expense\Services\ExpenseService;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;
use Modules\Wallet\Models\Wallet;
use Tests\TestCase;

class ExpenseServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExpenseService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ExpenseService::class);
    }

    private function setupHome(User $user, float $openingBalance = 1000000): array
    {
        $home = new Home(['name' => 'Test Home', 'currency' => 'VND', 'timezone' => 'Asia/Ho_Chi_Minh']);
        $home->forceFill(['owner_id' => $user->id])->save();

        HomeMember::create([
            'home_id' => $home->id,
            'user_id' => $user->id,
            'role' => HomeMember::ROLE_OWNER,
        ]);

        $wallet = Wallet::create([
            'home_id' => $home->id,
            'name' => 'Test Wallet',
            'type' => Wallet::TYPE_CASH,
            'currency' => 'VND',
            'opening_balance' => $openingBalance,
            'balance' => $openingBalance,
        ]);

        $category = ExpenseCategory::create([
            'home_id' => $home->id,
            'name' => 'Food',
            'type' => Expense::TYPE_EXPENSE,
            'icon' => '🍔',
            'color' => '#ef4444',
            'is_system' => true,
        ]);

        return compact('home', 'wallet', 'category');
    }

    public function test_it_creates_expense_and_updates_wallet_balance(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'wallet' => $wallet, 'category' => $cat] = $this->setupHome($user);

        $initialBalance = (float) $wallet->balance;

        $expense = $this->service->createExpense([
            'home_id' => $home->id,
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'type' => Expense::TYPE_EXPENSE,
            'amount' => 500000,
            'currency' => 'VND',
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ], $user);

        $this->assertInstanceOf(Expense::class, $expense);
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'amount' => 500000.00,
            'type' => Expense::TYPE_EXPENSE,
        ]);

        $wallet->refresh();
        $this->assertEquals($initialBalance - 500000, (float) $wallet->balance);
        $this->assertEquals($initialBalance - 500000, $wallet->calculatedBalance());
    }

    public function test_it_creates_income_and_increases_balance(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'wallet' => $wallet] = $this->setupHome($user, 100000);

        $incomeCat = ExpenseCategory::create([
            'home_id' => $home->id,
            'name' => 'Salary',
            'type' => Expense::TYPE_INCOME,
            'icon' => '💰',
            'color' => '#22c55e',
            'is_system' => true,
        ]);

        $initialBalance = (float) $wallet->balance;

        $expense = $this->service->createExpense([
            'home_id' => $home->id,
            'wallet_id' => $wallet->id,
            'category_id' => $incomeCat->id,
            'type' => Expense::TYPE_INCOME,
            'amount' => 200000,
            'currency' => 'VND',
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ], $user);

        $wallet->refresh();
        $this->assertEquals($initialBalance + 200000, (float) $wallet->balance);
    }

    public function test_it_prevents_expense_on_wrong_home(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'wallet' => $wallet, 'category' => $cat] = $this->setupHome($user);

        // Create another home with a wallet that does NOT belong to the expense's home
        $otherUser = User::factory()->create();
        ['wallet' => $otherWallet] = $this->setupHome($otherUser);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $this->service->createExpense([
            'home_id' => $home->id,             // home A
            'wallet_id' => $otherWallet->id,     // wallet belongs to home B -> 403
            'category_id' => $cat->id,
            'type' => Expense::TYPE_EXPENSE,
            'amount' => 100000,
            'currency' => 'VND',
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ], $user);
    }

    public function test_it_updates_expense_and_adjusts_balance(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'wallet' => $wallet, 'category' => $cat] = $this->setupHome($user, 1000000);

        $expense = $this->service->createExpense([
            'home_id' => $home->id,
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'type' => Expense::TYPE_EXPENSE,
            'amount' => 300000,
            'currency' => 'VND',
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ], $user);

        $this->assertEquals(700000.0, (float) $wallet->fresh()->balance);

        $updated = $this->service->updateExpense($expense, [
            'amount' => 500000,
        ]);

        $wallet->refresh();
        $this->assertEquals(500000.0, (float) $wallet->balance);
        $this->assertEquals(500000.00, (float) $updated->amount);
    }

    public function test_it_updates_expense_type_and_adjusts_balance(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'wallet' => $wallet, 'category' => $cat] = $this->setupHome($user, 1000000);

        $expense = $this->service->createExpense([
            'home_id' => $home->id,
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'type' => Expense::TYPE_EXPENSE,
            'amount' => 200000,
            'currency' => 'VND',
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ], $user);

        $this->assertEquals(800000.0, (float) $wallet->fresh()->balance);

        // Change from expense to income (reverse the -200000 to +200000 => net +400000)
        $incomeCat = ExpenseCategory::create([
            'home_id' => $home->id,
            'name' => 'Refund',
            'type' => Expense::TYPE_INCOME,
            'icon' => '↩️',
            'color' => '#22c55e',
            'is_system' => true,
        ]);

        $this->service->updateExpense($expense, [
            'type' => Expense::TYPE_INCOME,
            'category_id' => $incomeCat->id,
        ]);

        $wallet->refresh();
        $this->assertEquals(1200000.0, (float) $wallet->balance);
    }

    public function test_it_deletes_expense_and_reverses_balance(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'wallet' => $wallet, 'category' => $cat] = $this->setupHome($user, 1000000);

        $expense = $this->service->createExpense([
            'home_id' => $home->id,
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'type' => Expense::TYPE_EXPENSE,
            'amount' => 400000,
            'currency' => 'VND',
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ], $user);

        $this->assertEquals(600000.0, (float) $wallet->fresh()->balance);

        $this->service->deleteExpense($expense);

        $wallet->refresh();
        $this->assertEquals(1000000.0, (float) $wallet->balance);
        $this->assertSoftDeleted('expenses', ['id' => $expense->id]);
    }

    public function test_it_prevents_deleting_transfer_linked_expense(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'wallet' => $wallet, 'category' => $cat] = $this->setupHome($user);

        // Create a second wallet and a transfer
        $wallet2 = Wallet::create([
            'home_id' => $home->id,
            'name' => 'Bank',
            'type' => Wallet::TYPE_BANK,
            'currency' => 'VND',
            'opening_balance' => 500000,
            'balance' => 500000,
        ]);

        $transfer = Transfer::create([
            'home_id' => $home->id,
            'from_wallet_id' => $wallet->id,
            'to_wallet_id' => $wallet2->id,
            'user_id' => $user->id,
            'amount' => 100000,
            'fee' => 0,
            'currency' => 'VND',
            'occurred_at' => now(),
        ]);

        // Create an expense linked to the transfer
        $linkedExpense = Expense::create([
            'home_id' => $home->id,
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'user_id' => $user->id,
            'type' => Expense::TYPE_EXPENSE,
            'amount' => 100000,
            'currency' => 'VND',
            'occurred_at' => now(),
            'transfer_id' => $transfer->id,
        ]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $this->service->deleteExpense($linkedExpense);
    }

    public function test_it_prevents_updating_transfer_linked_expense(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'wallet' => $wallet, 'category' => $cat] = $this->setupHome($user);

        $wallet2 = Wallet::create([
            'home_id' => $home->id,
            'name' => 'Bank',
            'type' => Wallet::TYPE_BANK,
            'currency' => 'VND',
            'opening_balance' => 500000,
            'balance' => 500000,
        ]);

        $transfer = Transfer::create([
            'home_id' => $home->id,
            'from_wallet_id' => $wallet->id,
            'to_wallet_id' => $wallet2->id,
            'user_id' => $user->id,
            'amount' => 100000,
            'fee' => 0,
            'currency' => 'VND',
            'occurred_at' => now(),
        ]);

        $linkedExpense = Expense::create([
            'home_id' => $home->id,
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'user_id' => $user->id,
            'type' => Expense::TYPE_EXPENSE,
            'amount' => 100000,
            'currency' => 'VND',
            'occurred_at' => now(),
            'transfer_id' => $transfer->id,
        ]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $this->service->updateExpense($linkedExpense, ['amount' => 50000]);
    }
}
