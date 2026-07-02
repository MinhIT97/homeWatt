<?php

namespace Modules\Expense\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\AI\Services\GeminiBillScanner;
use Modules\AI\Services\GeminiElectricBillScanner;
use Modules\Energy\Services\ElectricBillRecorder;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Expense\Models\Transfer;
use Modules\Expense\Services\ExpenseService;
use Modules\Expense\Services\QuickEntryService;
use Modules\Expense\Services\TelegramParserService;
use Modules\Expense\Services\TransferService;
use Modules\Wallet\Models\Wallet;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, TelegramParserService $parser, ExpenseService $expenseService): JsonResponse
    {
        $secret = config('services.telegram.webhook_secret');
        if ($secret && $request->header('X-Telegram-Bot-Api-Secret-Token') !== $secret) {
            abort(403, 'Invalid webhook token');
        }

        // Handle Callback Queries (Inline Button Clicks)
        $callbackQuery = $request->input('callback_query');
        if ($callbackQuery) {
            try {
                $this->handleCallbackQuery($callbackQuery, $expenseService);
            } catch (\Throwable $e) {
                Log::error('Telegram callback query handling failed', [
                    'error' => $e->getMessage(),
                    'callback_query_id' => $callbackQuery['id'] ?? null,
                ]);
            }

            return response()->json(['ok' => true]);
        }

        $chatId = $request->input('message.chat.id');
        $text = trim($request->input('message.text', ''));
        $photo = $request->input('message.photo');

        if (empty($chatId)) {
            return response()->json(['ok' => true]);
        }

        if (! empty($photo)) {
            try {
                $this->handlePhotoUpload($chatId, $photo, $expenseService);
            } catch (\Throwable $e) {
                Log::error('Telegram photo processing failed', [
                    'error' => $e->getMessage(),
                    'chat_id' => $chatId,
                ]);
                $this->sendMessage($chatId, '⚠️ Đã xảy ra lỗi khi quét hóa đơn.');
            }

            return response()->json(['ok' => true]);
        }

        if (empty($text)) {
            return response()->json(['ok' => true]);
        }

        try {
            // Handle /start commands for linking
            if (str_starts_with($text, '/start')) {
                $this->handleStartCommand($chatId, $text);

                return response()->json(['ok' => true]);
            }

            // Handle /help command
            $cleanTextLower = mb_strtolower($text, 'UTF-8');
            if ($cleanTextLower === '/help' || $cleanTextLower === 'help' || $cleanTextLower === '/trogiup' || $cleanTextLower === 'tro giup') {
                $this->handleHelpCommand($chatId);

                return response()->json(['ok' => true]);
            }

            if ($this->matchesCommand($cleanTextLower, ['/commands', 'commands', '/lenh', 'lenh', '/menu', 'menu'])) {
                $this->handleCommandListCommand($chatId);

                return response()->json(['ok' => true]);
            }

            // Handle /wallets or /vi command
            if ($cleanTextLower === '/wallets' || $cleanTextLower === '/vi' || $cleanTextLower === 'vi' || $cleanTextLower === '/balance' || $cleanTextLower === '/sodu' || $cleanTextLower === 'so du') {
                $this->handleWalletsCommand($chatId);

                return response()->json(['ok' => true]);
            }

            if ($this->matchesCommand($cleanTextLower, ['/today', 'today', '/homnay', 'hom nay', 'hôm nay', '/ngay', 'ngay'])) {
                $this->handleSummaryCommand($chatId, 'day');

                return response()->json(['ok' => true]);
            }

            if ($this->matchesCommand($cleanTextLower, ['/week', 'week', '/tuan', 'tuan', 'tuần này', 'tuan nay'])) {
                $this->handleSummaryCommand($chatId, 'week');

                return response()->json(['ok' => true]);
            }

            if ($this->matchesCommand($cleanTextLower, ['/month', 'month', '/thang', 'thang', 'tháng này', 'thang nay'])) {
                $this->handleSummaryCommand($chatId, 'month');

                return response()->json(['ok' => true]);
            }

            if (preg_match('/^(?:\/recent|recent|\/ganday|ganday|gan day|gần đây)(?:\s+(\d{1,2}))?$/u', $cleanTextLower, $matches)) {
                $this->handleRecentCommand($chatId, isset($matches[1]) ? (int) $matches[1] : 5);

                return response()->json(['ok' => true]);
            }

            if ($this->matchesCommand($cleanTextLower, ['/templates', 'templates', '/mau', 'mau', 'mẫu', '/goiy', 'goi y', 'gợi ý'])) {
                $this->handleTemplatesCommand($chatId);

                return response()->json(['ok' => true]);
            }

            if (! str_starts_with($text, '/') && $this->handlePendingTemplateAmount($chatId, $text)) {
                return response()->json(['ok' => true]);
            }

            if ($this->hasBatchTransactionLines($text)) {
                $this->handleBatchTransactionsCommand($chatId, $text, $parser, $expenseService);

                return response()->json(['ok' => true]);
            }

            // Handle general transaction input
            $this->handleTransactionCommand($chatId, $text, $parser, $expenseService);
        } catch (\Throwable $e) {
            Log::error('Telegram webhook handling failed', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
                'text' => $text,
            ]);
            $this->sendMessage($chatId, '⚠️ Đã xảy ra lỗi hệ thống khi xử lý yêu cầu của bạn.');
        }

        return response()->json(['ok' => true]);
    }

    private function handleStartCommand(int $chatId, string $text): void
    {
        preg_match('/\/start\s+([a-zA-Z0-9_-]+)/', $text, $matches);

        if (empty($matches)) {
            $msg = "👋 Chào mừng bạn đến với HomeWatt!\n\n"
                 ."Để kết nối tài khoản Telegram này với ứng dụng HomeWatt, vui lòng:\n"
                 ."1. Đăng nhập vào trang web HomeWatt.\n"
                 ."2. Đi tới trang Cá nhân (Profile).\n"
                 ."3. Nhấn 'Liên kết Telegram' để lấy mã kết nối và click vào link bot.";
            $this->sendMessage($chatId, $msg);

            return;
        }

        $code = trim($matches[1]);
        // Support prefixes like link_123456
        if (str_starts_with($code, 'link_')) {
            $code = substr($code, 5);
        }

        $user = User::where('telegram_verification_code', $code)->first();

        if (! $user) {
            $this->sendMessage($chatId, '❌ Mã liên kết không hợp lệ hoặc đã hết hạn.');

            return;
        }

        // Link Telegram account
        $user->forceFill([
            'telegram_chat_id' => $chatId,
            'telegram_verification_code' => null,
        ])->save();

        $msg = "🎉 Liên kết tài khoản thành công!\n\n"
             .'Tài khoản HomeWatt của bạn: *'.e($user->name).'* ('.e($user->email).")\n\n"
             ."Bây giờ bạn có thể nhập chi tiêu nhanh qua đây bất cứ lúc nào bằng cú pháp thông minh. Ví dụ:\n"
             ."• `chi 50k ăn sáng` (Mặc định ghi vào ví Tiền mặt)\n"
             ."• `chi 150k vcb ăn tối` (Ghi nhận vào ví Vietcombank/vcb)\n"
             ."• `chi 100k vpbank xăng xe` (Ghi nhận vào ví VPBank)\n"
             ."• `thu 2.5tr bán điện mặt trời`\n"
             ."• `cho Hường Nguyễn vay 35k techcombank` hoặc `cho vay 500k cho bạn Nam`\n"
             ."• `đi vay 1m từ anh Ba`\n\n"
             ."Gõ `/lenh` để mở menu lệnh nhanh, xem báo cáo hôm nay, giao dịch gần đây và mẫu nhập.\n\n"
             .'💡 *Mẹo:* Bạn chỉ cần gõ thêm tên ví hoặc tên viết tắt (như `vcb`, `tech`, `momo`, `tm`...) vào tin nhắn để hệ thống tự nhận diện đúng ví ghi nhận!';

        $this->sendMessage($chatId, $msg);
    }

    private function matchesCommand(string $text, array $commands): bool
    {
        return in_array(preg_replace('/\s+/u', ' ', trim($text)), $commands, true);
    }

    private function hasBatchTransactionLines(string $text): bool
    {
        $lines = $this->transactionLines($text);

        if (count($lines) < 2 || count($lines) > 10) {
            return false;
        }

        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '/')) {
                return false;
            }
        }

        return true;
    }

    private function transactionLines(string $text): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/\R/u', $text) ?: []),
            fn ($line) => $line !== ''
        ));
    }

    private function handleBatchTransactionsCommand(int $chatId, string $text, TelegramParserService $parser, ExpenseService $expenseService): void
    {
        $lines = $this->transactionLines($text);

        if (count($lines) > 10) {
            $this->sendMessage($chatId, '⚠️ Mỗi lần nhập nhiều dòng tối đa 10 giao dịch để tránh ghi nhầm. Bạn tách bớt rồi gửi lại nhé.');

            return;
        }

        $this->sendMessage($chatId, '🧾 Đang ghi '.count($lines).' dòng giao dịch...');

        $processed = 0;
        foreach ($lines as $line) {
            try {
                $this->handleTransactionCommand($chatId, $line, $parser, $expenseService);
                $processed++;
            } catch (\Throwable $e) {
                Log::warning('Telegram batch line failed', [
                    'chat_id' => $chatId,
                    'line' => $line,
                    'error' => $e->getMessage(),
                ]);
                $this->sendMessage($chatId, "⚠️ Không xử lý được dòng: `{$line}`");
            }
        }

        $this->sendMessage($chatId, "✅ Đã xử lý {$processed}/".count($lines).' dòng. Mỗi giao dịch đều có nút hoàn tác riêng ở tin xác nhận.');
    }

    private function handleTransactionCommand(int $chatId, string $text, TelegramParserService $parser, ExpenseService $expenseService): void
    {
        $user = User::where('telegram_chat_id', $chatId)->first();

        if (! $user) {
            $msg = "❌ Tài khoản Telegram của bạn chưa được liên kết với HomeWatt.\n\n"
                 .'Vui lòng đăng nhập vào trang web, vào trang Cá nhân và lấy mã để liên kết tài khoản.';
            $this->sendMessage($chatId, $msg);

            return;
        }

        $memberships = $user->homeMembers()->with('home')->get();

        if ($memberships->isEmpty()) {
            $this->sendMessage($chatId, '❌ Bạn chưa tham gia vào bất kỳ ngôi nhà nào trên HomeWatt. Vui lòng tạo hoặc tham gia nhà trước.');

            return;
        }

        // Gather wallets from ALL homes for multi-home users
        $homeIds = $memberships->pluck('home_id');
        $allWallets = Wallet::whereIn('home_id', $homeIds)
            ->where('is_archived', false)
            ->get()
            ->sortByDesc(fn ($w) => mb_strlen($w->name, 'UTF-8'))
            ->values();

        if ($allWallets->isEmpty()) {
            $this->sendMessage($chatId, '❌ Ngôi nhà của bạn chưa có ví tiền nào để ghi nhận giao dịch. Vui lòng tạo ví trên website.');

            return;
        }

        $dateExtraction = $this->extractOccurredAt($text);
        $text = $dateExtraction['text'];
        $occurredAt = $dateExtraction['occurred_at'];

        // 1. Match wallets across ALL homes
        $extracted = $this->extractWallets($text, $allWallets);
        $modifiedText = $extracted['text'];
        $matchedWallets = $extracted['matched_wallets'];

        // Determine active home
        if (count($matchedWallets) > 0) {
            $selectedHome = $memberships->firstWhere('home_id', $matchedWallets[0]->home_id)?->home;
        } else {
            $selectedHome = $memberships->first()->home;
        }

        $home = $selectedHome;

        // Parse command text
        $parsed = $parser->parse($modifiedText, $home->id);

        if (! $parsed) {
            $msg = "❓ Cú pháp không hợp lệ. Vui lòng nhập theo các ví dụ sau:\n\n"
                 ."• *Chi tiêu*: `chi 75k mua rau quả` hoặc `tieu 200k vcb xang xe`\n"
                 ."• *Thu nhập*: `thu 12tr luong thang` hoặc `thu 500k momo bán đồ cũ`\n"
                 ."• *Cho vay*: `cho Hường Nguyễn vay 35k techcombank` hoặc `cho vay 200k cho bạn`\n"
                 ."• *Đi vay*: `vay 1tr mua đồ ăn`\n"
                 ."• *Trả nợ*: `trả nợ 100k`\n"
                 ."• *Thu nợ*: `thu nợ 300k từ Nam`\n"
                 ."• *Chuyển khoản*: `chuyển 80k từ techcombank sang vpbank` hoặc `ck 50k sang momo`\n\n"
                 .'💡 *Lưu ý:* Hệ thống tự động ghi nhận vào ví đúng nếu bạn ghi tên ví hoặc tên viết tắt (như vcb, tech, momo, tm) trong nội dung tin nhắn.';
            $this->sendMessage($chatId, $msg);

            return;
        }

        // Check if it's a transfer
        if ($parsed['type'] === 'transfer') {
            $fromWallet = null;
            $toWallet = null;
            $modifiedTextLower = mb_strtolower($modifiedText, 'UTF-8');

            if (count($matchedWallets) >= 2) {
                if (preg_match('/\{wallet_(\d+)\}\s*(?:sang|đến|den|qua|vào|vao|->)\s*\{wallet_(\d+)\}/i', $modifiedTextLower, $matches)) {
                    $fromWallet = $matchedWallets[(int) $matches[1]] ?? null;
                    $toWallet = $matchedWallets[(int) $matches[2]] ?? null;
                } elseif (preg_match('/(?:sang|đến|den|qua|vào|vao|->)\s*\{wallet_(\d+)\}\s*(?:từ|tu)\s*\{wallet_(\d+)\}/i', $modifiedTextLower, $matches)) {
                    $toWallet = $matchedWallets[(int) $matches[1]] ?? null;
                    $fromWallet = $matchedWallets[(int) $matches[2]] ?? null;
                } else {
                    $fromWallet = $matchedWallets[0] ?? null;
                    $toWallet = $matchedWallets[1] ?? null;
                }
            } elseif (count($matchedWallets) === 1) {
                $wallet = $matchedWallets[0];
                if (preg_match('/(?:sang|đến|den|qua|vào|vao|->)\s*\{wallet_0\}/i', $modifiedTextLower)) {
                    $toWallet = $wallet;
                } elseif (preg_match('/(?:từ|tu)\s*\{wallet_0\}/i', $modifiedTextLower)) {
                    $fromWallet = $wallet;
                } else {
                    $toWallet = $wallet;
                }
            }

            // Fill default wallets
            $homeWallets = $allWallets->where('home_id', $home->id);
            $defaultWallet = $homeWallets->first(fn ($w) => str_contains(mb_strtolower($w->name, 'UTF-8'), 'tiền mặt'))
                ?: $homeWallets->first(fn ($w) => str_contains(mb_strtolower($w->name, 'UTF-8'), 'chính'))
                ?: $homeWallets->first();

            if (! $fromWallet) {
                $fromWallet = $defaultWallet;
            }
            if (! $toWallet) {
                $toWallet = $homeWallets->first(fn ($w) => $w->id !== $fromWallet->id) ?: $defaultWallet;
            }

            if ($fromWallet->id === $toWallet->id) {
                $this->sendMessage($chatId, "❌ Không thể chuyển tiền đến cùng một ví ({$fromWallet->name}). Vui lòng nhập lại ví nguồn và ví đích khác nhau.");

                return;
            }

            $transferItem = [
                'mode' => 'transfer',
                'home_id' => $home->id,
                'from_wallet_id' => $fromWallet->id,
                'to_wallet_id' => $toWallet->id,
                'amount' => $parsed['amount'],
                'fee' => 0,
                'description' => $parsed['description'],
                'occurred_at' => $occurredAt->toDateTimeString(),
            ];

            if ($this->sendDuplicateConfirmationIfNeeded($chatId, $user, $transferItem)) {
                return;
            }

            try {
                $transferService = app(TransferService::class);
                $transfer = $transferService->createTransfer([
                    'home_id' => $home->id,
                    'from_wallet_id' => $fromWallet->id,
                    'to_wallet_id' => $toWallet->id,
                    'amount' => $parsed['amount'],
                    'description' => $parsed['description'],
                    'occurred_at' => $occurredAt->toDateTimeString(),
                ], $user);

                $confirmMsg = "✅ *Chuyển khoản thành công!*\n\n"
                            .'*Số tiền*: '.number_format($parsed['amount'], 0, ',', '.')." đ\n"
                            .'*Từ ví*: '.$fromWallet->name.' (Số dư: '.number_format((float) $fromWallet->fresh()->calculatedBalance(), 0, ',', '.')." đ)\n"
                            .'*Sang ví*: '.$toWallet->name.' (Số dư: '.number_format((float) $toWallet->fresh()->calculatedBalance(), 0, ',', '.')." đ)\n"
                            .'*Ghi chú*: '.$parsed['description'];

                $replyMarkup = [
                    'inline_keyboard' => [
                        [
                            ['text' => 'Đổi ví nguồn', 'callback_data' => 'change_tr_from:'.$transfer->id],
                            ['text' => 'Đổi ví nhận', 'callback_data' => 'change_tr_to:'.$transfer->id],
                        ],
                        [
                            ['text' => '↩️ Hoàn tác', 'callback_data' => 'undo_transfer:'.$transfer->id],
                            ['text' => '💳 Số dư ví', 'callback_data' => 'view_wallets'],
                        ],
                        [
                            ['text' => '📊 Hôm nay', 'callback_data' => 'cmd_today'],
                        ],
                    ],
                ];

                $this->sendMessage($chatId, $confirmMsg, $replyMarkup);
            } catch (\Throwable $e) {
                $this->sendMessage($chatId, '❌ Lỗi: '.$e->getMessage());
            }

            return;
        }

        // 2. Normal income/expense
        $selectedWallet = count($matchedWallets) > 0 ? $matchedWallets[0] : null;

        if (! $selectedWallet) {
            $homeWallets = $allWallets->where('home_id', $home->id);
            $selectedWallet = $homeWallets->first(fn ($w) => str_contains(mb_strtolower($w->name, 'UTF-8'), 'tiền mặt'))
                ?: $homeWallets->first(fn ($w) => str_contains(mb_strtolower($w->name, 'UTF-8'), 'chính'))
                ?: $homeWallets->first();

            // Notify multi-home users which home is being used
            if ($memberships->count() > 1) {
                $this->sendMessage($chatId, "ℹ️ Đang ghi giao dịch vào nhà *{$home->name}*.\nĐể chọn nhà khác, hãy thêm tên ví thuộc nhà đó vào tin nhắn.");
            }
        }

        // Add default values to payload for ExpenseService
        $payload = [
            'home_id' => $home->id,
            'wallet_id' => $selectedWallet->id,
            'category_id' => $parsed['category_id'],
            'amount' => $parsed['amount'],
            'type' => $parsed['type'],
            'description' => $parsed['description'],
            'occurred_at' => $occurredAt->toDateTimeString(),
        ];

        if ($this->sendDuplicateConfirmationIfNeeded($chatId, $user, ['mode' => 'transaction', ...$payload])) {
            return;
        }

        $expense = $expenseService->createExpense($payload, $user);

        // Success Confirmation Message
        $typeEmoji = match ($parsed['category_group'] ?? null) {
            ExpenseCategory::GROUP_LENDING => '🤝 CHO VAY',
            ExpenseCategory::GROUP_BORROWING => '🏦 ĐI VAY',
            ExpenseCategory::GROUP_DEBT_COLLECTION => '🪙 THU NỢ',
            ExpenseCategory::GROUP_DEBT_REPAYMENT => '💸 TRẢ NỢ',
            default => $parsed['type'] === 'income' ? '🟢 THU NHẬP' : '🔴 CHI TIÊU',
        };
        $counterpartyLine = ($parsed['category_group'] ?? null) === ExpenseCategory::GROUP_LENDING && ! empty($parsed['counterparty'])
            ? '*Người vay*: '.$parsed['counterparty']."\n"
            : '';
        $confirmMsg = "✅ *Ghi nhận thành công!*\n\n"
                    .'*Loại*: '.$typeEmoji."\n"
                    .'*Số tiền*: '.number_format($parsed['amount'], 0, ',', '.')." đ\n"
                    .'*Danh mục*: '.$parsed['category_name']."\n"
                    .$counterpartyLine
                    .'*Ghi chú*: '.$parsed['description']."\n"
                    .'*Ví*: '.$selectedWallet->name.' (Số dư: '.number_format((float) $selectedWallet->fresh()->calculatedBalance(), 0, ',', '.').' đ)';

        $replyMarkup = [
            'inline_keyboard' => [
                [
                    ['text' => 'Đổi ví', 'callback_data' => 'change_wallet:'.$expense->id],
                    ['text' => 'Đổi danh mục', 'callback_data' => 'change_category:'.$expense->id],
                ],
                [
                    ['text' => 'Đổi loại', 'callback_data' => 'change_type:'.$expense->id],
                    ['text' => '↩️ Hoàn tác', 'callback_data' => 'undo_expense:'.$expense->id],
                ],
                [
                    ['text' => '💳 Số dư ví', 'callback_data' => 'view_wallets'],
                    ['text' => '📊 Hôm nay', 'callback_data' => 'cmd_today'],
                ],
            ],
        ];

        $this->sendMessage($chatId, $confirmMsg, $replyMarkup);
    }

    private function extractOccurredAt(string $text): array
    {
        $occurredAt = now();
        $cleanText = $text;

        if (preg_match('/(?:^|\s)(hôm qua|hom qua)(?:\s|$)/iu', $cleanText, $matches)) {
            $occurredAt = now()->subDay();
            $cleanText = trim(str_replace($matches[0], ' ', $cleanText));
        } elseif (preg_match('/(?:ngày|ngay)\s+(\d{1,2})[\/.-](\d{1,2})(?:[\/.-](\d{2,4}))?/iu', $cleanText, $matches)) {
            $occurredAt = $this->buildOccurredAtFromDateParts((int) $matches[1], (int) $matches[2], $matches[3] ?? null);
            $cleanText = trim(str_replace($matches[0], ' ', $cleanText));
        } elseif (preg_match('/(?:^|\s)(\d{1,2})[\/.-](\d{1,2})(?:[\/.-](\d{2,4}))?(?:\s|$)/u', $cleanText, $matches)) {
            $occurredAt = $this->buildOccurredAtFromDateParts((int) $matches[1], (int) $matches[2], $matches[3] ?? null);
            $cleanText = trim(str_replace($matches[0], ' ', $cleanText));
        }

        return [
            'text' => preg_replace('/\s{2,}/u', ' ', $cleanText) ?: $text,
            'occurred_at' => $occurredAt,
        ];
    }

    private function buildOccurredAtFromDateParts(int $day, int $month, ?string $year): Carbon
    {
        $fullYear = $year ? (int) $year : now()->year;
        if ($fullYear < 100) {
            $fullYear += 2000;
        }

        try {
            $date = Carbon::create($fullYear, $month, $day, now()->hour, now()->minute, now()->second);
        } catch (\Throwable) {
            return now();
        }

        if (! $year && $date->isFuture()) {
            $date->subYear();
        }

        return $date->isFuture() ? now() : $date;
    }

    private function extractWallets(string $text, $allWallets): array
    {
        $cleanTextLower = mb_strtolower($text, 'UTF-8');
        $matchedWallets = [];
        $placeholderIndex = 0;

        // Generate candidates
        $candidates = [];
        foreach ($allWallets as $w) {
            $walletNameLower = mb_strtolower($w->name, 'UTF-8');
            $walletNameNoSpaces = str_replace(' ', '', $walletNameLower);

            $matchCandidates = [
                $w->name,
                $walletNameLower,
                $walletNameNoSpaces,
                'tài khoản '.$walletNameLower,
                'tài khoản '.$walletNameNoSpaces,
                'taikhoan '.$walletNameLower,
                'taikhoan '.$walletNameNoSpaces,
                'tk '.$walletNameLower,
                'tk '.$walletNameNoSpaces,
                'ví '.$walletNameLower,
                'ví '.$walletNameNoSpaces,
                'vi '.$walletNameLower,
                'vi '.$walletNameNoSpaces,
            ];

            if (str_contains($walletNameLower, 'techcombank')) {
                $matchCandidates[] = 'techcombank';
                $matchCandidates[] = 'tech';
                $matchCandidates[] = 'tcb';
                $matchCandidates[] = 'ví thấu chi tech';
                $matchCandidates[] = 'ví thấu chi techcombank';
                $matchCandidates[] = 'thấu chi techcombank';
                $matchCandidates[] = 'thấu chi tech';
                $matchCandidates[] = 'thấu chi tcb';
            }
            if (str_contains($walletNameLower, 'vietcombank')) {
                $matchCandidates[] = 'vietcombank';
                $matchCandidates[] = 'vcb';
            }
            if (str_contains($walletNameLower, 'momo')) {
                $matchCandidates[] = 'momo';
            }
            if (str_contains($walletNameLower, 'tiền mặt') || str_contains($walletNameLower, 'tien mat')) {
                $matchCandidates[] = 'tien mat';
                $matchCandidates[] = 'tiền mặt';
                $matchCandidates[] = 'tm';
            }
            if (str_contains($walletNameLower, 'vpbank') || str_contains($walletNameLower, 'vp bank')) {
                $matchCandidates[] = 'vpbank';
                $matchCandidates[] = 'vp bank';
                $matchCandidates[] = 'vp';
            }

            // De-duplicate candidates and filter out empty
            $matchCandidates = array_values(array_unique(array_filter($matchCandidates)));

            foreach ($matchCandidates as $candidate) {
                $candidates[] = [
                    'wallet' => $w,
                    'candidate' => mb_strtolower($candidate, 'UTF-8'),
                ];
            }
        }

        // Sort by candidate length descending
        usort($candidates, fn ($a, $b) => mb_strlen($b['candidate'], 'UTF-8') <=> mb_strlen($a['candidate'], 'UTF-8'));

        $modifiedOriginal = $text;

        foreach ($candidates as $item) {
            $candidateLower = $item['candidate'];

            // Check in lowercase text
            $modifiedLower = mb_strtolower($modifiedOriginal, 'UTF-8');
            if (($pos = mb_strpos($modifiedLower, $candidateLower, 0, 'UTF-8')) !== false) {
                $placeholder = "{wallet_{$placeholderIndex}}";
                $matchedWallets[$placeholderIndex] = $item['wallet'];

                // Replace in original text using multibyte functions
                $modifiedOriginal = mb_substr($modifiedOriginal, 0, $pos, 'UTF-8')
                    .$placeholder
                    .mb_substr($modifiedOriginal, $pos + mb_strlen($candidateLower, 'UTF-8'), null, 'UTF-8');

                $placeholderIndex++;
            }
        }

        return [
            'text' => $modifiedOriginal,
            'matched_wallets' => $matchedWallets,
        ];
    }

    private function handleHelpCommand(int $chatId): void
    {
        $msg = "ℹ️ *HƯỚNG DẪN SỬ DỤNG HOMEWATT BOT*\n\n"
             ."🤖 Bot giúp ghi chép nhanh giao dịch bằng cú pháp tiếng Việt thông minh.\n\n"
             ."🔑 *CÁC LỆNH HỆ THỐNG:*\n"
             ."• `/help` hoặc `help`: Hiển thị hướng dẫn này\n"
             ."• `/lenh` hoặc `menu`: Xem danh sách lệnh nhanh\n"
             ."• `/vi` hoặc `/wallets` hoặc `vi`: Xem danh sách các ví và số dư hiện tại\n\n"
             ."📝 *CÚ PHÁP GHI CHÉP GIAO DỊCH:*\n"
             ."Gõ theo định dạng: `[Hành động] [Số tiền] [Tên ví (nếu có)] [Mô tả/Hạng mục]`\n\n"
             ."• 🔴 *Chi tiêu*: `chi 75k mua rau` hoặc `tieu 200k vcb xang xe`\n"
             ."• 🟢 *Thu nhập*: `thu 12tr luong` hoặc `thu 500k momo ban do`\n"
             ."• 🤝 *Cho vay*: `cho Huong Nguyen vay 35k techcombank` hoặc `cho vay 200k cho ban`\n"
             ."• 💸 *Trả nợ*: `tra no 100k`\n"
             ."• 🏦 *Đi vay*: `vay 1tr mua do`\n"
             ."• 🪙 *Thu nợ*: `thu no 300k tu Nam`\n"
             ."• 🧾 *Nhiều dòng*: gửi nhiều giao dịch, mỗi dòng một khoản\n"
             ."• 📅 *Ngày khác*: `hôm qua chi 50k ăn trưa` hoặc `01/07 chi 80k cafe`\n\n"
             .'💡 *Mẹo nhận diện ví:* Thêm tên ví hoặc viết tắt của ví (như `vcb`, `tech`, `momo`, `tm`) để hệ thống tự khớp. Mặc định sẽ ghi vào ví *Tiền mặt* nếu không ghi tên ví.';

        $this->sendMessage($chatId, $msg);
    }

    private function handleCommandListCommand(int $chatId): void
    {
        $msg = "⚡ *LỆNH NHANH HOMEWATT BOT*\n\n"
             ."*Ghi giao dịch:*\n"
             ."• `35k cafe tm`\n"
             ."• `chi 75k ăn sáng`\n"
             ."• `thu 12tr lương`\n"
             ."• `chuyển 500k từ momo sang vcb`\n"
             ."• `cho Hường Nguyễn vay 35k techcombank`\n\n"
             ."*Báo cáo nhanh:*\n"
             ."• `/today` hoặc `hôm nay`: Tóm tắt hôm nay\n"
             ."• `/week` hoặc `tuần này`: Tóm tắt tuần này\n"
             ."• `/month` hoặc `tháng này`: Tóm tắt tháng này\n"
             ."• `/recent 5`: 5 giao dịch gần nhất\n"
             ."• `/vi`: Số dư ví\n"
             ."• `/mau`: Mẫu nhập nhanh\n\n"
             ."*Nhập nhiều dòng:*\n"
             ."```\n"
             ."cafe 35k tm\n"
             ."ăn trưa 60k\n"
             ."gửi xe 5k\n"
             .'```';

        $replyMarkup = [
            'inline_keyboard' => [
                [
                    ['text' => '📊 Hôm nay', 'callback_data' => 'cmd_today'],
                    ['text' => '💳 Ví', 'callback_data' => 'view_wallets'],
                ],
                [
                    ['text' => '🧾 Gần đây', 'callback_data' => 'cmd_recent'],
                    ['text' => '✨ Mẫu nhập', 'callback_data' => 'cmd_templates'],
                ],
            ],
        ];

        $this->sendMessage($chatId, $msg, $replyMarkup);
    }

    private function handleWalletsCommand(int $chatId): void
    {
        $user = User::where('telegram_chat_id', $chatId)->first();

        if (! $user) {
            $msg = "❌ Tài khoản Telegram của bạn chưa được liên kết với HomeWatt.\n\n"
                 .'Vui lòng đăng nhập vào trang web, vào trang Cá nhân và lấy mã để liên kết tài khoản.';
            $this->sendMessage($chatId, $msg);

            return;
        }

        $memberships = $user->homeMembers()->with('home')->get();

        if ($memberships->isEmpty()) {
            $this->sendMessage($chatId, '❌ Bạn chưa tham gia vào bất kỳ ngôi nhà nào trên HomeWatt.');

            return;
        }

        $homeIds = $memberships->pluck('home_id');
        $wallets = Wallet::whereIn('home_id', $homeIds)->where('is_archived', false)->get();

        if ($wallets->isEmpty()) {
            $this->sendMessage($chatId, '❌ Bạn chưa tạo ví tiền nào. Vui lòng tạo ví trên website.');

            return;
        }

        $msg = "💰 *DANH SÁCH VÍ & SỐ DƯ HIỆN TẠI:*\n\n";

        $grouped = $wallets->groupBy('home_id');
        foreach ($grouped as $homeId => $homeWallets) {
            $homeName = $memberships->firstWhere('home_id', $homeId)?->home?->name ?? 'Nhà';
            $msg .= "🏠 *{$homeName}:*\n";
            foreach ($homeWallets as $w) {
                $typeEmoji = ($w->type === Wallet::TYPE_CREDIT_CARD || $w->type === Wallet::TYPE_OVERDRAFT) ? '💳' : ($w->type === Wallet::TYPE_BANK ? '🏦' : '💵');
                $balanceStr = number_format($w->calculatedBalance(), 0, ',', '.').' '.$w->currency;

                if ($w->type === Wallet::TYPE_CREDIT_CARD) {
                    $debt = (float) $w->opening_balance - $w->calculatedBalance();
                    $msg .= "  • {$typeEmoji} *{$w->name}*: Hạn mức ".number_format($w->opening_balance, 0, ',', '.').' | Đang nợ: '.number_format($debt, 0, ',', '.')." {$w->currency}\n";
                } elseif ($w->type === Wallet::TYPE_OVERDRAFT) {
                    $debt = (float) $w->opening_balance - $w->calculatedBalance();
                    $msg .= "  • {$typeEmoji} *{$w->name}*: Hạn mức thấu chi ".number_format($w->opening_balance, 0, ',', '.').' | Đang nợ: '.number_format($debt, 0, ',', '.')." {$w->currency}\n";
                } else {
                    $msg .= "  • {$typeEmoji} *{$w->name}*: {$balanceStr}\n";
                }
            }
            $msg .= "\n";
        }

        $this->sendMessage($chatId, trim($msg));
    }

    private function handleTemplatesCommand(int $chatId): void
    {
        $context = $this->telegramContext($chatId);
        if ($context) {
            ['user' => $user, 'memberships' => $memberships] = $context;
            $home = $memberships->first()?->home;

            if ($home) {
                try {
                    $templates = app(QuickEntryService::class)->templatesForHome($user, (int) $home->id);
                    if ($templates !== []) {
                        $keyboard = collect($templates)
                            ->chunk(2)
                            ->map(fn ($row) => $row->map(fn ($template) => [
                                'text' => ($template['icon'] ?? '✨').' '.$template['name'],
                                'callback_data' => 'tpl:'.$template['id'],
                            ])->values()->all())
                            ->values()
                            ->all();

                        $this->sendMessage($chatId, "✨ *MẪU GIAO DỊCH THƯỜNG DÙNG*\n\nBấm một mẫu rồi nhập số tiền, ví dụ `35k` hoặc `1.2tr`.", [
                            'inline_keyboard' => $keyboard,
                        ]);

                        return;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Telegram templates load failed', [
                        'chat_id' => $chatId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $msg = "✨ *MẪU NHẬP NHANH*\n\n"
             ."*Chi tiêu thường ngày:*\n"
             ."• `35k cafe tm`\n"
             ."• `ăn trưa 60k vpbank`\n"
             ."• `chi 120k đi chợ tiền mặt`\n"
             ."• `hôm qua chi 45k gửi xe`\n\n"
             ."*Thu nhập:*\n"
             ."• `thu 12tr lương techcombank`\n"
             ."• `thu 500k bán đồ momo`\n\n"
             ."*Vay nợ:*\n"
             ."• `cho Hường Nguyễn vay 35k techcombank`\n"
             ."• `thu nợ 300k từ Nam`\n"
             ."• `trả nợ 1tr ngân hàng`\n"
             ."• `vay 2tr từ anh Ba`\n\n"
             ."*Chuyển ví:*\n"
             ."• `chuyển 500k từ momo sang vcb`\n"
             ."• `ck 80k từ tech sang vpbank`\n\n"
             ."*Nhập nhiều dòng một lần:*\n"
             ."```\n"
             ."cafe 35k tm\n"
             ."ăn trưa 60k\n"
             ."gửi xe 5k\n"
             .'```';

        $this->sendMessage($chatId, $msg);
    }

    private function handleSummaryCommand(int $chatId, string $period): void
    {
        $context = $this->telegramContext($chatId);
        if (! $context) {
            return;
        }

        ['user' => $user, 'memberships' => $memberships] = $context;
        $homeIds = $memberships->pluck('home_id');

        [$start, $end, $title] = match ($period) {
            'week' => [now()->startOfWeek()->startOfDay(), now()->endOfWeek()->endOfDay(), 'TUẦN NÀY'],
            'month' => [now()->startOfMonth()->startOfDay(), now()->endOfMonth()->endOfDay(), 'THÁNG NÀY'],
            default => [now()->startOfDay(), now()->endOfDay(), 'HÔM NAY'],
        };

        $debtCategoryIds = ExpenseCategory::whereIn('home_id', $homeIds)
            ->whereIn('category_group', ExpenseCategory::DEBT_GROUPS)
            ->pluck('id');

        $baseExpenseQuery = Expense::whereIn('home_id', $homeIds)
            ->whereNull('transfer_id')
            ->whereBetween('occurred_at', [$start, $end]);

        $income = (float) (clone $baseExpenseQuery)
            ->where('type', Expense::TYPE_INCOME)
            ->whereNotIn('category_id', $debtCategoryIds)
            ->sum('amount');

        $expense = (float) (clone $baseExpenseQuery)
            ->where('type', Expense::TYPE_EXPENSE)
            ->whereNotIn('category_id', $debtCategoryIds)
            ->sum('amount');

        $debtIn = (float) (clone $baseExpenseQuery)
            ->where('type', Expense::TYPE_INCOME)
            ->whereIn('category_id', $debtCategoryIds)
            ->sum('amount');

        $debtOut = (float) (clone $baseExpenseQuery)
            ->where('type', Expense::TYPE_EXPENSE)
            ->whereIn('category_id', $debtCategoryIds)
            ->sum('amount');

        $transferAmount = (float) Transfer::whereIn('home_id', $homeIds)
            ->whereBetween('occurred_at', [$start, $end])
            ->sum('amount');

        $wallets = Wallet::whereIn('home_id', $homeIds)->where('is_archived', false)->get();
        $totalBalance = (float) $wallets->sum(fn ($wallet) => $wallet->netBalance());

        $recent = (clone $baseExpenseQuery)
            ->with(['wallet', 'category'])
            ->latest('occurred_at')
            ->take(5)
            ->get();

        $msg = "📊 *TÓM TẮT {$title}*\n"
             .'📅 _'.$start->format('d/m/Y').($period === 'day' ? '' : ' - '.$end->format('d/m/Y'))."_\n\n"
             .'• 🟢 Thu nhập thật: *'.$this->money($income)." đ*\n"
             .'• 🔴 Chi tiêu thật: *'.$this->money($expense)." đ*\n"
             .'• ⚖️ Chênh lệch: *'.($income - $expense >= 0 ? '+' : '').$this->money($income - $expense)." đ*\n"
             .'• 🤝 Vay/nợ vào: *'.$this->money($debtIn)." đ*\n"
             .'• 💸 Vay/nợ ra: *'.$this->money($debtOut)." đ*\n"
             .'• 🔁 Chuyển ví: *'.$this->money($transferAmount)." đ*\n"
             .'• 💳 Tổng số dư ròng: *'.$this->money($totalBalance)." đ*\n";

        if ($recent->isNotEmpty()) {
            $msg .= "\n🧾 *Giao dịch gần nhất:*\n";
            foreach ($recent as $item) {
                $sign = $item->type === Expense::TYPE_INCOME ? '+' : '-';
                $msg .= '• '.$item->occurred_at?->format('H:i').' '.$sign.$this->money((float) $item->amount).' - '.$this->plain($item->description ?: $item->category?->name ?: 'Giao dịch').' ('.$this->plain($item->wallet?->name ?: 'Ví').")\n";
            }
        }

        $replyMarkup = [
            'inline_keyboard' => [
                [
                    ['text' => '🧾 Gần đây', 'callback_data' => 'cmd_recent'],
                    ['text' => '💳 Số dư ví', 'callback_data' => 'view_wallets'],
                ],
            ],
        ];

        $this->sendMessage($chatId, trim($msg), $replyMarkup);
    }

    private function handleRecentCommand(int $chatId, int $limit = 5): void
    {
        $context = $this->telegramContext($chatId);
        if (! $context) {
            return;
        }

        ['memberships' => $memberships] = $context;
        $homeIds = $memberships->pluck('home_id');
        $limit = max(1, min($limit, 10));

        $expenses = Expense::whereIn('home_id', $homeIds)
            ->whereNull('transfer_id')
            ->with(['wallet', 'category'])
            ->latest('occurred_at')
            ->take($limit)
            ->get()
            ->map(fn ($expense) => [
                'occurred_at' => $expense->occurred_at,
                'line' => ($expense->type === Expense::TYPE_INCOME ? '🟢 +' : '🔴 -')
                    .$this->money((float) $expense->amount).' đ - '
                    .$this->plain($expense->description ?: $expense->category?->name ?: 'Giao dịch')
                    .' · '.$this->plain($expense->wallet?->name ?: 'Ví'),
            ]);

        $transfers = Transfer::whereIn('home_id', $homeIds)
            ->with(['fromWallet', 'toWallet'])
            ->latest('occurred_at')
            ->take($limit)
            ->get()
            ->map(fn ($transfer) => [
                'occurred_at' => $transfer->occurred_at,
                'line' => '🔁 '.$this->money((float) $transfer->amount).' đ - '
                    .$this->plain($transfer->fromWallet?->name ?: 'Ví nguồn')
                    .' → '.$this->plain($transfer->toWallet?->name ?: 'Ví nhận'),
            ]);

        $items = $expenses->concat($transfers)
            ->sortByDesc('occurred_at')
            ->take($limit)
            ->values();

        if ($items->isEmpty()) {
            $this->sendMessage($chatId, '🧾 Chưa có giao dịch nào gần đây.');

            return;
        }

        $msg = "🧾 *{$limit} GIAO DỊCH GẦN ĐÂY*\n\n";
        foreach ($items as $item) {
            $msg .= '• '.$item['occurred_at']?->format('d/m H:i').' '.$item['line']."\n";
        }

        $this->sendMessage($chatId, trim($msg));
    }

    private function telegramContext(int $chatId): ?array
    {
        $user = User::where('telegram_chat_id', $chatId)->first();

        if (! $user) {
            $this->sendMessage($chatId, "❌ Tài khoản Telegram của bạn chưa được liên kết với HomeWatt.\n\nVui lòng đăng nhập vào trang web, vào trang Cá nhân và lấy mã để liên kết tài khoản.");

            return null;
        }

        $memberships = $user->homeMembers()->with('home')->get();

        if ($memberships->isEmpty()) {
            $this->sendMessage($chatId, '❌ Bạn chưa tham gia vào bất kỳ ngôi nhà nào trên HomeWatt.');

            return null;
        }

        return compact('user', 'memberships');
    }

    private function money(float $amount): string
    {
        return number_format($amount, 0, ',', '.');
    }

    private function plain(?string $text): string
    {
        return str_replace(['*', '_', '`', '[', ']'], '', (string) $text);
    }

    private function sendDuplicateConfirmationIfNeeded(int $chatId, User $user, array $item): bool
    {
        $duplicate = app(QuickEntryService::class)->findDuplicate($item);
        if (! $duplicate) {
            return false;
        }

        $key = Str::random(10);
        Cache::put($this->pendingTransactionCacheKey($key), [
            'user_id' => $user->id,
            'item' => $item,
        ], now()->addMinutes(10));

        $amount = number_format((float) ($item['amount'] ?? 0), 0, ',', '.');
        $description = $this->plain($item['description'] ?? 'Giao dịch');
        $message = "⚠️ *Có thể bị trùng giao dịch*\n\n"
            ."• Số tiền: *{$amount} đ*\n"
            ."• Nội dung: *{$description}*\n"
            .'• Lý do: '.$duplicate['message']."\n\n"
            .'Bạn muốn lưu thêm giao dịch này không?';

        $this->sendMessage($chatId, $message, [
            'inline_keyboard' => [
                [
                    ['text' => 'Vẫn lưu', 'callback_data' => 'dup_save:'.$key],
                    ['text' => 'Bỏ qua', 'callback_data' => 'dup_cancel:'.$key],
                ],
            ],
        ]);

        return true;
    }

    private function pendingTransactionCacheKey(string $key): string
    {
        return 'telegram_pending_transaction:'.$key;
    }

    private function handlePendingTemplateAmount(int $chatId, string $text): bool
    {
        $templateId = Cache::get('telegram_template:'.$chatId);
        if (! $templateId) {
            return false;
        }

        $quickEntry = app(QuickEntryService::class);
        if ($quickEntry->parseAmount($text) <= 0) {
            $this->sendMessage($chatId, 'Nhập số tiền cho mẫu này nhé. Ví dụ: `35k`, `1.2tr`, `50000`.');

            return true;
        }

        $user = User::where('telegram_chat_id', $chatId)->first();
        if (! $user) {
            Cache::forget('telegram_template:'.$chatId);

            return false;
        }

        try {
            $preview = $quickEntry->previewTemplate($user, (int) $templateId, $text);
            $item = $preview['items'][0] ?? null;
            if (! $item) {
                return false;
            }

            if ($this->sendDuplicateConfirmationIfNeeded($chatId, $user, $item)) {
                Cache::forget('telegram_template:'.$chatId);

                return true;
            }

            $result = $quickEntry->storeItem($user, $item, true);
            Cache::forget('telegram_template:'.$chatId);
            $this->sendStoredQuickItemConfirmation($chatId, $result);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Telegram template amount failed', [
                'chat_id' => $chatId,
                'template_id' => $templateId,
                'error' => $e->getMessage(),
            ]);
            $this->sendMessage($chatId, '❌ Không lưu được mẫu này: '.$e->getMessage());
            Cache::forget('telegram_template:'.$chatId);

            return true;
        }
    }

    private function sendStoredQuickItemConfirmation(int $chatId, array $result): void
    {
        if (($result['mode'] ?? null) === 'transfer') {
            $transfer = Transfer::with(['fromWallet', 'toWallet'])->find($result['id'] ?? null);
            if (! $transfer) {
                return;
            }

            $msg = "✅ *Chuyển khoản thành công!*\n\n"
                .'*Số tiền*: '.number_format((float) $transfer->amount, 0, ',', '.')." đ\n"
                .'*Từ ví*: '.$transfer->fromWallet?->name."\n"
                .'*Sang ví*: '.$transfer->toWallet?->name."\n"
                .'*Ghi chú*: '.$this->plain($transfer->description ?: 'Chuyển ví');

            $this->sendMessage($chatId, $msg, $this->transferActionKeyboard($transfer));

            return;
        }

        $expense = Expense::with(['wallet', 'category'])->find($result['id'] ?? null);
        if (! $expense) {
            return;
        }

        $msg = "✅ *Ghi nhận thành công!*\n\n"
            .'*Loại*: '.($expense->isIncome() ? '🟢 THU NHẬP' : '🔴 CHI TIÊU')."\n"
            .'*Số tiền*: '.number_format((float) $expense->amount, 0, ',', '.')." đ\n"
            .'*Danh mục*: '.$expense->category?->name."\n"
            .'*Ghi chú*: '.$this->plain($expense->description)."\n"
            .'*Ví*: '.$expense->wallet?->name;

        $this->sendMessage($chatId, $msg, $this->expenseActionKeyboard($expense));
    }

    private function expenseActionKeyboard(Expense $expense): array
    {
        return [
            'inline_keyboard' => [
                [
                    ['text' => 'Đổi ví', 'callback_data' => 'change_wallet:'.$expense->id],
                    ['text' => 'Đổi danh mục', 'callback_data' => 'change_category:'.$expense->id],
                ],
                [
                    ['text' => 'Đổi loại', 'callback_data' => 'change_type:'.$expense->id],
                    ['text' => '↩️ Hoàn tác', 'callback_data' => 'undo_expense:'.$expense->id],
                ],
                [
                    ['text' => '💳 Số dư ví', 'callback_data' => 'view_wallets'],
                    ['text' => '📊 Hôm nay', 'callback_data' => 'cmd_today'],
                ],
            ],
        ];
    }

    private function transferActionKeyboard(Transfer $transfer): array
    {
        return [
            'inline_keyboard' => [
                [
                    ['text' => 'Đổi ví nguồn', 'callback_data' => 'change_tr_from:'.$transfer->id],
                    ['text' => 'Đổi ví nhận', 'callback_data' => 'change_tr_to:'.$transfer->id],
                ],
                [
                    ['text' => '↩️ Hoàn tác', 'callback_data' => 'undo_transfer:'.$transfer->id],
                    ['text' => '💳 Số dư ví', 'callback_data' => 'view_wallets'],
                ],
                [
                    ['text' => '📊 Hôm nay', 'callback_data' => 'cmd_today'],
                ],
            ],
        ];
    }

    private function handlePhotoUpload(int $chatId, array $photo, ExpenseService $expenseService): void
    {
        $token = config('services.telegram.bot_token');
        if (empty($token)) {
            $this->sendMessage($chatId, '⚠️ Token Telegram Bot chưa được cấu hình.');

            return;
        }

        $user = User::where('telegram_chat_id', $chatId)->first();
        if (! $user) {
            $this->sendMessage($chatId, '❌ Tài khoản Telegram của bạn chưa được liên kết với HomeWatt.');

            return;
        }

        $memberships = $user->homeMembers()->with('home')->get();
        if ($memberships->isEmpty()) {
            $this->sendMessage($chatId, '❌ Bạn chưa tham gia vào ngôi nhà nào.');

            return;
        }

        $home = $memberships->first()->home;

        // Find wallets
        $wallet = Wallet::where('home_id', $home->id)
            ->where('is_archived', false)
            ->get()
            ->first(fn ($w) => str_contains(mb_strtolower($w->name, 'UTF-8'), 'tiền mặt'))
            ?: Wallet::where('home_id', $home->id)
                ->where('is_archived', false)
                ->first();

        if (! $wallet) {
            $this->sendMessage($chatId, '❌ Ngôi nhà của bạn chưa có ví tiền nào để ghi nhận giao dịch.');

            return;
        }

        $this->sendMessage($chatId, '🤖 AI đang quét hóa đơn của bạn, vui lòng đợi trong giây lát...');

        // Get the largest photo
        $largestPhoto = end($photo);
        $fileId = $largestPhoto['file_id'];

        $fileResponse = Http::get("https://api.telegram.org/bot{$token}/getFile?file_id={$fileId}");
        if ($fileResponse->failed()) {
            $this->sendMessage($chatId, '❌ Không thể tải thông tin tệp từ Telegram.');

            return;
        }

        $filePath = $fileResponse->json('result.file_path');
        if (empty($filePath)) {
            $this->sendMessage($chatId, '❌ Không tìm thấy đường dẫn tệp ảnh.');

            return;
        }

        $imageResponse = Http::get("https://api.telegram.org/file/bot{$token}/{$filePath}");
        if ($imageResponse->failed()) {
            $this->sendMessage($chatId, '❌ Không thể tải dữ liệu ảnh từ Telegram.');

            return;
        }

        $base64 = base64_encode($imageResponse->body());

        // 1. Try scanning as an electricity bill first
        $electricScanner = app(GeminiElectricBillScanner::class);
        $electricResult = $electricScanner->scan($base64);

        if ($electricResult && $electricResult['is_electric_bill'] && ! empty($electricResult['amount'])) {
            $categories = ExpenseCategory::where('home_id', $home->id)
                ->where('type', 'expense')
                ->get();
            $selectedCategory = $categories->first(fn ($cat) => str_contains(mb_strtolower($cat->name, 'UTF-8'), 'điện'))
                ?: $categories->first(fn ($cat) => str_contains(mb_strtolower($cat->name, 'UTF-8'), 'hóa đơn'))
                ?: $categories->first(fn ($cat) => str_contains(mb_strtolower($cat->name, 'UTF-8'), 'sinh hoạt'))
                ?: $categories->first(fn ($cat) => $cat->type === 'expense');

            if (! $selectedCategory) {
                $this->sendMessage($chatId, '❌ Hệ thống chưa cấu hình danh mục chi tiêu nào.');

                return;
            }

            $payload = [
                'home_id' => $home->id,
                'wallet_id' => $wallet->id,
                'category_id' => $selectedCategory->id,
                'amount' => $electricResult['amount'],
                'type' => 'expense',
                'description' => 'Hóa đơn tiền điện '.($electricResult['billing_month'] ?: now()->format('m/Y')),
                'notes' => 'Chỉ số cũ: '.($electricResult['old_index'] ?? 'N/A')
                    .' | Chỉ số mới: '.($electricResult['new_index'] ?? 'N/A')
                    .' | Tiêu thụ: '.($electricResult['kwh'] ?? 'N/A').' kWh. '
                    .'Quét tự động qua Telegram.',
                'occurred_at' => now()->toDateTimeString(),
            ];

            $this->sendReceiptPreview($chatId, $user, $payload, [
                'scan_type' => 'electric',
                'electric_result' => $electricResult,
            ]);

            return;
        }

        // 2. Fallback to normal receipt scanner
        $scanner = app(GeminiBillScanner::class);
        $result = $scanner->scan($base64);

        if (! $result || empty($result['amount'])) {
            $this->sendMessage($chatId, '🤖 AI không thể quét được số tiền hoặc thông tin hóa đơn này. Vui lòng chụp ảnh phẳng, đủ sáng và rõ số tiền, hoặc nhập tay chi tiêu.');

            return;
        }

        // Match category
        $categories = ExpenseCategory::where('home_id', $home->id)
            ->where('type', 'expense')
            ->get();
        $aiCategoryKey = $result['category'];
        $mappings = [
            'eating' => ['ăn', 'uống', 'nhà hàng', 'food', 'cafe', 'cà phê'],
            'shopping' => ['mua', 'sắm', 'quần áo', 'điện tử', 'tạp hóa', 'siêu thị'],
            'living' => ['sinh hoạt', 'điện', 'nước', 'nhà', 'phí'],
        ];

        $selectedCategory = null;
        if (isset($mappings[$aiCategoryKey])) {
            foreach ($categories as $cat) {
                foreach ($mappings[$aiCategoryKey] as $kw) {
                    if (str_contains(mb_strtolower($cat->name, 'UTF-8'), $kw)) {
                        $selectedCategory = $cat;
                        break 2;
                    }
                }
            }
        }

        if (! $selectedCategory) {
            $selectedCategory = $categories->first();
        }

        if (! $selectedCategory) {
            $this->sendMessage($chatId, '❌ Hệ thống chưa cấu hình danh mục chi tiêu nào.');

            return;
        }

        // Create transaction
        $payload = [
            'home_id' => $home->id,
            'wallet_id' => $wallet->id,
            'category_id' => $selectedCategory->id,
            'amount' => $result['amount'],
            'type' => 'expense',
            'description' => $result['description'] ?: 'Quét hóa đơn AI',
            'notes' => $result['notes'] ?: 'Quét tự động qua Telegram',
            'occurred_at' => now()->toDateTimeString(),
        ];

        $this->sendReceiptPreview($chatId, $user, $payload, [
            'scan_type' => 'receipt',
            'ai_result' => $result,
        ]);
    }

    private function sendReceiptPreview(int $chatId, User $user, array $payload, array $meta, ?string $key = null): void
    {
        $key ??= Str::random(10);
        Cache::put($this->receiptCacheKey($key), [
            'user_id' => $user->id,
            'payload' => $payload,
            'meta' => $meta,
        ], now()->addMinutes(30));

        $wallet = Wallet::find($payload['wallet_id']);
        $category = ExpenseCategory::find($payload['category_id']);
        $duplicate = app(QuickEntryService::class)->findDuplicate(['mode' => 'transaction', ...$payload]);
        $isElectric = ($meta['scan_type'] ?? null) === 'electric';
        $electric = $meta['electric_result'] ?? [];

        $msg = ($isElectric ? "⚡ *AI ĐÃ ĐỌC HÓA ĐƠN ĐIỆN*\n\n" : "🧾 *AI ĐÃ ĐỌC HÓA ĐƠN*\n\n")
            .'• Loại: *Chi tiêu*'."\n"
            .'• Số tiền: *'.number_format((float) $payload['amount'], 0, ',', '.')." đ*\n"
            .'• Ví: *'.($wallet?->name ?: 'Chưa chọn')."*\n"
            .'• Danh mục: *'.($category?->name ?: 'Chưa chọn')."*\n"
            .'• Mô tả: *'.$this->plain($payload['description'] ?? 'Hóa đơn')."*\n";

        if ($isElectric) {
            $msg .= '• Kỳ hóa đơn: *'.($electric['billing_month'] ?: now()->format('m/Y'))."*\n"
                .'• Sản lượng: *'.(! empty($electric['kwh']) ? number_format((float) $electric['kwh'], 1, ',', '.').' kWh' : 'N/A')."*\n"
                .'• Chỉ số: '.($electric['old_index'] ?? 'N/A').' → '.($electric['new_index'] ?? 'N/A')."\n";
        }

        if ($duplicate) {
            $msg .= "\n⚠️ ".$duplicate['message']."\n";
        }

        $msg .= "\nBấm *Lưu* để tạo giao dịch.";

        $this->sendMessage($chatId, $msg, [
            'inline_keyboard' => [
                [
                    ['text' => 'Lưu', 'callback_data' => 'r_save:'.$key],
                    ['text' => 'Bỏ qua', 'callback_data' => 'r_cancel:'.$key],
                ],
                [
                    ['text' => 'Đổi ví', 'callback_data' => 'r_wallet:'.$key],
                    ['text' => 'Đổi danh mục', 'callback_data' => 'r_cat:'.$key],
                ],
            ],
        ]);
    }

    private function receiptCacheKey(string $key): string
    {
        return 'telegram_receipt_preview:'.$key;
    }

    private function receiptPreviewPayload(string $key, User $user): ?array
    {
        $pending = Cache::get($this->receiptCacheKey($key));
        if (! $pending || (int) ($pending['user_id'] ?? 0) !== (int) $user->id) {
            return null;
        }

        return $pending;
    }

    private function storeReceiptPreview(string $key, User $user): ?Expense
    {
        $pending = $this->receiptPreviewPayload($key, $user);
        if (! $pending) {
            return null;
        }

        $payload = $pending['payload'];
        $meta = $pending['meta'] ?? [];
        $expenseService = app(ExpenseService::class);

        $expense = DB::transaction(function () use ($expenseService, $payload, $user, $meta) {
            $expense = $expenseService->createExpense($payload, $user);

            if (($meta['scan_type'] ?? null) === 'electric') {
                app(ElectricBillRecorder::class)->recordFromScan($expense, $meta['electric_result'] ?? []);
            }

            return $expense;
        });

        Cache::forget($this->receiptCacheKey($key));

        return $expense->fresh(['wallet', 'category']);
    }

    private function sendReceiptWalletChoices(int $chatId, string $key, User $user): void
    {
        $pending = $this->receiptPreviewPayload($key, $user);
        if (! $pending) {
            $this->sendMessage($chatId, '⚠️ Preview hóa đơn đã hết hạn.');

            return;
        }

        $wallets = Wallet::where('home_id', $pending['payload']['home_id'])
            ->where('is_archived', false)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $keyboard = $wallets->chunk(2)->map(fn ($row) => $row->map(fn ($wallet) => [
            'text' => $wallet->name,
            'callback_data' => 'r_set_w:'.$key.':'.$wallet->id,
        ])->values()->all())->values()->all();

        $this->sendMessage($chatId, 'Chọn ví cho hóa đơn:', ['inline_keyboard' => $keyboard]);
    }

    private function sendReceiptCategoryChoices(int $chatId, string $key, User $user): void
    {
        $pending = $this->receiptPreviewPayload($key, $user);
        if (! $pending) {
            $this->sendMessage($chatId, '⚠️ Preview hóa đơn đã hết hạn.');

            return;
        }

        $categories = ExpenseCategory::where('home_id', $pending['payload']['home_id'])
            ->where('type', Expense::TYPE_EXPENSE)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->take(24)
            ->get();

        $keyboard = $categories->chunk(2)->map(fn ($row) => $row->map(fn ($category) => [
            'text' => ($category->icon ?: '🧾').' '.$category->name,
            'callback_data' => 'r_set_c:'.$key.':'.$category->id,
        ])->values()->all())->values()->all();

        $this->sendMessage($chatId, 'Chọn danh mục cho hóa đơn:', ['inline_keyboard' => $keyboard]);
    }

    private function updateReceiptPreviewChoice(int $chatId, string $key, User $user, string $field, int $id): void
    {
        $pending = $this->receiptPreviewPayload($key, $user);
        if (! $pending) {
            $this->sendMessage($chatId, '⚠️ Preview hóa đơn đã hết hạn.');

            return;
        }

        if ($field === 'wallet_id') {
            Wallet::where('home_id', $pending['payload']['home_id'])->where('is_archived', false)->findOrFail($id);
        } else {
            ExpenseCategory::where('home_id', $pending['payload']['home_id'])->where('type', Expense::TYPE_EXPENSE)->findOrFail($id);
        }

        $pending['payload'][$field] = $id;
        Cache::put($this->receiptCacheKey($key), $pending, now()->addMinutes(30));
        $this->sendReceiptPreview($chatId, $user, $pending['payload'], $pending['meta'], $key);
    }

    private function sendExpenseWalletChoices(int $chatId, Expense $expense, User $user): void
    {
        if (! $this->canAccessHome($user, (int) $expense->home_id) || $expense->belongsToTransfer()) {
            $this->sendMessage($chatId, '❌ Không thể đổi ví cho giao dịch này.');

            return;
        }

        $wallets = Wallet::where('home_id', $expense->home_id)
            ->where('is_archived', false)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $keyboard = $wallets->chunk(2)->map(fn ($row) => $row->map(fn ($wallet) => [
            'text' => $wallet->name,
            'callback_data' => 'set_wallet:'.$expense->id.':'.$wallet->id,
        ])->values()->all())->values()->all();

        $this->sendMessage($chatId, 'Chọn ví mới:', ['inline_keyboard' => $keyboard]);
    }

    private function sendExpenseCategoryChoices(int $chatId, Expense $expense, User $user): void
    {
        if (! $this->canAccessHome($user, (int) $expense->home_id) || $expense->belongsToTransfer()) {
            $this->sendMessage($chatId, '❌ Không thể đổi danh mục cho giao dịch này.');

            return;
        }

        $categories = ExpenseCategory::where('home_id', $expense->home_id)
            ->where('type', $expense->type)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->take(24)
            ->get();

        $keyboard = $categories->chunk(2)->map(fn ($row) => $row->map(fn ($category) => [
            'text' => ($category->icon ?: '🧾').' '.$category->name,
            'callback_data' => 'set_cat:'.$expense->id.':'.$category->id,
        ])->values()->all())->values()->all();

        $this->sendMessage($chatId, 'Chọn danh mục mới:', ['inline_keyboard' => $keyboard]);
    }

    private function sendExpenseTypeChoices(int $chatId, Expense $expense, User $user): void
    {
        if (! $this->canAccessHome($user, (int) $expense->home_id) || $expense->belongsToTransfer()) {
            $this->sendMessage($chatId, '❌ Không thể đổi loại cho giao dịch này.');

            return;
        }

        $this->sendMessage($chatId, 'Chọn loại giao dịch:', [
            'inline_keyboard' => [
                [
                    ['text' => 'Chi tiêu', 'callback_data' => 'set_type:'.$expense->id.':expense'],
                    ['text' => 'Thu nhập', 'callback_data' => 'set_type:'.$expense->id.':income'],
                ],
            ],
        ]);
    }

    private function sendTransferWalletChoices(int $chatId, Transfer $transfer, User $user, string $side): void
    {
        if (! $this->canAccessHome($user, (int) $transfer->home_id)) {
            $this->sendMessage($chatId, '❌ Bạn không có quyền sửa chuyển ví này.');

            return;
        }

        $wallets = Wallet::where('home_id', $transfer->home_id)
            ->where('is_archived', false)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $prefix = $side === 'from' ? 'set_tr_from:' : 'set_tr_to:';
        $keyboard = $wallets->chunk(2)->map(fn ($row) => $row->map(fn ($wallet) => [
            'text' => $wallet->name,
            'callback_data' => $prefix.$transfer->id.':'.$wallet->id,
        ])->values()->all())->values()->all();

        $this->sendMessage($chatId, $side === 'from' ? 'Chọn ví nguồn mới:' : 'Chọn ví nhận mới:', ['inline_keyboard' => $keyboard]);
    }

    private function replaceTransferWallet(Transfer $transfer, User $user, ?int $fromWalletId, ?int $toWalletId): Transfer
    {
        if (! $this->canAccessHome($user, (int) $transfer->home_id)) {
            abort(403);
        }

        $fromWalletId ??= $transfer->from_wallet_id;
        $toWalletId ??= $transfer->to_wallet_id;

        Wallet::where('home_id', $transfer->home_id)->where('is_archived', false)->findOrFail($fromWalletId);
        Wallet::where('home_id', $transfer->home_id)->where('is_archived', false)->findOrFail($toWalletId);

        $data = [
            'home_id' => $transfer->home_id,
            'from_wallet_id' => $fromWalletId,
            'to_wallet_id' => $toWalletId,
            'amount' => (float) $transfer->amount,
            'fee' => (float) $transfer->fee,
            'description' => $transfer->description,
            'occurred_at' => $transfer->occurred_at?->toDateTimeString() ?? now()->toDateTimeString(),
        ];

        $transferService = app(TransferService::class);
        $transferService->reverseTransfer($transfer);

        return $transferService->createTransfer($data, $user);
    }

    private function fallbackCategoryForTelegram(int $homeId, string $type): ExpenseCategory
    {
        return ExpenseCategory::where('home_id', $homeId)
            ->where('type', $type)
            ->where(function ($query) {
                $query->where('name', 'Khác')
                    ->orWhere('name', 'Thu nhập khác')
                    ->orWhere('category_group', ExpenseCategory::GROUP_OTHER);
            })
            ->first()
            ?: ExpenseCategory::where('home_id', $homeId)->where('type', $type)->firstOrFail();
    }

    private function canAccessHome(User $user, int $homeId): bool
    {
        return $user->homeMembers()
            ->where('home_id', $homeId)
            ->whereIn('role', ['owner', 'manager'])
            ->exists();
    }

    private function handleCallbackQuery(array $callbackQuery, ExpenseService $expenseService): void
    {
        $queryId = (string) ($callbackQuery['id'] ?? '');
        $message = $callbackQuery['message'] ?? [];
        $chatId = $message['chat']['id'] ?? null;
        $data = $callbackQuery['data'] ?? '';
        $messageId = $message['message_id'] ?? null;
        $messageText = $message['text'] ?? $message['caption'] ?? 'Giao dịch';

        if (empty($queryId) || empty($chatId) || empty($data)) {
            $this->answerCallbackQuery($queryId, '⚠️ Dữ liệu không hợp lệ.');

            return;
        }

        $user = User::where('telegram_chat_id', $chatId)->first();
        if (! $user) {
            $this->answerCallbackQuery($queryId, '❌ Tài khoản chưa liên kết.');

            return;
        }

        if (str_starts_with($data, 'dup_save:')) {
            $key = Str::after($data, 'dup_save:');
            $pending = Cache::pull($this->pendingTransactionCacheKey($key));
            if (! $pending || (int) ($pending['user_id'] ?? 0) !== (int) $user->id) {
                $this->answerCallbackQuery($queryId, '⚠️ Giao dịch chờ đã hết hạn.');

                return;
            }

            try {
                $result = app(QuickEntryService::class)->storeItem($user, $pending['item'], true);
                $this->answerCallbackQuery($queryId, '✅ Đã lưu giao dịch.');
                $this->sendStoredQuickItemConfirmation($chatId, $result);
                $this->editMessageText($chatId, $messageId, $messageText."\n\n✅ *Đã lưu thêm giao dịch này.*");
            } catch (\Throwable $e) {
                $this->answerCallbackQuery($queryId, '❌ Lỗi: '.$e->getMessage());
            }
        } elseif (str_starts_with($data, 'dup_cancel:')) {
            Cache::forget($this->pendingTransactionCacheKey(Str::after($data, 'dup_cancel:')));
            $this->answerCallbackQuery($queryId, 'Đã bỏ qua.');
            $this->editMessageText($chatId, $messageId, $messageText."\n\n🚫 *Đã bỏ qua giao dịch này.*");
        } elseif (str_starts_with($data, 'tpl:')) {
            $templateId = (int) Str::after($data, 'tpl:');
            Cache::put('telegram_template:'.$chatId, $templateId, now()->addMinutes(10));
            $this->answerCallbackQuery($queryId, 'Nhập số tiền cho mẫu này.');
            $this->sendMessage($chatId, 'Nhập số tiền cho mẫu đã chọn. Ví dụ: `35k`, `1.2tr`, `50000`.');
        } elseif (str_starts_with($data, 'r_save:')) {
            $key = Str::after($data, 'r_save:');
            try {
                $expense = $this->storeReceiptPreview($key, $user);
                if (! $expense) {
                    $this->answerCallbackQuery($queryId, '⚠️ Preview hóa đơn đã hết hạn.');

                    return;
                }

                $this->answerCallbackQuery($queryId, '✅ Đã lưu hóa đơn.');
                $msg = "✅ *Đã lưu hóa đơn AI*\n\n"
                    .'*Số tiền*: '.number_format((float) $expense->amount, 0, ',', '.')." đ\n"
                    .'*Danh mục*: '.$expense->category?->name."\n"
                    .'*Ví*: '.$expense->wallet?->name."\n"
                    .'*Mô tả*: '.$this->plain($expense->description);
                $this->sendMessage($chatId, $msg, $this->expenseActionKeyboard($expense));
                $this->editMessageText($chatId, $messageId, $messageText."\n\n✅ *Đã lưu hóa đơn này.*");
            } catch (\Throwable $e) {
                $this->answerCallbackQuery($queryId, '❌ Lỗi: '.$e->getMessage());
            }
        } elseif (str_starts_with($data, 'r_cancel:')) {
            Cache::forget($this->receiptCacheKey(Str::after($data, 'r_cancel:')));
            $this->answerCallbackQuery($queryId, 'Đã bỏ qua hóa đơn.');
            $this->editMessageText($chatId, $messageId, $messageText."\n\n🚫 *Đã bỏ qua hóa đơn này.*");
        } elseif (str_starts_with($data, 'r_wallet:')) {
            $this->answerCallbackQuery($queryId, 'Chọn ví...');
            $this->sendReceiptWalletChoices($chatId, Str::after($data, 'r_wallet:'), $user);
        } elseif (str_starts_with($data, 'r_cat:')) {
            $this->answerCallbackQuery($queryId, 'Chọn danh mục...');
            $this->sendReceiptCategoryChoices($chatId, Str::after($data, 'r_cat:'), $user);
        } elseif (preg_match('/^r_set_w:([^:]+):(\d+)$/', $data, $matches)) {
            $this->answerCallbackQuery($queryId, 'Đã đổi ví.');
            $this->updateReceiptPreviewChoice($chatId, $matches[1], $user, 'wallet_id', (int) $matches[2]);
        } elseif (preg_match('/^r_set_c:([^:]+):(\d+)$/', $data, $matches)) {
            $this->answerCallbackQuery($queryId, 'Đã đổi danh mục.');
            $this->updateReceiptPreviewChoice($chatId, $matches[1], $user, 'category_id', (int) $matches[2]);
        } elseif (str_starts_with($data, 'change_wallet:')) {
            $expense = Expense::find((int) Str::after($data, 'change_wallet:'));
            if (! $expense) {
                $this->answerCallbackQuery($queryId, '⚠️ Giao dịch không tồn tại.');

                return;
            }
            $this->answerCallbackQuery($queryId, 'Chọn ví...');
            $this->sendExpenseWalletChoices($chatId, $expense, $user);
        } elseif (str_starts_with($data, 'change_category:')) {
            $expense = Expense::find((int) Str::after($data, 'change_category:'));
            if (! $expense) {
                $this->answerCallbackQuery($queryId, '⚠️ Giao dịch không tồn tại.');

                return;
            }
            $this->answerCallbackQuery($queryId, 'Chọn danh mục...');
            $this->sendExpenseCategoryChoices($chatId, $expense, $user);
        } elseif (str_starts_with($data, 'change_type:')) {
            $expense = Expense::find((int) Str::after($data, 'change_type:'));
            if (! $expense) {
                $this->answerCallbackQuery($queryId, '⚠️ Giao dịch không tồn tại.');

                return;
            }
            $this->answerCallbackQuery($queryId, 'Chọn loại...');
            $this->sendExpenseTypeChoices($chatId, $expense, $user);
        } elseif (preg_match('/^set_wallet:(\d+):(\d+)$/', $data, $matches)) {
            $expense = Expense::find((int) $matches[1]);
            $wallet = $expense ? Wallet::where('home_id', $expense->home_id)->where('is_archived', false)->find((int) $matches[2]) : null;
            if (! $expense || ! $wallet || ! $this->canAccessHome($user, (int) $expense->home_id) || $expense->belongsToTransfer()) {
                $this->answerCallbackQuery($queryId, '❌ Không thể đổi ví.');

                return;
            }
            $expenseService->updateExpense($expense, ['wallet_id' => $wallet->id]);
            $this->answerCallbackQuery($queryId, '✅ Đã đổi ví.');
            $this->sendMessage($chatId, '✅ Đã đổi ví giao dịch sang *'.$wallet->name.'*.', $this->expenseActionKeyboard($expense->fresh()));
        } elseif (preg_match('/^set_cat:(\d+):(\d+)$/', $data, $matches)) {
            $expense = Expense::find((int) $matches[1]);
            $category = $expense ? ExpenseCategory::where('home_id', $expense->home_id)->where('type', $expense->type)->find((int) $matches[2]) : null;
            if (! $expense || ! $category || ! $this->canAccessHome($user, (int) $expense->home_id) || $expense->belongsToTransfer()) {
                $this->answerCallbackQuery($queryId, '❌ Không thể đổi danh mục.');

                return;
            }
            $expenseService->updateExpense($expense, ['category_id' => $category->id]);
            $this->answerCallbackQuery($queryId, '✅ Đã đổi danh mục.');
            $this->sendMessage($chatId, '✅ Đã đổi danh mục giao dịch sang *'.$category->name.'*.', $this->expenseActionKeyboard($expense->fresh()));
        } elseif (preg_match('/^set_type:(\d+):(expense|income)$/', $data, $matches)) {
            $expense = Expense::find((int) $matches[1]);
            if (! $expense || ! $this->canAccessHome($user, (int) $expense->home_id) || $expense->belongsToTransfer()) {
                $this->answerCallbackQuery($queryId, '❌ Không thể đổi loại.');

                return;
            }
            $category = $this->fallbackCategoryForTelegram((int) $expense->home_id, $matches[2]);
            $expenseService->updateExpense($expense, ['type' => $matches[2], 'category_id' => $category->id]);
            $this->answerCallbackQuery($queryId, '✅ Đã đổi loại.');
            $this->sendMessage($chatId, '✅ Đã đổi loại giao dịch sang *'.($matches[2] === 'income' ? 'Thu nhập' : 'Chi tiêu').'*.', $this->expenseActionKeyboard($expense->fresh()));
        } elseif (str_starts_with($data, 'change_tr_from:')) {
            $transfer = Transfer::find((int) Str::after($data, 'change_tr_from:'));
            if (! $transfer) {
                $this->answerCallbackQuery($queryId, '⚠️ Chuyển ví không tồn tại.');

                return;
            }
            $this->answerCallbackQuery($queryId, 'Chọn ví nguồn...');
            $this->sendTransferWalletChoices($chatId, $transfer, $user, 'from');
        } elseif (str_starts_with($data, 'change_tr_to:')) {
            $transfer = Transfer::find((int) Str::after($data, 'change_tr_to:'));
            if (! $transfer) {
                $this->answerCallbackQuery($queryId, '⚠️ Chuyển ví không tồn tại.');

                return;
            }
            $this->answerCallbackQuery($queryId, 'Chọn ví nhận...');
            $this->sendTransferWalletChoices($chatId, $transfer, $user, 'to');
        } elseif (preg_match('/^set_tr_from:(\d+):(\d+)$/', $data, $matches)) {
            try {
                $transfer = Transfer::findOrFail((int) $matches[1]);
                $newTransfer = $this->replaceTransferWallet($transfer, $user, (int) $matches[2], null);
                $this->answerCallbackQuery($queryId, '✅ Đã đổi ví nguồn.');
                $this->sendStoredQuickItemConfirmation($chatId, ['mode' => 'transfer', 'id' => $newTransfer->id]);
                $this->editMessageText($chatId, $messageId, $messageText."\n\n🔁 *Đã thay bằng chuyển ví #{$newTransfer->id}.*");
            } catch (\Throwable $e) {
                $this->answerCallbackQuery($queryId, '❌ Lỗi: '.$e->getMessage());
            }
        } elseif (preg_match('/^set_tr_to:(\d+):(\d+)$/', $data, $matches)) {
            try {
                $transfer = Transfer::findOrFail((int) $matches[1]);
                $newTransfer = $this->replaceTransferWallet($transfer, $user, null, (int) $matches[2]);
                $this->answerCallbackQuery($queryId, '✅ Đã đổi ví nhận.');
                $this->sendStoredQuickItemConfirmation($chatId, ['mode' => 'transfer', 'id' => $newTransfer->id]);
                $this->editMessageText($chatId, $messageId, $messageText."\n\n🔁 *Đã thay bằng chuyển ví #{$newTransfer->id}.*");
            } catch (\Throwable $e) {
                $this->answerCallbackQuery($queryId, '❌ Lỗi: '.$e->getMessage());
            }
        } elseif (str_starts_with($data, 'undo_expense:')) {
            $expenseId = (int) substr($data, 13);
            $expense = Expense::find($expenseId);

            if (! $expense) {
                $this->answerCallbackQuery($queryId, '⚠️ Giao dịch không tồn tại hoặc đã bị xóa.');

                return;
            }

            $isMember = $user->homeMembers()->where('home_id', $expense->home_id)->exists();
            if (! $isMember) {
                $this->answerCallbackQuery($queryId, '❌ Bạn không có quyền thực hiện hành động này.');

                return;
            }

            try {
                $expenseService->deleteExpense($expense);
                $this->answerCallbackQuery($queryId, '✅ Đã hoàn tác chi tiêu thành công.');

                $this->editMessageText($chatId, $messageId, $messageText."\n\n🔄 *Đã hoàn tác giao dịch này.*");
            } catch (\Throwable $e) {
                $this->answerCallbackQuery($queryId, '❌ Lỗi: '.$e->getMessage());
            }
        } elseif (str_starts_with($data, 'undo_transfer:')) {
            $transferId = (int) substr($data, 14);
            $transfer = Transfer::find($transferId);

            if (! $transfer) {
                $this->answerCallbackQuery($queryId, '⚠️ Giao dịch không tồn tại hoặc đã bị xóa.');

                return;
            }

            $isMember = $user->homeMembers()->where('home_id', $transfer->home_id)->exists();
            if (! $isMember) {
                $this->answerCallbackQuery($queryId, '❌ Bạn không có quyền thực hiện hành động này.');

                return;
            }

            try {
                $transferService = app(TransferService::class);
                $transferService->reverseTransfer($transfer);
                $this->answerCallbackQuery($queryId, '✅ Đã hoàn tác chuyển khoản thành công.');

                $this->editMessageText($chatId, $messageId, $messageText."\n\n🔄 *Đã hoàn tác chuyển khoản này.*");
            } catch (\Throwable $e) {
                $this->answerCallbackQuery($queryId, '❌ Lỗi: '.$e->getMessage());
            }
        } elseif ($data === 'view_wallets') {
            $this->answerCallbackQuery($queryId, 'Đang tải danh sách ví...');
            $this->handleWalletsCommand($chatId);
        } elseif ($data === 'cmd_today') {
            $this->answerCallbackQuery($queryId, 'Đang tải tóm tắt hôm nay...');
            $this->handleSummaryCommand($chatId, 'day');
        } elseif ($data === 'cmd_recent') {
            $this->answerCallbackQuery($queryId, 'Đang tải giao dịch gần đây...');
            $this->handleRecentCommand($chatId, 5);
        } elseif ($data === 'cmd_templates') {
            $this->answerCallbackQuery($queryId, 'Đang tải mẫu nhập...');
            $this->handleTemplatesCommand($chatId);
        } else {
            $this->answerCallbackQuery($queryId, '⚠️ Lệnh không xác định.');
        }
    }

    private function answerCallbackQuery(string $callbackQueryId, string $text): void
    {
        $token = config('services.telegram.bot_token');
        if (empty($token) || empty($callbackQueryId)) {
            return;
        }

        Http::post("https://api.telegram.org/bot{$token}/answerCallbackQuery", [
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
        ]);
    }

    private function editMessageText(int $chatId, ?int $messageId, string $text): void
    {
        $token = config('services.telegram.bot_token');
        if (empty($token) || empty($messageId)) {
            return;
        }

        Http::post("https://api.telegram.org/bot{$token}/editMessageText", [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ]);
    }

    private function sendMessage(int $chatId, string $text, ?array $replyMarkup = null): void
    {
        $token = config('services.telegram.bot_token');
        if (empty($token)) {
            Log::warning('Telegram bot token not configured; message could not be sent.', [
                'chat_id' => $chatId,
                'text' => $text,
            ]);

            return;
        }

        $params = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ];

        if ($replyMarkup) {
            $params['reply_markup'] = $replyMarkup;
        }

        Http::post("https://api.telegram.org/bot{$token}/sendMessage", $params);
    }
}
