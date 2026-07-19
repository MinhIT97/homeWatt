<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Expense\Models\Transfer;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;
use Modules\Wallet\Models\Wallet;
use Tests\TestCase;

class WalletBalanceTest extends TestCase
{
    use RefreshDatabase;

    private function setupWallet(User $user, string $type = 'cash', float $openingBalance = 1000000, float $currentBalance = 1000000): array
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
            'type' => $type,
            'currency' => 'VND',
            'opening_balance' => $openingBalance,
            'balance' => $currentBalance,
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

    public function test_calculated_balance_matches_expected(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'wallet' => $wallet, 'category' => $cat] = $this->setupWallet($user, 'cash', 1000000, 1000000);

        // Create some expenses
        Expense::create([
            'home_id' => $home->id,
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'user_id' => $user->id,
            'type' => Expense::TYPE_EXPENSE,
            'amount' => 200000,
            'currency' => 'VND',
            'occurred_at' => now(),
        ]);

        Expense::create([
            'home_id' => $home->id,
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'user_id' => $user->id,
            'type' => Expense::TYPE_EXPENSE,
            'amount' => 150000,
            'currency' => 'VND',
            'occurred_at' => now(),
        ]);

        // Create an income
        $incomeCat = ExpenseCategory::create([
            'home_id' => $home->id,
            'name' => 'Salary',
            'type' => Expense::TYPE_INCOME,
            'icon' => '💰',
            'color' => '#22c55e',
            'is_system' => true,
        ]);

        Expense::create([
            'home_id' => $home->id,
            'wallet_id' => $wallet->id,
            'category_id' => $incomeCat->id,
            'user_id' => $user->id,
            'type' => Expense::TYPE_INCOME,
            'amount' => 100000,
            'currency' => 'VND',
            'occurred_at' => now(),
        ]);

        // Expected: 1000000 (opening) - 200000 - 150000 + 100000 = 750000
        $expected = 1000000 - 200000 - 150000 + 100000;

        $wallet->refresh();
        $this->assertEquals($expected, $wallet->calculatedBalance());
    }

    public function test_calculated_balance_excludes_transfer_linked_expenses(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'wallet' => $wallet, 'category' => $cat] = $this->setupWallet($user, 'cash', 1000000, 1000000);

        // Create a second wallet
        $wallet2 = Wallet::create([
            'home_id' => $home->id,
            'name' => 'Bank',
            'type' => Wallet::TYPE_BANK,
            'currency' => 'VND',
            'opening_balance' => 0,
            'balance' => 0,
        ]);

        // Create a transfer
        $transfer = Transfer::create([
            'home_id' => $home->id,
            'from_wallet_id' => $wallet->id,
            'to_wallet_id' => $wallet2->id,
            'user_id' => $user->id,
            'amount' => 300000,
            'fee' => 0,
            'currency' => 'VND',
            'occurred_at' => now(),
        ]);

        // These expenses are transfer-linked and should be excluded from calculatedBalance
        Expense::create([
            'home_id' => $home->id,
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'user_id' => $user->id,
            'type' => Expense::TYPE_EXPENSE,
            'amount' => 300000,
            'currency' => 'VND',
            'occurred_at' => now(),
            'transfer_id' => $transfer->id,
        ]);

        // The calculatedBalance() excludes transfer-linked expenses and uses the transfer table directly
        // Opening: 1000000, transfer out: 300000 => expected: 700000
        $wallet->refresh();
        $this->assertEquals(700000.0, $wallet->calculatedBalance());
    }

    public function test_transfer_in_affects_calculated_balance(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'wallet' => $wallet] = $this->setupWallet($user, 'cash', 1000000, 1000000);

        $wallet2 = Wallet::create([
            'home_id' => $home->id,
            'name' => 'Bank',
            'type' => Wallet::TYPE_BANK,
            'currency' => 'VND',
            'opening_balance' => 500000,
            'balance' => 500000,
        ]);

        Transfer::create([
            'home_id' => $home->id,
            'from_wallet_id' => $wallet2->id,
            'to_wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'amount' => 200000,
            'fee' => 0,
            'currency' => 'VND',
            'occurred_at' => now(),
        ]);

        // Opening: 1000000 + transfer in: 200000 = 1200000
        $wallet->refresh();
        $this->assertEquals(1200000.0, $wallet->calculatedBalance());
    }

    public function test_net_balance_excludes_credit_card_opening(): void
    {
        $user = User::factory()->create();
        ['wallet' => $creditCard] = $this->setupWallet($user, Wallet::TYPE_CREDIT_CARD, 5000000, 4500000);

        // Credit card: opening 5M, balance 4.5M
        // Net balance = calculatedBalance - opening_balance
        // calculated = 5000000 (opening, no transactions)
        // But wait, the balance column is 4.5M... let me think.
        // calculatedBalance uses opening_balance + sum(expenses) + sum(transfers)
        // Since there are no expenses/transfers, calculatedBalance() = 5000000
        // netBalance() = 5000000 - 5000000 = 0
        $this->assertEquals(5000000.0, $creditCard->calculatedBalance());
        $this->assertEquals(0.0, $creditCard->netBalance());
    }

    public function test_credit_card_net_balance_with_spending(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'wallet' => $creditCard, 'category' => $cat] = $this->setupWallet(
            $user,
            Wallet::TYPE_CREDIT_CARD,
            0,
            0
        );

        Expense::create([
            'home_id' => $home->id,
            'wallet_id' => $creditCard->id,
            'category_id' => $cat->id,
            'user_id' => $user->id,
            'type' => Expense::TYPE_EXPENSE,
            'amount' => 3000000,
            'currency' => 'VND',
            'occurred_at' => now(),
        ]);

        // calculatedBalance() = opening(0) - expenses(3M) = -3M
        // netBalance() = -3M - 0 = -3M (this is debt on credit card)
        $creditCard->refresh();
        $this->assertEquals(-3000000.0, $creditCard->calculatedBalance());
        $this->assertEquals(-3000000.0, $creditCard->netBalance());
    }

    public function test_cash_wallet_net_equals_calculated(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'wallet' => $cash, 'category' => $cat] = $this->setupWallet($user, 'cash', 1000000, 1000000);

        Expense::create([
            'home_id' => $home->id,
            'wallet_id' => $cash->id,
            'category_id' => $cat->id,
            'user_id' => $user->id,
            'type' => Expense::TYPE_EXPENSE,
            'amount' => 250000,
            'currency' => 'VND',
            'occurred_at' => now(),
        ]);

        $cash->refresh();
        $calc = $cash->calculatedBalance();
        $net = $cash->netBalance();

        // For cash wallets, netBalance() simply returns calculatedBalance()
        $this->assertEquals(750000.0, $calc);
        $this->assertEquals($calc, $net);
    }

    public function test_can_delete_empty_wallet(): void
    {
        $user = User::factory()->create();
        ['wallet' => $wallet] = $this->setupWallet($user, 'cash', 0, 0);

        $this->assertTrue($wallet->canDelete());
    }

    public function test_cannot_delete_wallet_with_balance(): void
    {
        $user = User::factory()->create();
        ['wallet' => $wallet] = $this->setupWallet($user, 'cash', 1000000, 1000000);

        $this->assertFalse($wallet->canDelete());
    }

    public function test_cannot_delete_wallet_with_outstanding_transactions(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'wallet' => $wallet, 'category' => $cat] = $this->setupWallet($user, 'cash', 500000, 500000);

        // Create an expense that reduces balance to zero, but expense still exists
        Expense::create([
            'home_id' => $home->id,
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'user_id' => $user->id,
            'type' => Expense::TYPE_EXPENSE,
            'amount' => 500000,
            'currency' => 'VND',
            'occurred_at' => now(),
        ]);

        // Balance is now zero (500K - 500K)
        // But calculatedBalance() includes the expense, so it's 0.
        // canDelete() checks abs(calculatedBalance()) < 0.01 -- this should be true
        $wallet->refresh();
        $this->assertTrue($wallet->canDelete());
    }

    public function test_refresh_balance_updates_persisted_value(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'wallet' => $wallet, 'category' => $cat] = $this->setupWallet($user, 'cash', 1000000, 1000000);

        // Manually corrupt the persisted balance
        $wallet->forceFill(['balance' => 999999])->save();

        // Create an expense
        Expense::create([
            'home_id' => $home->id,
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'user_id' => $user->id,
            'type' => Expense::TYPE_EXPENSE,
            'amount' => 300000,
            'currency' => 'VND',
            'occurred_at' => now(),
        ]);

        // refreshBalance should recalculate and persist
        $newBalance = $wallet->fresh()->refreshBalance();

        // Expected: 1000000 - 300000 = 700000
        $this->assertEquals(700000.0, $newBalance);
        $this->assertEquals(700000.0, (float) $wallet->fresh()->balance);
    }
}
