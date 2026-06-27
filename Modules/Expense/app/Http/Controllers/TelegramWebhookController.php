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
             . "• `chi 50k ăn sáng`\n"
             . "• `thu 2.5tr bán điện mặt trời`\n"
             . "• `cho vay 500k cho bạn Nam`\n"
             . "• `đi vay 1m từ anh Ba`";
        
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
        $allWallets = Wallet::whereIn('home_id', $homeIds)->where('is_archived', false)->get();

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

            if (str_contains($cleanTextLower, $walletNameLower)) {
                $selectedWallet = $w;
                $selectedHome = $memberships->firstWhere('home_id', $w->home_id)?->home;
                $text = str_ireplace($w->name, '', $text);
                break;
            }

            // Keyword abbreviations matching
            $abbreviations = [];
            if (str_contains($walletNameLower, 'techcombank')) $abbreviations[] = 'tech';
            if (str_contains($walletNameLower, 'vietcombank')) $abbreviations[] = 'vcb';
            if (str_contains($walletNameLower, 'momo')) $abbreviations[] = 'momo';
            if (str_contains($walletNameLower, 'tiền mặt')) {
                $abbreviations[] = 'tien mat';
                $abbreviations[] = 'tm';
            }

            foreach ($abbreviations as $abbr) {
                if (str_contains($cleanTextLower, $abbr)) {
                    $selectedWallet = $w;
                    $selectedHome = $memberships->firstWhere('home_id', $w->home_id)?->home;
                    $text = str_ireplace($abbr, '', $text);
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
                 . "• *Chi tiêu*: `chi 75k mua rau quả` hoặc `tieu 200k xang xe`\n"
                 . "• *Thu nhập*: `thu 12tr luong thang` hoặc `thu 500k bán đồ cũ`\n"
                 . "• *Cho vay*: `cho vay 200k cho bạn`\n"
                 . "• *Đi vay*: `vay 1tr mua đồ ăn`\n"
                 . "• *Trả nợ*: `trả nợ 100k`\n"
                 . "• *Thu nợ*: `thu nợ 300k từ Nam`";
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
