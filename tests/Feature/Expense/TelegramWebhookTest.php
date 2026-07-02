<?php

namespace Tests\Feature\Expense;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\AI\Services\GeminiElectricBillScanner;
use Modules\Energy\Models\EnergyBill;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Expense\Models\Transfer;
use Modules\Expense\Services\ExpenseService;
use Modules\Expense\Services\TransferService;
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
            'description' => 'Chuyển Tiền',
        ]);

        // Assert balances updated
        $tech->refresh();
        $vp->refresh();
        $this->assertEquals(920000.0, (float) $tech->balance);
        $this->assertEquals(580000.0, (float) $vp->balance);

        // Verify Telegram message was sent
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage') &&
                str_contains($request['text'], 'Chuyển khoản thành công') &&
                str_contains($request['text'], '80.000');
        });
    }

    public function test_telegram_webhook_parses_transfer_transaction_with_overdraft_short_name(): void
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
                'text' => 'chuyển 35k từ thấu chi techcombank sang vpbank',
            ],
        ];

        $response = $this->postJson('/api/v1/telegram/webhook', $payload);

        $response->assertStatus(200);

        // Assert Transfer was created in DB
        $this->assertDatabaseHas('transfers', [
            'from_wallet_id' => $tech->id,
            'to_wallet_id' => $vp->id,
            'amount' => '35000.00',
            'description' => 'Chuyển Tiền',
        ]);

        // Assert balances updated
        $tech->refresh();
        $vp->refresh();
        $this->assertEquals(965000.0, (float) $tech->balance);
        $this->assertEquals(535000.0, (float) $vp->balance);

        // Verify Telegram message was sent
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && str_contains($request['text'], 'Chuyển khoản thành công')
                && str_contains($request['text'], '35.000');
        });
    }

    public function test_telegram_webhook_parses_lending_to_named_borrower_with_wallet(): void
    {
        config(['services.telegram.bot_token' => 'fake_token']);
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        ['home' => $home, 'techcombank' => $tech] = $this->setupUserWithWallets();

        $lendingCategory = ExpenseCategory::create([
            'home_id' => $home->id,
            'name' => 'Cho vay',
            'type' => 'expense',
            'category_group' => ExpenseCategory::GROUP_LENDING,
            'is_system' => true,
        ]);

        $response = $this->postJson('/api/v1/telegram/webhook', [
            'message' => [
                'chat' => [
                    'id' => 123456789,
                ],
                'text' => 'cho Hường Nguyễn vay 35k tài khoản thấu chi techcombank',
            ],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('expenses', [
            'wallet_id' => $tech->id,
            'category_id' => $lendingCategory->id,
            'amount' => '35000.00',
            'type' => 'expense',
            'description' => 'Hường Nguyễn',
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage') &&
                str_contains($request['text'], 'CHO VAY') &&
                str_contains($request['text'], 'Người vay') &&
                str_contains($request['text'], 'Hường Nguyễn') &&
                ! str_contains($request['text'], 'THU NHẬP');
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
            'description' => 'Ăn Trưa',
        ]);

        // Assert balance updated
        $tech->refresh();
        $this->assertEquals(950000.0, (float) $tech->balance);

        // Verify Inline Keyboard was sent
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage') &&
                isset($request['reply_markup']) &&
                str_contains(json_encode($request['reply_markup']), 'undo_expense:');
        });
    }

    public function test_telegram_webhook_supports_batch_transaction_lines(): void
    {
        config(['services.telegram.bot_token' => 'fake_token']);
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        ['techcombank' => $tech] = $this->setupUserWithWallets();

        $response = $this->postJson('/api/v1/telegram/webhook', [
            'message' => [
                'chat' => [
                    'id' => 123456789,
                ],
                'text' => "chi 10k techcombank cafe\nchi 20k techcombank xăng xe",
            ],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('expenses', [
            'wallet_id' => $tech->id,
            'amount' => '10000.00',
            'description' => 'Cafe',
        ]);
        $this->assertDatabaseHas('expenses', [
            'wallet_id' => $tech->id,
            'amount' => '20000.00',
            'description' => 'Xăng Xe',
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage') &&
                str_contains($request['text'], 'Đã xử lý 2/2 dòng');
        });
    }

    public function test_telegram_webhook_supports_yesterday_date_hint(): void
    {
        config(['services.telegram.bot_token' => 'fake_token']);
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        ['techcombank' => $tech] = $this->setupUserWithWallets();

        $response = $this->postJson('/api/v1/telegram/webhook', [
            'message' => [
                'chat' => [
                    'id' => 123456789,
                ],
                'text' => 'hôm qua chi 50k techcombank ăn trưa',
            ],
        ]);

        $response->assertStatus(200);

        $expense = Expense::where('wallet_id', $tech->id)
            ->where('amount', '50000.00')
            ->first();

        $this->assertNotNull($expense);
        $this->assertSame(now()->subDay()->toDateString(), $expense->occurred_at->toDateString());
        $this->assertSame('Ăn Trưa', $expense->description);
    }

    public function test_telegram_today_summary_excludes_transfer_legs_from_income(): void
    {
        config(['services.telegram.bot_token' => 'fake_token']);
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        ['user' => $user, 'home' => $home, 'techcombank' => $tech, 'vpbank' => $vp] = $this->setupUserWithWallets();

        $incomeCategory = ExpenseCategory::create([
            'home_id' => $home->id,
            'name' => 'Lương',
            'type' => 'income',
            'is_system' => true,
        ]);
        $expenseCategory = ExpenseCategory::where('home_id', $home->id)->where('type', 'expense')->first();

        Expense::create([
            'home_id' => $home->id,
            'wallet_id' => $tech->id,
            'category_id' => $incomeCategory->id,
            'user_id' => $user->id,
            'type' => 'income',
            'amount' => 100000,
            'occurred_at' => now(),
        ]);
        Expense::create([
            'home_id' => $home->id,
            'wallet_id' => $tech->id,
            'category_id' => $expenseCategory->id,
            'user_id' => $user->id,
            'type' => 'expense',
            'amount' => 50000,
            'occurred_at' => now(),
        ]);

        app(TransferService::class)->createTransfer([
            'home_id' => $home->id,
            'from_wallet_id' => $tech->id,
            'to_wallet_id' => $vp->id,
            'amount' => 70000,
            'description' => 'Chuyển ví',
            'occurred_at' => now(),
        ], $user);

        $response = $this->postJson('/api/v1/telegram/webhook', [
            'message' => [
                'chat' => [
                    'id' => 123456789,
                ],
                'text' => '/today',
            ],
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage') &&
                str_contains($request['text'], 'TÓM TẮT HÔM NAY') &&
                str_contains($request['text'], 'Thu nhập thật: *100.000 đ*') &&
                str_contains($request['text'], 'Chi tiêu thật: *50.000 đ*') &&
                str_contains($request['text'], 'Chuyển ví: *70.000 đ*') &&
                ! str_contains($request['text'], 'Thu nhập thật: *170.000 đ*');
        });
    }

    public function test_telegram_commands_menu_and_recent_commands(): void
    {
        config(['services.telegram.bot_token' => 'fake_token']);
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        ['user' => $user, 'home' => $home, 'techcombank' => $tech] = $this->setupUserWithWallets();
        $category = ExpenseCategory::where('home_id', $home->id)->first();

        Expense::create([
            'home_id' => $home->id,
            'wallet_id' => $tech->id,
            'category_id' => $category->id,
            'user_id' => $user->id,
            'type' => 'expense',
            'amount' => 45000,
            'description' => 'Cafe',
            'occurred_at' => now(),
        ]);

        $this->postJson('/api/v1/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 123456789],
                'text' => '/lenh',
            ],
        ])->assertStatus(200);

        $this->postJson('/api/v1/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 123456789],
                'text' => '/recent 3',
            ],
        ])->assertStatus(200);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage') &&
                str_contains($request['text'], 'LỆNH NHANH HOMEWATT BOT');
        });

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage') &&
                str_contains($request['text'], '3 GIAO DỊCH GẦN ĐÂY') &&
                str_contains($request['text'], 'Cafe');
        });
    }

    public function test_telegram_photo_electric_bill_previews_then_creates_expense_and_energy_bill(): void
    {
        config(['services.telegram.bot_token' => 'fake_token']);
        Http::fake([
            'api.telegram.org/botfake_token/getFile*' => Http::response([
                'ok' => true,
                'result' => [
                    'file_path' => 'photos/electric-bill.jpg',
                ],
            ], 200),
            'api.telegram.org/file/botfake_token/*' => Http::response('fake-image-bytes', 200),
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $this->app->instance(GeminiElectricBillScanner::class, new class extends GeminiElectricBillScanner
        {
            public function __construct() {}

            public function scan(string $imageBase64): ?array
            {
                return [
                    'is_electric_bill' => true,
                    'old_index' => 1200.0,
                    'new_index' => 1358.0,
                    'kwh' => 158.0,
                    'amount' => 456789.0,
                    'merchant' => 'EVN',
                    'customer_name' => 'Nguyen Van A',
                    'customer_code' => 'PE123456789',
                    'billing_month' => '06/2026',
                ];
            }
        });

        ['user' => $user, 'home' => $home, 'techcombank' => $tech] = $this->setupUserWithWallets();

        $response = $this->postJson('/api/v1/telegram/webhook', [
            'message' => [
                'chat' => [
                    'id' => 123456789,
                ],
                'photo' => [
                    ['file_id' => 'small_photo'],
                    ['file_id' => 'large_photo'],
                ],
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseMissing('expenses', [
            'home_id' => $home->id,
            'amount' => '456789.00',
        ]);

        $saveCallback = null;
        foreach (Http::recorded() as [$request]) {
            if (! str_contains($request->url(), 'sendMessage')) {
                continue;
            }

            if (! str_contains($request['text'] ?? '', 'AI ĐÃ ĐỌC HÓA ĐƠN ĐIỆN')) {
                continue;
            }

            $saveCallback = $request['reply_markup']['inline_keyboard'][0][0]['callback_data'] ?? null;
            break;
        }

        $this->assertIsString($saveCallback);
        $this->assertStringStartsWith('r_save:', $saveCallback);

        $this->postJson('/api/v1/telegram/webhook', [
            'callback_query' => [
                'id' => 'receipt_save_cb',
                'message' => [
                    'chat' => [
                        'id' => 123456789,
                    ],
                    'message_id' => 778,
                    'text' => 'Preview hóa đơn',
                ],
                'data' => $saveCallback,
            ],
        ])->assertOk();

        $expense = Expense::where('home_id', $home->id)
            ->where('amount', '456789.00')
            ->first();

        $this->assertNotNull($expense);
        $this->assertSame($tech->id, $expense->wallet_id);

        $this->assertDatabaseHas('energy_bills', [
            'home_id' => $home->id,
            'expense_id' => $expense->id,
            'user_id' => $user->id,
            'provider' => 'EVN',
            'customer_code' => 'PE123456789',
            'billing_period' => '06/2026',
        ]);

        $energyBill = EnergyBill::where('expense_id', $expense->id)->first();
        $this->assertNotNull($energyBill);
        $this->assertEquals(158.0, $energyBill->kwh);
        $this->assertSame('2026-06-01', $energyBill->period_start->toDateString());
        $this->assertSame('2026-06-30', $energyBill->period_end->toDateString());
        $this->assertSame('456789.00', $energyBill->amount);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage') &&
                str_contains($request['text'], 'Đã lưu hóa đơn AI');
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
        EnergyBill::create([
            'home_id' => $tech->home_id,
            'expense_id' => $expense->id,
            'user_id' => $user->id,
            'billing_period' => '06/2026',
            'kwh' => 20,
            'amount' => 50000,
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
                'data' => 'undo_expense:'.$expense->id,
            ],
        ];

        $response = $this->postJson('/api/v1/telegram/webhook', $payload);

        $response->assertStatus(200);

        // Assert Expense was deleted/soft-deleted
        $this->assertSoftDeleted('expenses', [
            'id' => $expense->id,
        ]);

        $this->assertSoftDeleted('energy_bills', [
            'expense_id' => $expense->id,
        ]);

        // Assert balance was restored
        $tech->refresh();
        $this->assertEquals(1000000.0, (float) $tech->balance);

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

    public function test_telegram_callback_can_change_wallet_category_and_type(): void
    {
        config(['services.telegram.bot_token' => 'fake_token']);
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        ['user' => $user, 'home' => $home, 'techcombank' => $tech, 'vpbank' => $vp] = $this->setupUserWithWallets();
        $oldCategory = ExpenseCategory::where('home_id', $home->id)->where('type', 'expense')->first();
        $newCategory = ExpenseCategory::create([
            'home_id' => $home->id,
            'name' => 'Ăn uống',
            'type' => 'expense',
            'is_system' => true,
        ]);
        $incomeCategory = ExpenseCategory::create([
            'home_id' => $home->id,
            'name' => 'Thu nhập khác',
            'type' => 'income',
            'category_group' => ExpenseCategory::GROUP_OTHER,
            'is_system' => true,
        ]);

        $expense = app(ExpenseService::class)->createExpense([
            'home_id' => $home->id,
            'wallet_id' => $tech->id,
            'category_id' => $oldCategory->id,
            'type' => 'expense',
            'amount' => 50000,
            'description' => 'Cafe',
            'occurred_at' => now()->toDateTimeString(),
        ], $user);

        $this->postJson('/api/v1/telegram/webhook', [
            'callback_query' => [
                'id' => 'set_wallet_cb',
                'message' => [
                    'chat' => ['id' => 123456789],
                    'message_id' => 900,
                    'text' => 'Giao dịch',
                ],
                'data' => 'set_wallet:'.$expense->id.':'.$vp->id,
            ],
        ])->assertOk();

        $this->assertSame($vp->id, $expense->fresh()->wallet_id);

        $this->postJson('/api/v1/telegram/webhook', [
            'callback_query' => [
                'id' => 'set_cat_cb',
                'message' => [
                    'chat' => ['id' => 123456789],
                    'message_id' => 901,
                    'text' => 'Giao dịch',
                ],
                'data' => 'set_cat:'.$expense->id.':'.$newCategory->id,
            ],
        ])->assertOk();

        $this->assertSame($newCategory->id, $expense->fresh()->category_id);

        $this->postJson('/api/v1/telegram/webhook', [
            'callback_query' => [
                'id' => 'set_type_cb',
                'message' => [
                    'chat' => ['id' => 123456789],
                    'message_id' => 902,
                    'text' => 'Giao dịch',
                ],
                'data' => 'set_type:'.$expense->id.':income',
            ],
        ])->assertOk();

        $expense->refresh();
        $this->assertSame('income', $expense->type);
        $this->assertSame($incomeCategory->id, $expense->category_id);
    }

    public function test_telegram_callback_query_undo_transfer(): void
    {
        config(['services.telegram.bot_token' => 'fake_token']);
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        ['user' => $user, 'techcombank' => $tech, 'vpbank' => $vp] = $this->setupUserWithWallets();

        // Create a pre-existing transfer
        $transferService = app(TransferService::class);
        $transfer = $transferService->createTransfer([
            'home_id' => $tech->home_id,
            'from_wallet_id' => $tech->id,
            'to_wallet_id' => $vp->id,
            'amount' => 100000,
            'description' => 'Chuyển tiền',
        ], $user);

        $tech->refresh();
        $vp->refresh();
        $this->assertEquals(900000.0, (float) $tech->balance);
        $this->assertEquals(600000.0, (float) $vp->balance);

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
                'data' => 'undo_transfer:'.$transfer->id,
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
        $this->assertEquals(1000000.0, (float) $tech->balance);
        $this->assertEquals(500000.0, (float) $vp->balance);

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
                'data' => 'undo_expense:'.$expense->id,
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
