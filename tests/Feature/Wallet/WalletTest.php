<?php

namespace Tests\Feature\Wallet;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Expense\Models\Expense;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;
use Modules\Wallet\Models\Wallet;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    private function setupHome(User $owner): Home
    {
        $home = new Home(['name' => 'Test Home']);
        $home->forceFill(['owner_id' => $owner->id])->save();
        $m = HomeMember::create(['home_id' => $home->id, 'user_id' => $owner->id]);
        $m->assignRole('owner');

        return $home;
    }

    public function test_user_can_create_wallet(): void
    {
        $user = User::factory()->create();
        $home = $this->setupHome($user);

        $response = $this->actingAs($user)->post(route('wallets.store'), [
            'home_id' => $home->id,
            'name' => 'Ví tiền mặt',
            'type' => 'cash',
            'opening_balance' => 5000000,
            'currency' => 'VND',
            'icon' => '💵',
            'color' => '#10b981',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('wallets', [
            'home_id' => $home->id,
            'name' => 'Ví tiền mặt',
            'type' => 'cash',
            'opening_balance' => '5000000.00',
            'balance' => '5000000.00',
        ]);
    }

    public function test_wallet_balance_calculated_from_opening_balance(): void
    {
        $user = User::factory()->create();
        $home = $this->setupHome($user);
        $wallet = Wallet::create([
            'home_id' => $home->id,
            'name' => 'VCB',
            'type' => 'bank',
            'opening_balance' => 1000000,
            'currency' => 'VND',
        ]);

        $this->assertEquals(1000000.0, $wallet->calculatedBalance());
    }

    public function test_user_cannot_view_other_homes_wallet(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();
        $homeA = $this->setupHome($ownerA);
        $walletA = Wallet::create([
            'home_id' => $homeA->id,
            'name' => 'A wallet',
            'type' => 'cash',
            'opening_balance' => 1000,
            'currency' => 'VND',
        ]);

        $response = $this->actingAs($ownerB)->get(route('wallets.show', $walletA));

        $response->assertForbidden();
    }

    public function test_negative_opening_balance_rejected(): void
    {
        $user = User::factory()->create();
        $home = $this->setupHome($user);

        $response = $this->actingAs($user)->post(route('wallets.store'), [
            'home_id' => $home->id,
            'name' => 'Negative',
            'type' => 'cash',
            'opening_balance' => -1000,
        ]);

        $response->assertSessionHasErrors('opening_balance');
        $this->assertDatabaseCount('wallets', 0);
    }

    public function test_invalid_type_rejected(): void
    {
        $user = User::factory()->create();
        $home = $this->setupHome($user);

        $response = $this->actingAs($user)->post(route('wallets.store'), [
            'home_id' => $home->id,
            'name' => 'Invalid',
            'type' => 'crypto',
            'opening_balance' => 0,
        ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_wallet_factory_creates_valid_wallet(): void
    {
        $wallet = Wallet::factory()->cash()->create();

        $this->assertNotNull($wallet->id);
        $this->assertSame(Wallet::TYPE_CASH, $wallet->type);
        $this->assertGreaterThanOrEqual(0, (float) $wallet->opening_balance);
        $this->assertFalse($wallet->is_archived);
    }

    public function test_archived_wallets_can_be_restored(): void
    {
        $user = User::factory()->create();
        $home = $this->setupHome($user);
        $wallet = Wallet::create([
            'home_id' => $home->id,
            'name' => 'Archive test',
            'type' => 'cash',
            'opening_balance' => 0,
            'currency' => 'VND',
        ]);

        $wallet->archive();
        $this->assertTrue($wallet->fresh()->is_archived);

        $wallet->unarchive();
        $this->assertFalse($wallet->fresh()->is_archived);
    }

    public function test_wallet_with_balance_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $home = $this->setupHome($user);
        $wallet = Wallet::create([
            'home_id' => $home->id,
            'name' => 'Has money',
            'type' => 'cash',
            'opening_balance' => 100000,
            'balance' => 100000,
            'currency' => 'VND',
        ]);

        $this->assertFalse($wallet->canDelete());
    }

    public function test_wallet_with_zero_balance_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $home = $this->setupHome($user);
        $wallet = Wallet::create([
            'home_id' => $home->id,
            'name' => 'Empty',
            'type' => 'cash',
            'opening_balance' => 0,
            'balance' => 0,
            'currency' => 'VND',
        ]);

        $this->assertTrue($wallet->canDelete());
    }

    public function test_only_owner_can_delete_wallet(): void
    {
        $owner = User::factory()->create();
        $manager = User::factory()->create();
        $home = $this->setupHome($owner);

        $managerMember = HomeMember::create(['home_id' => $home->id, 'user_id' => $manager->id]);
        $managerMember->assignRole('manager');

        $wallet = Wallet::create([
            'home_id' => $home->id,
            'name' => 'Test',
            'type' => 'cash',
            'opening_balance' => 0,
            'currency' => 'VND',
        ]);

        $response = $this->actingAs($manager)->delete(route('wallets.destroy', $wallet));
        $response->assertForbidden();

        $response = $this->actingAs($owner)->delete(route('wallets.destroy', $wallet));
        $response->assertRedirect();
        $this->assertSoftDeleted('wallets', ['id' => $wallet->id]);
    }

    public function test_user_can_create_overdraft_wallet_and_net_balance_is_correct(): void
    {
        $user = User::factory()->create();
        $home = $this->setupHome($user);

        $response = $this->actingAs($user)->post(route('wallets.store'), [
            'home_id' => $home->id,
            'name' => 'Ví thấu chi test',
            'type' => 'overdraft',
            'opening_balance' => 10000000,
            'currency' => 'VND',
        ]);

        $response->assertRedirect();
        $wallet = Wallet::where('name', 'Ví thấu chi test')->first();
        $this->assertNotNull($wallet);
        $this->assertSame('overdraft', $wallet->type);
        $this->assertEquals(10000000.0, (float) $wallet->opening_balance);
        $this->assertEquals(0.0, $wallet->netBalance());
    }

    public function test_user_can_view_wallet_details_with_time_filters(): void
    {
        $user = User::factory()->create();
        $home = $this->setupHome($user);
        $wallet = Wallet::create([
            'home_id' => $home->id,
            'name' => 'Cash Wallet',
            'type' => 'cash',
            'opening_balance' => 1000000,
            'balance' => 1000000,
            'currency' => 'VND',
        ]);

        // Create an expense for today
        Expense::factory()->create([
            'home_id' => $home->id,
            'wallet_id' => $wallet->id,
            'type' => 'expense',
            'amount' => 50000,
            'occurred_at' => now(),
        ]);

        // Create an income for last month
        Expense::factory()->create([
            'home_id' => $home->id,
            'wallet_id' => $wallet->id,
            'type' => 'income',
            'amount' => 120000,
            'occurred_at' => now()->subMonth(),
        ]);

        // Test period = all
        $response = $this->actingAs($user)->get(route('wallets.show', [$wallet, 'period' => 'all']));
        $response->assertOk();
        $response->assertViewHas('totalSpent', 50000.0);
        $response->assertViewHas('totalIncome', 120000.0);

        // Test period = day (today)
        $response = $this->actingAs($user)->get(route('wallets.show', [
            $wallet,
            'period' => 'day',
            'date' => now()->format('Y-m-d'),
        ]));
        $response->assertOk();
        $response->assertViewHas('totalSpent', 50000.0);
        $response->assertViewHas('totalIncome', 0.0);

        // Test period = month (this month)
        $response = $this->actingAs($user)->get(route('wallets.show', [
            $wallet,
            'period' => 'month',
            'month' => now()->format('Y-m'),
        ]));
        $response->assertOk();
        $response->assertViewHas('totalSpent', 50000.0);
        $response->assertViewHas('totalIncome', 0.0);

        // Test period = month (last month)
        $response = $this->actingAs($user)->get(route('wallets.show', [
            $wallet,
            'period' => 'month',
            'month' => now()->subMonth()->format('Y-m'),
        ]));
        $response->assertOk();
        $response->assertViewHas('totalSpent', 0.0);
        $response->assertViewHas('totalIncome', 120000.0);
    }
}
