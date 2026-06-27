<?php

namespace Modules\Expense\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Expense\Services\ExpenseService;
use Modules\Expense\Services\TelegramParserService;
use Modules\Wallet\Models\Wallet;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, TelegramParserService $parser, ExpenseService $expenseService): JsonResponse
    {
        $secret = config('services.telegram.webhook_secret');
        if ($secret && $request->header('X-Telegram-Bot-Api-Secret-Token') !== $secret) {
            abort(403, 'Invalid webhook token');
        }

        $chatId = $request->input('message.chat.id');
        $text = trim($request->input('message.text', ''));

        if (empty($chatId) || empty($text)) {
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
                 . "Để kết nối tài khoản Telegram này với ứng dụng HomeWatt, vui lòng:\n"
                 . "1. Đăng nhập vào trang web HomeWatt.\n"
                 . "2. Đi tới trang Cá nhân (Profile).\n"
                 . "3. Nhấn 'Liên kết Telegram' để lấy mã kết nối và click vào link bot.";
            $this->sendMessage($chatId, $msg);
            return;
        }

        $code = trim($matches[1]);
        // Support prefixes like link_123456
        if (str_starts_with($code, 'link_')) {
            $code = substr($code, 5);
        }

        $user = User::where('telegram_verification_code', $code)->first();

        if (!$user) {
            $this->sendMessage($chatId, '❌ Mã liên kết không hợp lệ hoặc đã hết hạn.');
            return;
        }

        // Link Telegram account
        $user->forceFill([
            'telegram_chat_id' => $chatId,
            'telegram_verification_code' => null,
        ])->save();

        $msg = "🎉 Liên kết tài khoản thành công!\n\n"
             . "Tài khoản HomeWatt của bạn: *" . e($user->name) . "* (" . e($user->email) . ")\n\n"
             . "Bây giờ bạn có thể nhập chi tiêu nhanh qua đây bất cứ lúc nào bằng cú pháp thông minh. Ví dụ:\n"
             . "• `chi 50k ăn sáng` (Mặc định ghi vào ví Tiền mặt)\n"
             . "• `chi 150k vcb ăn tối` (Ghi nhận vào ví Vietcombank/vcb)\n"
             . "• `chi 100k vpbank xăng xe` (Ghi nhận vào ví VPBank)\n"
             . "• `thu 2.5tr bán điện mặt trời`\n"
             . "• `cho vay 500k cho bạn Nam`\n"
             . "• `đi vay 1m từ anh Ba`\n\n"
             . "💡 *Mẹo:* Bạn chỉ cần gõ thêm tên ví hoặc tên viết tắt (như `vcb`, `tech`, `momo`, `tm`...) vào tin nhắn để hệ thống tự nhận diện đúng ví ghi nhận!";
        
        $this->sendMessage($chatId, $msg);
    }

    private function handleTransactionCommand(int $chatId, string $text, TelegramParserService $parser, ExpenseService $expenseService): void
    {
        $user = User::where('telegram_chat_id', $chatId)->first();

        if (!$user) {
            $msg = "❌ Tài khoản Telegram của bạn chưa được liên kết với HomeWatt.\n\n"
                 . "Vui lòng đăng nhập vào trang web, vào trang Cá nhân và lấy mã để liên kết tài khoản.";
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
            ->sortByDesc(fn($w) => mb_strlen($w->name, 'UTF-8'))
            ->values();

        if ($allWallets->isEmpty()) {
            $this->sendMessage($chatId, '❌ Ngôi nhà của bạn chưa có ví tiền nào để ghi nhận giao dịch. Vui lòng tạo ví trên website.');
            return;
        }

        // 1. Match wallet across ALL homes
        $selectedWallet = null;
        $selectedHome = null;
        $cleanTextLower = mb_strtolower($text, 'UTF-8');

        foreach ($allWallets as $w) {
            $walletNameLower = mb_strtolower($w->name, 'UTF-8');
            $walletNameNoSpaces = str_replace(' ', '', $walletNameLower);

            // Xây dựng danh sách các từ khóa có thể khớp với ví này
            $matchCandidates = [
                $w->name,
                $walletNameLower,
                $walletNameNoSpaces,
                'tài khoản ' . $walletNameLower,
                'tài khoản ' . $walletNameNoSpaces,
                'taikhoan ' . $walletNameLower,
                'taikhoan ' . $walletNameNoSpaces,
                'tk ' . $walletNameLower,
                'tk ' . $walletNameNoSpaces,
            ];

            // Tự động thêm các viết tắt phổ biến
            if (str_contains($walletNameLower, 'techcombank')) {
                $matchCandidates[] = 'tech';
                $matchCandidates[] = 'tcb';
            }
            if (str_contains($walletNameLower, 'vietcombank')) {
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

            // Sắp xếp các ứng viên khớp theo độ dài giảm dần để khớp cụm từ dài trước (tránh khớp nhầm từ ngắn)
            usort($matchCandidates, fn($a, $b) => strlen($b) <=> strlen($a));

            foreach ($matchCandidates as $candidate) {
                $candidateLower = mb_strtolower($candidate, 'UTF-8');
                
                // Khớp trực tiếp trong tin nhắn
                if (str_contains($cleanTextLower, $candidateLower)) {
                    $selectedWallet = $w;
                    $selectedHome = $memberships->firstWhere('home_id', $w->home_id)?->home;
                    $text = str_ireplace($candidateLower, '', $text);
                    break 2;
                }
                
                // Khớp không dấu/không cách (ví dụ "vp bank" khớp với "vpbank")
                $candidateNoSpaces = str_replace(' ', '', $candidateLower);
                $textNoSpaces = str_replace(' ', '', $cleanTextLower);
                if (str_contains($textNoSpaces, $candidateNoSpaces)) {
                    $selectedWallet = $w;
                    $selectedHome = $memberships->firstWhere('home_id', $w->home_id)?->home;
                    
                    // Loại bỏ phần khớp khỏi tin nhắn gốc để không bị đưa vào mô tả
                    $text = str_ireplace($w->name, '', $text);
                    $text = str_ireplace($candidateLower, '', $text);
                    foreach (explode(' ', $candidateLower) as $part) {
                        if (strlen($part) > 1) {
                            $text = str_ireplace($part, '', $text);
                        }
                    }
                    break 2;
                }
            }
        }

        // 2. Fallback to first home's default wallets
        if (!$selectedWallet) {
            $firstHome = $memberships->first()->home;
            $homeWallets = $allWallets->where('home_id', $firstHome->id);
            $selectedWallet = $homeWallets->first(fn($w) => str_contains(mb_strtolower($w->name, 'UTF-8'), 'tiền mặt'))
                ?: $homeWallets->first(fn($w) => str_contains(mb_strtolower($w->name, 'UTF-8'), 'chính'))
                ?: $homeWallets->first();
            $selectedHome = $firstHome;

            // Notify multi-home users which home is being used
            if ($memberships->count() > 1) {
                $this->sendMessage($chatId, "ℹ️ Đang ghi giao dịch vào nhà *{$selectedHome->name}*.\nĐể chọn nhà khác, hãy thêm tên ví thuộc nhà đó vào tin nhắn.");
            }
        }

        $home = $selectedHome;

        $parsed = $parser->parse($text, $home->id);

        if (!$parsed) {
            $msg = "❓ Cú pháp không hợp lệ. Vui lòng nhập theo các ví dụ sau:\n\n"
                 . "• *Chi tiêu*: `chi 75k mua rau quả` hoặc `tieu 200k vcb xang xe`\n"
                 . "• *Thu nhập*: `thu 12tr luong thang` hoặc `thu 500k momo bán đồ cũ`\n"
                 . "• *Cho vay*: `cho vay 200k cho bạn`\n"
                 . "• *Đi vay*: `vay 1tr mua đồ ăn`\n"
                 . "• *Trả nợ*: `trả nợ 100k`\n"
                 . "• *Thu nợ*: `thu nợ 300k từ Nam`\n\n"
                 . "💡 *Lưu ý:* Hệ thống tự động ghi nhận vào ví đúng nếu bạn ghi tên ví hoặc tên viết tắt (như vcb, tech, momo, tm) trong nội dung tin nhắn.";
            $this->sendMessage($chatId, $msg);
            return;
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
                    . "*Loại*: " . $typeEmoji . "\n"
                    . "*Số tiền*: " . number_format($parsed['amount'], 0, ',', '.') . " đ\n"
                    . "*Danh mục*: " . $parsed['category_name'] . "\n"
                    . "*Ghi chú*: " . $parsed['description'] . "\n"
                    . "*Ví*: " . $selectedWallet->name . " (Số dư: " . number_format((float) $selectedWallet->fresh()->calculatedBalance(), 0, ',', '.') . " đ)";

        $this->sendMessage($chatId, $confirmMsg);
    }

    private function handleHelpCommand(int $chatId): void
    {
        $msg = "ℹ️ *HƯỚNG DẪN SỬ DỤNG HOMEWATT BOT*\n\n"
             . "🤖 Bot giúp ghi chép nhanh giao dịch bằng cú pháp tiếng Việt thông minh.\n\n"
             . "🔑 *CÁC LỆNH HỆ THỐNG:*\n"
             . "• `/help` hoặc `help`: Hiển thị hướng dẫn này\n"
             . "• `/vi` hoặc `/wallets` hoặc `vi`: Xem danh sách các ví và số dư hiện tại\n\n"
             . "📝 *CÚ PHÁP GHI CHÉP GIAO DỊCH:*\n"
             . "Gõ theo định dạng: `[Hành động] [Số tiền] [Tên ví (nếu có)] [Mô tả/Hạng mục]`\n\n"
             . "• 🔴 *Chi tiêu*: `chi 75k mua rau` hoặc `tieu 200k vcb xang xe`\n"
             . "• 🟢 *Thu nhập*: `thu 12tr luong` hoặc `thu 500k momo ban do`\n"
             . "• 🤝 *Cho vay*: `cho vay 200k cho ban`\n"
             . "• 💸 *Trả nợ*: `tra no 100k`\n"
             . "• 🏦 *Đi vay*: `vay 1tr mua do`\n"
             . "• 🪙 *Thu nợ*: `thu no 300k tu Nam`\n\n"
             . "💡 *Mẹo nhận diện ví:* Thêm tên ví hoặc viết tắt của ví (như `vcb`, `tech`, `momo`, `tm`) để hệ thống tự khớp. Mặc định sẽ ghi vào ví *Tiền mặt* nếu không ghi tên ví.";

        $this->sendMessage($chatId, $msg);
    }

    private function handleWalletsCommand(int $chatId): void
    {
        $user = User::where('telegram_chat_id', $chatId)->first();

        if (!$user) {
            $msg = "❌ Tài khoản Telegram của bạn chưa được liên kết với HomeWatt.\n\n"
                 . "Vui lòng đăng nhập vào trang web, vào trang Cá nhân và lấy mã để liên kết tài khoản.";
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
                $typeEmoji = $w->type === Wallet::TYPE_CREDIT_CARD ? '💳' : ($w->type === Wallet::TYPE_BANK ? '🏦' : '💵');
                $balanceStr = number_format($w->calculatedBalance(), 0, ',', '.') . ' ' . $w->currency;
                
                if ($w->type === Wallet::TYPE_CREDIT_CARD) {
                    $debt = (float) $w->opening_balance - $w->calculatedBalance();
                    $msg .= "  • {$typeEmoji} *{$w->name}*: Hạn mức " . number_format($w->opening_balance, 0, ',', '.') . " | Đang nợ: " . number_format($debt, 0, ',', '.') . " {$w->currency}\n";
                } else {
                    $msg .= "  • {$typeEmoji} *{$w->name}*: {$balanceStr}\n";
                }
            }
            $msg .= "\n";
        }

        $this->sendMessage($chatId, trim($msg));
    }

    private function sendMessage(int $chatId, string $text): void
    {
        $token = config('services.telegram.bot_token');
        if (empty($token)) {
            Log::warning('Telegram bot token not configured; message could not be sent.', [
                'chat_id' => $chatId,
                'text' => $text,
            ]);
            return;
        }

        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ]);
    }
}
