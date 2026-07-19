<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\Transfer;
use Modules\Expense\Services\TransferService;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;
use Modules\Wallet\Models\Wallet;
use Tests\TestCase;

class TransferServiceTest extends TestCase
{
    use RefreshDatabase;

    private TransferService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TransferService::class);
    }

    private function setupTwoWallets(User $user, float $bal1 = 500000, float $bal2 = 100000): array
    {
        $home = new Home(['name' => 'Test Home', 'currency' => 'VND', 'timezone' => 'Asia/Ho_Chi_Minh']);
        $home->forceFill(['owner_id' => $user->id])->save();

        HomeMember::create([
            'home_id' => $home->id,
            'user_id' => $user->id,
            'role' => HomeMember::ROLE_OWNER,
        ]);

        $wallet1 = Wallet::create([
            'home_id' => $home->id,
            'name' => 'Cash',
            'type' => Wallet::TYPE_CASH,
            'currency' => 'VND',
            'opening_balance' => $bal1,
            'balance' => $bal1,
        ]);

        $wallet2 = Wallet::create([
            'home_id' => $home->id,
            'name' => 'Bank',
            'type' => Wallet::TYPE_BANK,
            'currency' => 'VND',
            'opening_balance' => $bal2,
            'balance' => $bal2,
        ]);

        return compact('home', 'wallet1', 'wallet2');
    }

    public function test_it_creates_transfer_and_moves_money(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'wallet1' => $from, 'wallet2' => $to] = $this->setupTwoWallets($user, 500000, 100000);

        $transfer = $this->service->createTransfer([
            'home_id' => $home->id,
            'from_wallet_id' => $from->id,
            'to_wallet_id' => $to->id,
            'amount' => 200000,
            'fee' => 0,
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ], $user);

        $this->assertInstanceOf(Transfer::class, $transfer);
        $this->assertDatabaseHas('transfers', [
            'id' => $transfer->id,
            'from_wallet_id' => $from->id,
            'to_wallet_id' => $to->id,
            'amount' => 200000.00,
        ]);

        // Verify wallet balances
        $this->assertEquals(300000.0, (float) $from->fresh()->balance);
        $this->assertEquals(300000.0, (float) $to->fresh()->balance);

        // Verify expense records
        $this->assertDatabaseCount('expenses', 2);
    }

    public function test_it_creates_transfer_with_fee(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'wallet1' => $from, 'wallet2' => $to] = $this->setupTwoWallets($user, 500000, 100000);

        $transfer = $this->service->createTransfer([
            'home_id' => $home->id,
            'from_wallet_id' => $from->id,
            'to_wallet_id' => $to->id,
            'amount' => 200000,
            'fee' => 10000,
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ], $user);

        // Source: 500000 - 200000 - 10000 = 290000
        $this->assertEquals(290000.0, (float) $from->fresh()->balance);
        // Dest: 100000 + 200000 = 300000
        $this->assertEquals(300000.0, (float) $to->fresh()->balance);
        // Fee creates an extra expense record
        $this->assertDatabaseCount('expenses', 3);
    }

    public function test_it_prevents_same_wallet_transfer(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'wallet1' => $wallet] = $this->setupTwoWallets($user);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot transfer to the same wallet');

        $this->service->createTransfer([
            'home_id' => $home->id,
            'from_wallet_id' => $wallet->id,
            'to_wallet_id' => $wallet->id,
            'amount' => 100000,
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ], $user);
    }

    public function test_it_prevents_transfer_from_credit_card(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'wallet2' => $bank] = $this->setupTwoWallets($user, 500000, 100000);

        $creditCard = Wallet::create([
            'home_id' => $home->id,
            'name' => 'Credit Card',
            'type' => Wallet::TYPE_CREDIT_CARD,
            'currency' => 'VND',
            'opening_balance' => 0,
            'balance' => 0,
        ]);

        $this->expectException(\RuntimeException::class);

        $this->service->createTransfer([
            'home_id' => $home->id,
            'from_wallet_id' => $creditCard->id,
            'to_wallet_id' => $bank->id,
            'amount' => 100000,
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ], $user);
    }

    public function test_it_prevents_transfer_without_funds(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'wallet1' => $from, 'wallet2' => $to] = $this->setupTwoWallets($user, 50000, 0);

        $this->expectException(\RuntimeException::class);

        $this->service->createTransfer([
            'home_id' => $home->id,
            'from_wallet_id' => $from->id,
            'to_wallet_id' => $to->id,
            'amount' => 100000, // More than available 50000
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ], $user);
    }

    public function test_it_reverses_transfer(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'wallet1' => $from, 'wallet2' => $to] = $this->setupTwoWallets($user, 500000, 100000);

        $transfer = $this->service->createTransfer([
            'home_id' => $home->id,
            'from_wallet_id' => $from->id,
            'to_wallet_id' => $to->id,
            'amount' => 200000,
            'fee' => 0,
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ], $user);

        // Verify initial state
        $this->assertEquals(300000.0, (float) $from->fresh()->balance);
        $this->assertEquals(300000.0, (float) $to->fresh()->balance);

        // Reverse the transfer
        $this->service->reverseTransfer($transfer);

        // Balances should be restored
        $this->assertEquals(500000.0, (float) $from->fresh()->balance);
        $this->assertEquals(100000.0, (float) $to->fresh()->balance);

        // Transfer should be soft-deleted
        $this->assertSoftDeleted('transfers', ['id' => $transfer->id]);

        // Expenses should be soft-deleted
        $expenses = Expense::withTrashed()->where('transfer_id', $transfer->id)->get();
        $this->assertCount(2, $expenses);
        foreach ($expenses as $exp) {
            $this->assertNotNull($exp->deleted_at);
        }
    }

    public function test_transfer_creates_correct_expense_types(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'wallet1' => $from, 'wallet2' => $to] = $this->setupTwoWallets($user, 500000, 100000);

        $this->service->createTransfer([
            'home_id' => $home->id,
            'from_wallet_id' => $from->id,
            'to_wallet_id' => $to->id,
            'amount' => 150000,
            'fee' => 0,
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ], $user);

        $expenses = Expense::all();
        $this->assertCount(2, $expenses);

        $outExpense = $expenses->where('type', Expense::TYPE_EXPENSE)->first();
        $inExpense = $expenses->where('type', Expense::TYPE_INCOME)->first();

        $this->assertNotNull($outExpense);
        $this->assertNotNull($inExpense);
        $this->assertEquals($from->id, $outExpense->wallet_id);
        $this->assertEquals($to->id, $inExpense->wallet_id);
        $this->assertEquals(150000.00, (float) $outExpense->amount);
        $this->assertEquals(150000.00, (float) $inExpense->amount);
    }
}
