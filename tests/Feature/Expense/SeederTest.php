<?php

namespace Tests\Feature\Expense;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Expense\Database\Seeders\DefaultCategoriesSeeder;
use Modules\Expense\Database\Seeders\ExpenseDemoDataSeeder;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\Transfer;
use Modules\Home\Models\Home;
use Modules\Wallet\Models\Wallet;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_categories_seeder_runs_successfully(): void
    {
        $user = User::factory()->create();
        // 1. Create a home so the seeder has something to seed for
        $home = Home::forceCreate([
            'owner_id' => $user->id,
            'name' => 'My Home',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'currency' => 'VND',
        ]);

        // 2. Run the DefaultCategoriesSeeder
        $this->seed(DefaultCategoriesSeeder::class);

        // 3. Verify category data exists
        $this->assertDatabaseHas('expense_categories', [
            'home_id' => $home->id,
            'name' => 'Ăn uống',
            'type' => 'expense',
        ]);

        $this->assertDatabaseHas('expense_categories', [
            'home_id' => $home->id,
            'name' => 'Con cái',
            'type' => 'expense',
        ]);

        $this->assertDatabaseHas('expense_categories', [
            'home_id' => $home->id,
            'name' => 'Đầu tư & Tiết kiệm',
            'type' => 'expense',
        ]);

        $this->assertDatabaseHas('expense_categories', [
            'home_id' => $home->id,
            'name' => 'Đầu tư',
            'type' => 'income',
        ]);
    }

    public function test_expense_demo_data_seeder_runs_successfully(): void
    {
        // Run the ExpenseDemoDataSeeder
        $this->seed(ExpenseDemoDataSeeder::class);

        // Verify that the demo user was created
        $this->assertDatabaseHas('users', [
            'email' => 'demo@homewatt.com',
            'name' => 'Demo User',
        ]);

        // Verify that the demo home was created
        $this->assertDatabaseHas('homes', [
            'name' => 'Ngôi nhà mẫu',
        ]);

        // Verify wallets were created
        $this->assertDatabaseHas('wallets', [
            'name' => 'Tiền mặt',
            'type' => 'cash',
        ]);

        $this->assertDatabaseHas('wallets', [
            'name' => 'Tài khoản ngân hàng',
            'type' => 'bank',
        ]);

        $this->assertDatabaseHas('wallets', [
            'name' => 'Thẻ tín dụng',
            'type' => 'credit_card',
        ]);

        // Verify categories were created
        $this->assertDatabaseHas('expense_categories', [
            'name' => 'Ăn uống',
        ]);

        // Verify that transaction records (expenses/transfers) were created
        $this->assertGreaterThan(0, Expense::count());
        $this->assertGreaterThan(0, Transfer::count());

        // Verify that wallet balances are calculated/refreshed
        $cashWallet = Wallet::where('name', 'Tiền mặt')->first();
        $bankWallet = Wallet::where('name', 'Tài khoản ngân hàng')->first();
        $creditWallet = Wallet::where('name', 'Thẻ tín dụng')->first();

        $this->assertNotNull($cashWallet);
        $this->assertNotNull($bankWallet);
        $this->assertNotNull($creditWallet);
    }
}
