<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Modules\Device\Models\Device;

class TelegramProactiveAlertsCommand extends Command
{
    protected $signature = 'telegram:send-alerts';

    protected $description = 'Quét thiết bị và gửi cảnh báo bảo hành/bảo dưỡng định kỳ qua Telegram cho người dùng';

    public function handle()
    {
        $token = config('services.telegram.bot_token');
        if (empty($token)) {
            $this->error('Token Telegram Bot chưa được cấu hình.');

            return 1;
        }

        $users = User::whereNotNull('telegram_chat_id')->get();
        $this->info('Tìm thấy '.$users->count().' người dùng đã liên kết Telegram.');

        foreach ($users as $user) {
            $this->info("Đang kiểm tra thiết bị của người dùng: {$user->name}...");

            // Lấy tất cả thiết bị của các nhà mà người dùng tham gia
            $devices = Device::whereHas('room.home.members', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->with(['room.home'])->get();

            $warrantyAlerts = [];
            $maintenanceAlerts = [];

            foreach ($devices as $device) {
                $today = today();

                // 1. Kiểm tra cảnh báo Bảo hành
                $expiresAt = $device->warranty_expires_at;
                if ($expiresAt) {
                    // diffInDays(..., false) trả về số âm nếu đã qua hạn
                    $daysDiff = $today->diffInDays($expiresAt, false);

                    if ($daysDiff === 30) {
                        $warrantyAlerts[] = "📅 Thiết bị *{$device->name}* sẽ hết hạn bảo hành sau *30 ngày* (ngày {$expiresAt->format('d/m/Y')}).";
                    } elseif ($daysDiff === 7) {
                        $warrantyAlerts[] = "⚠️ Thiết bị *{$device->name}* sẽ hết hạn bảo hành sau *7 ngày* (ngày {$expiresAt->format('d/m/Y')}).";
                    } elseif ($daysDiff === 0) {
                        $warrantyAlerts[] = "🚨 Thiết bị *{$device->name}* *HẾT HẠN BẢO HÀNH HÔM NAY* (ngày {$expiresAt->format('d/m/Y')})!";
                    }
                }

                // 2. Kiểm tra cảnh báo Bảo trì định kỳ
                $nextMaintenance = $device->next_maintenance_at;
                if ($nextMaintenance) {
                    $daysDiff = $today->diffInDays($nextMaintenance, false);

                    if ($daysDiff === 7) {
                        $maintenanceAlerts[] = "🔧 Thiết bị *{$device->name}* cần bảo dưỡng định kỳ sau *7 ngày nữa* (ngày {$nextMaintenance->format('d/m/Y')}).";
                    } elseif ($daysDiff === 3) {
                        $maintenanceAlerts[] = "⚠️ Thiết bị *{$device->name}* cần bảo dưỡng định kỳ sau *3 ngày nữa* (ngày {$nextMaintenance->format('d/m/Y')}).";
                    } elseif ($daysDiff === 0) {
                        $maintenanceAlerts[] = "🚨 Thiết bị *{$device->name}* *ĐÃ ĐẾN HẠN BẢO DƯỠNG HÔM NAY*!";
                    } elseif ($daysDiff < 0) {
                        // Nhắc nhở quá hạn bảo dưỡng mỗi 15 ngày
                        $absDays = abs($daysDiff);
                        if ($absDays === 1 || $absDays % 15 === 0) {
                            $maintenanceAlerts[] = "🛑 Thiết bị *{$device->name}* đã *QUÁ HẠN BẢO DƯỠNG {$absDays} ngày* (hạn: {$nextMaintenance->format('d/m/Y')}). Vui lòng bảo dưỡng sớm để kéo dài tuổi thọ thiết bị.";
                        }
                    }
                }
            }

            // Gửi tin nhắn nếu có cảnh báo
            if (! empty($warrantyAlerts) || ! empty($maintenanceAlerts)) {
                $msg = "🔔 *THÔNG BÁO THIẾT BỊ HOMEWATT*\n\n";

                if (! empty($warrantyAlerts)) {
                    $msg .= "ℹ️ *Thông tin bảo hành:*\n".implode("\n", $warrantyAlerts)."\n\n";
                }

                if (! empty($maintenanceAlerts)) {
                    $msg .= "🛠️ *Lịch bảo trì định kỳ:*\n".implode("\n", $maintenanceAlerts)."\n\n";
                }

                $msg .= "💡 _Gợi ý: Sau khi thực hiện sửa chữa hoặc bảo dưỡng, hãy nhấn 'Ghi nhận sửa chữa' trên website hoặc điền lịch sử để đặt lại chu kỳ bảo dưỡng._";

                $this->sendMessage($token, $user->telegram_chat_id, $msg);
                $this->info("-> Đã gửi cảnh báo tới Telegram của {$user->name}.");
            }
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
