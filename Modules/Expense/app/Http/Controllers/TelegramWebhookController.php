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
        $user->update([
            'telegram_chat_id' => $chatId,
            'telegram_verification_code' => null,
        ]);

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

        $member = $user->homeMembers()->with('home')->first();

        if (!$member || !$member->home) {
            $this->sendMessage($chatId, '❌ Bạn chưa tham gia vào bất kỳ ngôi nhà nào trên HomeWatt. Vui lòng tạo hoặc tham gia nhà trước.');
            return;
        }

        $home = $member->home;
        $wallets = Wallet::where('home_id', $home->id)->where('is_archived', false)->get();

        if ($wallets->isEmpty()) {
            $this->sendMessage($chatId, '❌ Ngôi nhà của bạn chưa có ví tiền nào để ghi nhận giao dịch. Vui lòng tạo ví trên website.');
            return;
        }

        // 1. Match wallet based on name or keywords inside the message
        $selectedWallet = null;
        $cleanTextLower = mb_strtolower($text, 'UTF-8');

        foreach ($wallets as $w) {
            $walletNameLower = mb_strtolower($w->name, 'UTF-8');
            
            // Full name matching
            if (str_contains($cleanTextLower, $walletNameLower)) {
                $selectedWallet = $w;
                // Strip the wallet name out of the text so it does not get in the description
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
                    $text = str_ireplace($abbr, '', $text);
                    break 2;
                }
            }
        }

        // 2. Fallback to default wallets
        if (!$selectedWallet) {
            $selectedWallet = $wallets->first(fn($w) => str_contains(mb_strtolower($w->name, 'UTF-8'), 'tiền mặt'))
                ?: $wallets->first(fn($w) => str_contains(mb_strtolower($w->name, 'UTF-8'), 'chính'))
                ?: $wallets->first();
        }

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
                    . "*Ví*: " . $selectedWallet->name . " (Số dư: " . number_format((float) $selectedWallet->fresh()->balance, 0, ',', '.') . " đ)";

        $this->sendMessage($chatId, $confirmMsg);
    }

    private function sendMessage(int $chatId, string $text): void
    {
        $token = config('services.telegram.bot_token') ?? env('TELEGRAM_BOT_TOKEN');
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
