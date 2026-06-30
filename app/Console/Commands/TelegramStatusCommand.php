<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramStatusCommand extends Command
{
    protected $signature = 'telegram:status {--set-webhook= : Thiết lập URL webhook mới (phải là https)}';

    protected $description = 'Kiểm tra trạng thái cấu hình và Webhook của Telegram Bot';

    public function handle()
    {
        $token = config('services.telegram.bot_token');
        $secret = config('services.telegram.webhook_secret');

        $this->info('=== KIỂM TRA CẤU HÌNH TELEGRAM ===');
        $this->line('TELEGRAM_BOT_TOKEN: '.($token ? 'Đã cấu hình (Mã ẩn: '.substr($token, 0, 6).'...)' : 'CHƯA CẤU HÌNH'));
        $this->line('TELEGRAM_WEBHOOK_SECRET: '.($secret ? 'Đã cấu hình' : 'CHƯA CẤU HÌNH (Khuyến nghị điền để bảo mật)'));

        if (! $token) {
            $this->error('Lỗi: Vui lòng điền TELEGRAM_BOT_TOKEN trong file .env trước!');

            return 1;
        }

        // 1. Kiểm tra thông tin Bot (getMe)
        $this->line("\n1. Đang kiểm tra kết nối tới Bot Telegram...");
        $response = Http::get("https://api.telegram.org/bot{$token}/getMe");

        if ($response->failed()) {
            $this->error('Không thể kết nối tới API Telegram. Lỗi: '.$response->body());
            $this->error('Vui lòng kiểm tra lại Token hoặc kết nối mạng/Proxy trên server.');

            return 1;
        }

        $botInfo = $response->json('result');
        $this->info('Kết nối thành công!');
        $this->line('  - Bot Name: '.$botInfo['first_name']);
        $this->line('  - Username: @'.$botInfo['username']);

        // 2. Thiết lập Webhook mới nếu truyền tham số --set-webhook
        $newWebhook = $this->option('set-webhook');
        if ($newWebhook) {
            if (! str_starts_with($newWebhook, 'https://')) {
                $this->error('Lỗi: URL Webhook phải sử dụng HTTPS!');

                return 1;
            }

            $this->line("\nĐang thiết lập webhook mới tới: ".$newWebhook);

            $setupParams = [
                'url' => $newWebhook,
            ];
            if ($secret) {
                $setupParams['secret_token'] = $secret;
            }

            $setupResponse = Http::post("https://api.telegram.org/bot{$token}/setWebhook", $setupParams);

            if ($setupResponse->failed()) {
                $this->error('Không thể cài đặt webhook. Lỗi: '.$setupResponse->body());
            } else {
                $this->info('Cài đặt Webhook thành công!');
            }
        }

        // 3. Kiểm tra trạng thái Webhook hiện tại (getWebhookInfo)
        $this->line("\n2. Đang kiểm tra trạng thái Webhook từ Telegram...");
        $webhookResponse = Http::get("https://api.telegram.org/bot{$token}/getWebhookInfo");

        if ($webhookResponse->failed()) {
            $this->error('Không thể truy vấn trạng thái webhook. Lỗi: '.$webhookResponse->body());

            return 1;
        }

        $info = $webhookResponse->json('result');
        $this->line('  - URL hiện tại: '.($info['url'] ?: 'Chưa được thiết lập (Bot đang ở chế độ Polling)'));

        if ($info['url']) {
            $this->line('  - Số tin nhắn đang chờ (Pending): '.$info['pending_update_count']);

            if (isset($info['last_error_date'])) {
                $errorDate = Carbon::createFromTimestamp($info['last_error_date'])->toDateTimeString();
                $this->warn("  - Lỗi gửi tin nhắn gần nhất ({$errorDate}): ".($info['last_error_message'] ?? 'Không rõ'));
                $this->warn('  - Gợi ý: Hãy kiểm tra SSL/HTTPS trên server có hợp lệ không, hoặc file log Laravel xem có lỗi PHP/DB nào chặn webhook không.');
            } else {
                $this->info('  - Trạng thái truyền nhận tin nhắn: Hoạt động bình thường (Không có lỗi gần đây).');
            }
        } else {
            $this->warn('  - Gợi ý: Bạn cần chạy lệnh sau để đăng ký webhook: ');
            $this->line('    php artisan telegram:status --set-webhook=https://ten-mien-cua-ban.com/api/v1/telegram/webhook');
        }

        return 0;
    }
}
