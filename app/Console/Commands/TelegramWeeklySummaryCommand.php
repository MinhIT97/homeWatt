<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use Modules\Expense\Models\Expense;
use Modules\Wallet\Models\Wallet;
use Modules\Device\Models\Device;
use Modules\Energy\Models\EnergyReading;

class TelegramWeeklySummaryCommand extends Command
{
    protected $signature = 'telegram:weekly-summary';
    protected $description = 'Gửi báo cáo tóm tắt tài chính và điện năng tiêu thụ hàng tuần qua Telegram';

    public function handle()
    {
        $token = config('services.telegram.bot_token');
        if (empty($token)) {
            $this->error('Token Telegram Bot chưa được cấu hình.');
            return 1;
        }

        $users = User::whereNotNull('telegram_chat_id')->get();
        $this->info("Tìm thấy " . $users->count() . " người dùng đã liên kết Telegram.");

        $startOfWeek = now()->startOfWeek()->startOfDay();
        $endOfWeek = now()->endOfWeek()->endOfDay();

        foreach ($users as $user) {
            $this->info("Đang tính toán số liệu tuần cho: {$user->name}...");

            // 1. Tính tổng Thu/Chi trong tuần
            $weeklyTransactions = Expense::where('user_id', $user->id)
                ->whereBetween('occurred_at', [$startOfWeek, $endOfWeek])
                ->get();

            $totalIncome = (float) $weeklyTransactions->where('type', 'income')->sum('amount');
            $totalExpense = (float) $weeklyTransactions->where('type', 'expense')->sum('amount');
            $netSavings = $totalIncome - $totalExpense;

            // 2. Tính số dư các ví
            $homeIds = $user->homeMembers()->pluck('home_id');
            $wallets = Wallet::whereIn('home_id', $homeIds)->where('is_archived', false)->get();

            // 3. Tính số điện tiêu thụ (kWh) trong tuần
            $devices = Device::whereHas('room.home.members', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->get();
            $deviceIds = $devices->pluck('id');
            
            $weeklyKwh = 0.0;
            if ($deviceIds->isNotEmpty()) {
                $weeklyKwh = (float) EnergyReading::whereIn('device_id', $deviceIds)
                    ->whereBetween('recorded_at', [$startOfWeek, $endOfWeek])
                    ->sum('kwh');
            }

            // Xây dựng tin nhắn báo cáo
            $msg = "📊 *BÁO CÁO TÓM TẮT TUẦN HOMEWATT*\n"
                 . "📅 _Từ " . $startOfWeek->format('d/m/Y') . " đến " . $endOfWeek->format('d/m/Y') . "_\n\n"
                 . "💰 *Tình hình Tài chính:*\n"
                 . "• 🟢 Tổng thu: *" . number_format($totalIncome, 0, ',', '.') . " đ*\n"
                 . "• 🔴 Tổng chi: *" . number_format($totalExpense, 0, ',', '.') . " đ*\n"
                 . "• ⚖️ Tích lũy ròng: *" . ($netSavings >= 0 ? '+' : '') . number_format($netSavings, 0, ',', '.') . " đ*\n\n"
                 . "⚡ *Điện năng tiêu thụ:*\n"
                 . "• 🔋 Tổng điện tiêu thụ: *" . number_format($weeklyKwh, 1, ',', '.') . " kWh*\n"
                 . "• 💸 Tiền điện ước tính: ~*" . number_format($weeklyKwh * 2000, 0, ',', '.') . " đ* _(tạm tính 2.000đ/kWh)_\n\n"
                 . "💳 *Số dư Ví hiện tại:*\n";

            if ($wallets->isNotEmpty()) {
                foreach ($wallets as $w) {
                    $balanceStr = number_format($w->calculatedBalance(), 0, ',', '.') . ' ' . $w->currency;
                    $emoji = ($w->type === 'credit_card' || $w->type === 'overdraft') ? '💳' : '💵';
                    $msg .= "  • {$emoji} *{$w->name}*: {$balanceStr}\n";
                }
            } else {
                $msg .= "  • _Chưa có thông tin ví_\n";
            }

            $msg .= "\n chúc bạn một tuần mới làm việc hiệu quả! 🚀";

            $this->sendMessage($token, $user->telegram_chat_id, $msg);
            $this->info("-> Đã gửi báo cáo tuần tới {$user->name}.");
        }

        return 0;
    }

    private function sendMessage(string $token, string $chatId, string $text): void
    {
        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ]);
    }
}
