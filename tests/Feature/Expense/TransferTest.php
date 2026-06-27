<?php

namespace Tests\Feature\Expense;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\Transfer;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;
use Modules\Wallet\Models\Wallet;
use Tests\TestCase;

class TransferTest extends TestCase
{
    use RefreshDatabase;

    private function setupTwoWallets(User $owner, float $bal1 = 500000, float $bal2 = 100000): array
    {
        $home = new Home(['name' => 'Test Home']);
        $home->forceFill(['owner_id' => $owner->id])->save();
        $m = HomeMember::create(['home_id' => $home->id, 'user_id' => $owner->id]);
        $m->assignRole('owner');
        $w1 = Wallet::create([
            'home_id' => $home->id, 'name' => 'Cash', 'type' => 'cash',
            'opening_balance' => $bal1, 'balance' => $bal1, 'currency' => 'VND',
        ]);
        $w2 = Wallet::create([
            'home_id' => $home->id, 'name' => 'Bank', 'type' => 'bank',
            'opening_balance' => $bal2, 'balance' => $bal2, 'currency' => 'VND',
        ]);

        return compact('home', 'w1', 'w2');
    }

    public function test_user_can_transfer_between_wallets(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'w1' => $from, 'w2' => $to] = $this->setupTwoWallets($user);

        $response = $this->actingAs($user)->post(route('transfers.store'), [
            'home_id' => $home->id,
            'from_wallet_id' => $from->id,
            'to_wallet_id' => $to->id,
            'amount' => 100000,
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('transfers', 1);
        $this->assertDatabaseCount('expenses', 2); // out + in
    }

    public function test_transfer_moves_money_between_wallets(): void
    {
        $user = User::factory()->create();
        ['w1' => $from, 'w2' => $to] = $this->setupTwoWallets($user, 500000, 100000);

        $this->actingAs($user)->post(route('transfers.store'), [
            'home_id' => $from->home_id,
            'from_wallet_id' => $from->id,
            'to_wallet_id' => $to->id,
            'amount' => 100000,
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $this->assertEquals(400000.0, (float) $from->fresh()->balance);
        $this->assertEquals(200000.0, (float) $to->fresh()->balance);
    }

    public function test_cannot_transfer_to_same_wallet(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'w1' => $wallet] = $this->setupTwoWallets($user);

        $response = $this->actingAs($user)->post(route('transfers.store'), [
            'home_id' => $home->id,
            'from_wallet_id' => $wallet->id,
            'to_wallet_id' => $wallet->id,
            'amount' => 100,
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_transfer_with_insufficient_balance_fails(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'w1' => $from, 'w2' => $to] = $this->setupTwoWallets($user, 100, 0);

        $response = $this->actingAs($user)->post(route('transfers.store'), [
            'home_id' => $home->id,
            'from_wallet_id' => $from->id,
            'to_wallet_id' => $to->id,
            'amount' => 100000,
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(0, Transfer::count());
    }

    public function test_transfer_creates_two_expense_records(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'w1' => $from, 'w2' => $to] = $this->setupTwoWallets($user);

        $this->actingAs($user)->post(route('transfers.store'), [
            'home_id' => $home->id,
            'from_wallet_id' => $from->id,
            'to_wallet_id' => $to->id,
            'amount' => 50000,
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $expenses = Expense::all();
        $this->assertCount(2, $expenses);

        $outExpense = $expenses->where('type', 'expense')->first();
        $inExpense = $expenses->where('type', 'income')->first();

        $this->assertSame($from->id, $outExpense->wallet_id);
        $this->assertSame($to->id, $inExpense->wallet_id);
        $this->assertEquals(50000.0, (float) $outExpense->amount);
        $this->assertEquals(50000.0, (float) $inExpense->amount);
    }
}
