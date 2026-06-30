<?php

namespace Modules\Expense\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\AI\Services\GeminiBillScanner;
use Modules\AI\Services\GeminiElectricBillScanner;
use Modules\Energy\Services\ElectricBillRecorder;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Expense\Models\Transfer;
use Modules\Expense\Services\ExpenseService;
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

            // Handle /wallets or /vi command
            if ($cleanTextLower === '/wallets' || $cleanTextLower === '/vi' || $cleanTextLower === 'vi' || $cleanTextLower === '/balance' || $cleanTextLower === '/sodu' || $cleanTextLower === 'so du') {
                $this->handleWalletsCommand($chatId);

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
             ."• `cho vay 500k cho bạn Nam`\n"
             ."• `đi vay 1m từ anh Ba`\n\n"
             .'💡 *Mẹo:* Bạn chỉ cần gõ thêm tên ví hoặc tên viết tắt (như `vcb`, `tech`, `momo`, `tm`...) vào tin nhắn để hệ thống tự nhận diện đúng ví ghi nhận!';

        $this->sendMessage($chatId, $msg);
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
                 ."• *Cho vay*: `cho vay 200k cho bạn`\n"
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

            try {
                $transferService = app(TransferService::class);
                $transfer = $transferService->createTransfer([
                    'home_id' => $home->id,
                    'from_wallet_id' => $fromWallet->id,
                    'to_wallet_id' => $toWallet->id,
                    'amount' => $parsed['amount'],
                    'description' => $parsed['description'],
                    'occurred_at' => now()->toDateTimeString(),
                ], $user);

                $confirmMsg = "✅ *Chuyển khoản thành công!*\n\n"
                            .'*Số tiền*: '.number_format($parsed['amount'], 0, ',', '.')." đ\n"
                            .'*Từ ví*: '.$fromWallet->name.' (Số dư: '.number_format((float) $fromWallet->fresh()->calculatedBalance(), 0, ',', '.')." đ)\n"
                            .'*Sang ví*: '.$toWallet->name.' (Số dư: '.number_format((float) $toWallet->fresh()->calculatedBalance(), 0, ',', '.')." đ)\n"
                            .'*Ghi chú*: '.$parsed['description'];

                $replyMarkup = [
                    'inline_keyboard' => [
                        [
                            ['text' => '↩️ Hoàn tác', 'callback_data' => 'undo_transfer:'.$transfer->id],
                            ['text' => '💳 Số dư ví', 'callback_data' => 'view_wallets'],
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
            'occurred_at' => now()->toDateTimeString(),
        ];

        $expense = $expenseService->createExpense($payload, $user);

        // Success Confirmation Message
        $typeEmoji = $parsed['type'] === 'income' ? '🟢 THU NHẬP' : '🔴 CHI TIÊU';
        $confirmMsg = "✅ *Ghi nhận thành công!*\n\n"
                    .'*Loại*: '.$typeEmoji."\n"
                    .'*Số tiền*: '.number_format($parsed['amount'], 0, ',', '.')." đ\n"
                    .'*Danh mục*: '.$parsed['category_name']."\n"
                    .'*Ghi chú*: '.$parsed['description']."\n"
                    .'*Ví*: '.$selectedWallet->name.' (Số dư: '.number_format((float) $selectedWallet->fresh()->calculatedBalance(), 0, ',', '.').' đ)';

        $replyMarkup = [
            'inline_keyboard' => [
                [
                    ['text' => '↩️ Hoàn tác', 'callback_data' => 'undo_expense:'.$expense->id],
                    ['text' => '💳 Số dư ví', 'callback_data' => 'view_wallets'],
                ],
            ],
        ];

        $this->sendMessage($chatId, $confirmMsg, $replyMarkup);
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
             ."• `/vi` hoặc `/wallets` hoặc `vi`: Xem danh sách các ví và số dư hiện tại\n\n"
             ."📝 *CÚ PHÁP GHI CHÉP GIAO DỊCH:*\n"
             ."Gõ theo định dạng: `[Hành động] [Số tiền] [Tên ví (nếu có)] [Mô tả/Hạng mục]`\n\n"
             ."• 🔴 *Chi tiêu*: `chi 75k mua rau` hoặc `tieu 200k vcb xang xe`\n"
             ."• 🟢 *Thu nhập*: `thu 12tr luong` hoặc `thu 500k momo ban do`\n"
             ."• 🤝 *Cho vay*: `cho vay 200k cho ban`\n"
             ."• 💸 *Trả nợ*: `tra no 100k`\n"
             ."• 🏦 *Đi vay*: `vay 1tr mua do`\n"
             ."• 🪙 *Thu nợ*: `thu no 300k tu Nam`\n\n"
             .'💡 *Mẹo nhận diện ví:* Thêm tên ví hoặc viết tắt của ví (như `vcb`, `tech`, `momo`, `tm`) để hệ thống tự khớp. Mặc định sẽ ghi vào ví *Tiền mặt* nếu không ghi tên ví.';

        $this->sendMessage($chatId, $msg);
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
            $categories = ExpenseCategory::where('home_id', $home->id)->get();
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

            [$expense, $energyBill] = DB::transaction(function () use ($expenseService, $payload, $user, $electricResult) {
                $expense = $expenseService->createExpense($payload, $user);
                $energyBill = app(ElectricBillRecorder::class)->recordFromScan($expense, $electricResult);

                return [$expense, $energyBill];
            });

            $billingMonth = $electricResult['billing_month'] ?: now()->format('m/Y');
            $confirmMsg = "✅ *QUÉT HÓA ĐƠN ĐIỆN THÀNH CÔNG (AI)*\n\n"
                        ."• 🔴 *CHI TIÊU*\n"
                        .'• *Kỳ hóa đơn*: *'.$billingMonth."*\n"
                        .'• *Số tiền*: *'.number_format($expense->amount, 0, ',', '.')." đ*\n"
                        .'• *Sản lượng*: *'.($electricResult['kwh'] ? number_format($electricResult['kwh'], 1, ',', '.').' kWh' : 'N/A')."*\n"
                        .'• *Chỉ số cũ/mới*: '.($electricResult['old_index'] ?? 'N/A').' ➡️ '.($electricResult['new_index'] ?? 'N/A')."\n"
                        .'• *Khách hàng*: '.($electricResult['customer_name'] ?: 'N/A').' ('.($electricResult['customer_code'] ?: 'N/A').")\n"
                        .'• *Ví*: *'.$wallet->name.'* (Số dư: '.number_format((float) $wallet->fresh()->calculatedBalance(), 0, ',', '.')." đ)\n\n"
                        .'⚡ *Energy Bill*: #'.$energyBill->id."\n\n"
                        .'🤖 _Hóa đơn điện đã được tự động ghi nhận vào Chi tiêu và Energy!_';

            $replyMarkup = [
                'inline_keyboard' => [
                    [
                        ['text' => '↩️ Hoàn tác', 'callback_data' => 'undo_expense:'.$expense->id],
                        ['text' => '💳 Số dư ví', 'callback_data' => 'view_wallets'],
                    ],
                ],
            ];

            $this->sendMessage($chatId, $confirmMsg, $replyMarkup);

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
        $categories = ExpenseCategory::all();
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
            $selectedCategory = $categories->where('type', 'expense')->first();
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

        $expense = $expenseService->createExpense($payload, $user);

        $confirmMsg = "✅ *QUÉT HÓA ĐƠN THÀNH CÔNG (AI)*\n\n"
                    ."• 🔴 *CHI TIÊU*\n"
                    .'• *Số tiền*: *'.number_format($expense->amount, 0, ',', '.')." đ*\n"
                    .'• *Danh mục*: *'.$selectedCategory->name."*\n"
                    .'• *Mô tả*: *'.$expense->description."*\n"
                    .'• *Ví*: *'.$wallet->name.'* (Số dư: '.number_format((float) $wallet->fresh()->calculatedBalance(), 0, ',', '.')." đ)\n\n"
                    .'🤖 _Hóa đơn đã được tự động ghi nhận vào tài khoản!_';

        $replyMarkup = [
            'inline_keyboard' => [
                [
                    ['text' => '↩️ Hoàn tác', 'callback_data' => 'undo_expense:'.$expense->id],
                    ['text' => '💳 Số dư ví', 'callback_data' => 'view_wallets'],
                ],
            ],
        ];

        $this->sendMessage($chatId, $confirmMsg, $replyMarkup);
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

        if (str_starts_with($data, 'undo_expense:')) {
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
