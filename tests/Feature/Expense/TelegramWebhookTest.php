<?php

namespace Tests\Feature\Expense;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Expense\Models\Transfer;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;
use Modules\Wallet\Models\Wallet;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function setupUserWithWallets(): array
    {
        $user = User::factory()->create([
            'telegram_chat_id' => 123456789,
        ]);

        $home = new Home(['name' => 'My Home']);
        $home->forceFill(['owner_id' => $user->id])->save();

        $m = HomeMember::create(['home_id' => $home->id, 'user_id' => $user->id]);
        $m->assignRole('owner');

        $techcombank = Wallet::create([
            'home_id' => $home->id,
            'name' => 'Ví thấu chi Techcombank',
            'type' => 'cash',
            'opening_balance' => 1000000,
            'balance' => 1000000,
            'currency' => 'VND',
        ]);

        $vpbank = Wallet::create([
            'home_id' => $home->id,
            'name' => 'Ví VPBank',
            'type' => 'cash',
            'opening_balance' => 500000,
            'balance' => 500000,
            'currency' => 'VND',
        ]);

        $otherCategory = ExpenseCategory::create([
            'home_id' => $home->id,
            'name' => 'Khác',
            'type' => 'expense',
            'category_group' => 'other',
            'is_system' => true,
        ]);

        return compact('user', 'home', 'techcombank', 'vpbank');
    }

    public function test_telegram_webhook_parses_transfer_transaction(): void
    {
        config(['services.telegram.bot_token' => 'fake_token']);
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        ['user' => $user, 'techcombank' => $tech, 'vpbank' => $vp] = $this->setupUserWithWallets();

        $payload = [
            'message' => [
                'chat' => [
                    'id' => 123456789,
                ],
                'text' => 'Chuyển 80k từ ví thấu chi techcombank sang ví vpbank',
            ],
        ];

        $response = $this->postJson('/api/v1/telegram/webhook', $payload);

        $response->assertStatus(200);

        // Assert Transfer was created in DB
        $this->assertDatabaseHas('transfers', [
            'from_wallet_id' => $tech->id,
            'to_wallet_id' => $vp->id,
            'amount' => '80000.00',
        ]);

        // Assert balances updated
        $tech->refresh();
        $vp->refresh();
        $this->assertEquals(920000.0, (float)$tech->balance);
        $this->assertEquals(580000.0, (float)$vp->balance);

        // Verify Telegram message was sent
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage') &&
                str_contains($request['text'], 'Chuyển khoản thành công') &&
                str_contains($request['text'], '80.000');
        });
    }

    public function test_telegram_webhook_parses_normal_expense(): void
    {
        config(['services.telegram.bot_token' => 'fake_token']);
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        ['user' => $user, 'techcombank' => $tech] = $this->setupUserWithWallets();

        $payload = [
            'message' => [
                'chat' => [
                    'id' => 123456789,
                ],
                'text' => 'chi 50k techcombank ăn trưa',
            ],
        ];

        $response = $this->postJson('/api/v1/telegram/webhook', $payload);

        $response->assertStatus(200);

        // Assert Expense was created in DB
        $this->assertDatabaseHas('expenses', [
            'wallet_id' => $tech->id,
            'amount' => '50000.00',
            'type' => 'expense',
        ]);

        // Assert balance updated
        $tech->refresh();
        $this->assertEquals(950000.0, (float)$tech->balance);
    }
}
