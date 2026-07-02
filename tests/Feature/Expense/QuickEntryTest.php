<?php

namespace Tests\Feature\Expense;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Expense\Models\ExpenseRecurringTransaction;
use Modules\Expense\Models\Transfer;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;
use Modules\Wallet\Models\Wallet;
use Tests\TestCase;

class QuickEntryTest extends TestCase
{
    use RefreshDatabase;

    private function setupHome(User $user): array
    {
        $home = new Home(['name' => 'Nhà chính']);
        $home->forceFill(['owner_id' => $user->id])->save();
        $member = HomeMember::create(['home_id' => $home->id, 'user_id' => $user->id]);
        $member->assignRole('owner');

        $cash = Wallet::create([
            'home_id' => $home->id,
            'name' => 'Tiền mặt',
            'type' => Wallet::TYPE_CASH,
            'currency' => 'VND',
            'opening_balance' => 2000000,
            'balance' => 2000000,
        ]);
        $momo = Wallet::create([
            'home_id' => $home->id,
            'name' => 'MoMo',
            'type' => Wallet::TYPE_BANK,
            'currency' => 'VND',
            'opening_balance' => 2000000,
            'balance' => 2000000,
        ]);
        $vcb = Wallet::create([
            'home_id' => $home->id,
            'name' => 'Vietcombank',
            'type' => Wallet::TYPE_BANK,
            'currency' => 'VND',
            'opening_balance' => 1000000,
            'balance' => 1000000,
        ]);
        $vpbank = Wallet::create([
            'home_id' => $home->id,
            'name' => 'VPBank',
            'type' => Wallet::TYPE_BANK,
            'currency' => 'VND',
            'opening_balance' => 1000000,
            'balance' => 1000000,
        ]);

        $food = $this->category($home, 'Ăn uống', Expense::TYPE_EXPENSE, null, '🍜');
        $transport = $this->category($home, 'Đi lại', Expense::TYPE_EXPENSE, null, '🚗');
        $this->category($home, 'Xăng xe', Expense::TYPE_EXPENSE, null, '⛽', $transport->id);
        $this->category($home, 'Cafe', Expense::TYPE_EXPENSE, null, '☕', $food->id);
        $this->category($home, 'Khác', Expense::TYPE_EXPENSE, ExpenseCategory::GROUP_OTHER);
        $this->category($home, 'Thu nhập khác', Expense::TYPE_INCOME, ExpenseCategory::GROUP_OTHER);
        $this->category($home, 'Cho vay', Expense::TYPE_EXPENSE, ExpenseCategory::GROUP_LENDING);
        $this->category($home, 'Trả nợ', Expense::TYPE_EXPENSE, ExpenseCategory::GROUP_DEBT_REPAYMENT);
        $this->category($home, 'Thu nợ', Expense::TYPE_INCOME, ExpenseCategory::GROUP_DEBT_COLLECTION);
        $this->category($home, 'Đi vay', Expense::TYPE_INCOME, ExpenseCategory::GROUP_BORROWING);

        return compact('home', 'cash', 'momo', 'vcb', 'vpbank', 'food', 'transport');
    }

    private function category(Home $home, string $name, string $type, ?string $group = null, string $icon = '🧾', ?int $parentId = null): ExpenseCategory
    {
        return ExpenseCategory::create([
            'home_id' => $home->id,
            'parent_id' => $parentId,
            'name' => $name,
            'type' => $type,
            'category_group' => $group,
            'icon' => $icon,
            'is_system' => true,
        ]);
    }

    public function test_quick_preview_and_store_chat_expense(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'cash' => $cash] = $this->setupHome($user);

        $preview = $this->actingAs($user)->postJson(route('expenses.quick.preview'), [
            'home_id' => $home->id,
            'text' => 'ăn sáng 35k tiền mặt',
        ]);

        $preview->assertOk()
            ->assertJsonPath('items.0.ok', true)
            ->assertJsonPath('items.0.amount', 35000)
            ->assertJsonPath('items.0.wallet_id', $cash->id)
            ->assertJsonPath('items.0.category_name', 'Ăn uống');

        $item = $preview->json('items.0');
        $store = $this->actingAs($user)->postJson(route('expenses.quick.store'), [
            'items' => [$item],
        ]);

        $store->assertOk()->assertJsonPath('ok', true);
        $this->assertDatabaseHas('expenses', [
            'home_id' => $home->id,
            'wallet_id' => $cash->id,
            'type' => Expense::TYPE_EXPENSE,
            'amount' => '35000.00',
        ]);
    }

    public function test_quick_preview_and_store_transfer_without_income_pollution(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'momo' => $momo, 'vcb' => $vcb] = $this->setupHome($user);

        $preview = $this->actingAs($user)->postJson(route('expenses.quick.preview'), [
            'home_id' => $home->id,
            'text' => 'chuyển 500k từ momo sang vcb',
        ]);

        $preview->assertOk()
            ->assertJsonPath('items.0.mode', 'transfer')
            ->assertJsonPath('items.0.from_wallet_id', $momo->id)
            ->assertJsonPath('items.0.to_wallet_id', $vcb->id)
            ->assertJsonPath('items.0.amount', 500000);

        $this->actingAs($user)->postJson(route('expenses.quick.store'), [
            'items' => [$preview->json('items.0')],
        ])->assertOk();

        $this->assertSame(1, Transfer::count());
        $this->assertSame(2, Expense::whereNotNull('transfer_id')->count());
        $this->assertSame(0, Expense::whereNull('transfer_id')->where('type', Expense::TYPE_INCOME)->count());
    }

    public function test_quick_store_detects_duplicate_and_allows_force_save(): void
    {
        $user = User::factory()->create();
        ['home' => $home] = $this->setupHome($user);

        $item = $this->actingAs($user)->postJson(route('expenses.quick.preview'), [
            'home_id' => $home->id,
            'text' => 'cafe 35k',
        ])->json('items.0');

        $this->actingAs($user)->postJson(route('expenses.quick.store'), [
            'items' => [$item],
        ])->assertOk();

        $this->actingAs($user)->postJson(route('expenses.quick.store'), [
            'items' => [$item],
        ])->assertStatus(409)->assertJsonPath('duplicate', true);

        $this->actingAs($user)->postJson(route('expenses.quick.store'), [
            'items' => [$item],
            'force' => true,
        ])->assertOk();

        $this->assertSame(2, Expense::whereNull('transfer_id')->count());
    }

    public function test_quick_preview_uses_recent_wallet_category_habit(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'vpbank' => $vpbank] = $this->setupHome($user);

        $first = $this->actingAs($user)->postJson(route('expenses.quick.preview'), [
            'home_id' => $home->id,
            'text' => 'xăng 50k vpbank',
        ])->json('items.0');

        $this->actingAs($user)->postJson(route('expenses.quick.store'), [
            'items' => [$first],
        ])->assertOk();

        $preview = $this->actingAs($user)->postJson(route('expenses.quick.preview'), [
            'home_id' => $home->id,
            'text' => 'xăng 60k',
        ]);

        $preview->assertOk()
            ->assertJsonPath('items.0.wallet_id', $vpbank->id);
    }

    public function test_quick_templates_preview_and_store(): void
    {
        $user = User::factory()->create();
        ['home' => $home] = $this->setupHome($user);

        $templates = $this->actingAs($user)->getJson(route('expenses.quick.templates', ['home_id' => $home->id]));
        $templates->assertOk();
        $templateId = collect($templates->json('templates'))->firstWhere('name', 'Cafe')['id'];

        $preview = $this->actingAs($user)->postJson(route('expenses.quick.preview'), [
            'home_id' => $home->id,
            'template_id' => $templateId,
            'amount' => '35k',
        ]);

        $preview->assertOk()
            ->assertJsonPath('items.0.source', 'template')
            ->assertJsonPath('items.0.amount', 35000)
            ->assertJsonPath('items.0.description', 'Cafe');
    }

    public function test_recurring_transaction_command_generates_due_expense(): void
    {
        $user = User::factory()->create();
        ['home' => $home, 'cash' => $cash, 'food' => $food] = $this->setupHome($user);

        $this->actingAs($user)->postJson(route('expenses.quick.recurring.store'), [
            'home_id' => $home->id,
            'wallet_id' => $cash->id,
            'category_id' => $food->id,
            'name' => 'Ăn trưa định kỳ',
            'type' => Expense::TYPE_EXPENSE,
            'amount' => '60k',
            'frequency' => ExpenseRecurringTransaction::FREQUENCY_MONTHLY,
            'next_due_date' => now()->toDateString(),
        ])->assertCreated();

        $this->artisan('expenses:generate-recurring')->assertExitCode(0);

        $this->assertDatabaseHas('expenses', [
            'home_id' => $home->id,
            'wallet_id' => $cash->id,
            'category_id' => $food->id,
            'amount' => '60000.00',
        ]);
    }
}
