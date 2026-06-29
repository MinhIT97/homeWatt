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

        // Verify Inline Keyboard was sent
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage') &&
                isset($request['reply_markup']) &&
                str_contains(json_encode($request['reply_markup']), 'undo_expense:');
        });
    }

    public function test_telegram_callback_query_undo_expense(): void
    {
        config(['services.telegram.bot_token' => 'fake_token']);
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        ['user' => $user, 'techcombank' => $tech] = $this->setupUserWithWallets();

        // Create a pre-existing expense
        $expense = Expense::create([
            'home_id' => $tech->home_id,
            'wallet_id' => $tech->id,
            'category_id' => ExpenseCategory::where('home_id', $tech->home_id)->first()->id,
            'user_id' => $user->id,
            'type' => 'expense',
            'amount' => 50000,
            'occurred_at' => now(),
        ]);
        $tech->decrement('balance', 50000);

        $payload = [
            'callback_query' => [
                'id' => 'cb_query_123',
                'message' => [
                    'chat' => [
                        'id' => 123456789,
                    ],
                    'message_id' => 777,
                    'text' => 'Giao dịch chi tiêu',
                ],
                'data' => 'undo_expense:' . $expense->id,
            ],
        ];

        $response = $this->postJson('/api/v1/telegram/webhook', $payload);

        $response->assertStatus(200);

        // Assert Expense was deleted/soft-deleted
        $this->assertSoftDeleted('expenses', [
            'id' => $expense->id,
        ]);

        // Assert balance was restored
        $tech->refresh();
        $this->assertEquals(1000000.0, (float)$tech->balance);

        // Verify Callback response and EditMessageText were called
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'answerCallbackQuery') &&
                str_contains($request['text'], 'Đã hoàn tác chi tiêu thành công');
        });
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'editMessageText') &&
                str_contains($request['text'], 'Đã hoàn tác giao dịch này');
        });
    }

    public function test_telegram_callback_query_undo_transfer(): void
    {
        config(['services.telegram.bot_token' => 'fake_token']);
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        ['user' => $user, 'techcombank' => $tech, 'vpbank' => $vp] = $this->setupUserWithWallets();

        // Create a pre-existing transfer
        $transferService = app(\Modules\Expense\Services\TransferService::class);
        $transfer = $transferService->createTransfer([
            'home_id' => $tech->home_id,
            'from_wallet_id' => $tech->id,
            'to_wallet_id' => $vp->id,
            'amount' => 100000,
            'description' => 'Chuyển tiền',
        ], $user);

        $tech->refresh();
        $vp->refresh();
        $this->assertEquals(900000.0, (float)$tech->balance);
        $this->assertEquals(600000.0, (float)$vp->balance);

        $payload = [
            'callback_query' => [
                'id' => 'cb_query_456',
                'message' => [
                    'chat' => [
                        'id' => 123456789,
                    ],
                    'message_id' => 888,
                    'text' => 'Giao dịch chuyển khoản',
                ],
                'data' => 'undo_transfer:' . $transfer->id,
            ],
        ];

        $response = $this->postJson('/api/v1/telegram/webhook', $payload);

        $response->assertStatus(200);

        // Assert Transfer was soft-deleted/deleted
        $this->assertSoftDeleted('transfers', [
            'id' => $transfer->id,
        ]);

        // Assert balances were restored
        $tech->refresh();
        $vp->refresh();
        $this->assertEquals(1000000.0, (float)$tech->balance);
        $this->assertEquals(500000.0, (float)$vp->balance);

        // Verify callback messages
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'answerCallbackQuery') &&
                str_contains($request['text'], 'Đã hoàn tác chuyển khoản thành công');
        });
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'editMessageText') &&
                str_contains($request['text'], 'Đã hoàn tác chuyển khoản này');
        });
    }

    public function test_telegram_callback_query_undo_expense_without_message_text(): void
    {
        config(['services.telegram.bot_token' => 'fake_token']);
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        ['user' => $user, 'techcombank' => $tech] = $this->setupUserWithWallets();

        $expense = Expense::create([
            'home_id' => $tech->home_id,
            'wallet_id' => $tech->id,
            'category_id' => ExpenseCategory::where('home_id', $tech->home_id)->first()->id,
            'user_id' => $user->id,
            'type' => 'expense',
            'amount' => 50000,
            'occurred_at' => now(),
        ]);
        $tech->decrement('balance', 50000);

        $payload = [
            'callback_query' => [
                'id' => 'cb_query_no_text',
                'message' => [
                    'chat' => [
                        'id' => 123456789,
                    ],
                    'message_id' => 999,
                ],
                'data' => 'undo_expense:' . $expense->id,
            ],
        ];

        $this->postJson('/api/v1/telegram/webhook', $payload)
            ->assertStatus(200);

        $this->assertSoftDeleted('expenses', [
            'id' => $expense->id,
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'answerCallbackQuery') &&
                str_contains($request['text'], 'Đã hoàn tác chi tiêu thành công');
        });

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'editMessageText') &&
                str_contains($request['text'], 'Đã hoàn tác giao dịch này');
        });
    }
}
