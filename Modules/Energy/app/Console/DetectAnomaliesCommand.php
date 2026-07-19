<?php

namespace Modules\Energy\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Energy\Services\AnomalyDetector;
use Modules\Home\Models\Home;

class DetectAnomaliesCommand extends Command
{
    protected $signature = 'energy:detect-anomalies {--home-id= : Specific home ID to check} {--notify : Send notifications for detected anomalies}';

    protected $description = 'Detect energy anomalies across all homes and optionally send alerts';

    public function handle(AnomalyDetector $detector): int
    {
        if ($homeId = $this->option('home-id')) {
            $homes = Home::where('id', $homeId)->get();
        } else {
            $homes = Home::all();
        }

        $totalAnomalies = 0;
        $totalSpikes = 0;

        foreach ($homes as $home) {
            $this->info("Checking home: {$home->name} (ID: {$home->id})");

            try {
                $result = $detector->detectHomeAnomalies($home->id);

                $totalAnomalies += count($result['device_anomalies']);
                $totalSpikes += count($result['hourly_spikes']);

                $this->info('  Device anomalies: '.count($result['device_anomalies']));
                $this->info('  Hourly spikes: '.count($result['hourly_spikes']));

                // Log high severity anomalies
                foreach ($result['device_anomalies'] as $anomaly) {
                    if (($anomaly['severity'] ?? '') === 'high') {
                        $this->warn("  HIGH: {$anomaly['device_name']} — {$anomaly['recommendation']}");
                        Log::warning('Energy anomaly detected', [
                            'home_id' => $home->id,
                            'device_id' => $anomaly['device_id'] ?? null,
                            'device_name' => $anomaly['device_name'] ?? 'Unknown',
                            'severity' => $anomaly['severity'] ?? 'unknown',
                            'ratio' => $anomaly['ratio'] ?? null,
                            'recommendation' => $anomaly['recommendation'] ?? '',
                        ]);
                    }
                }

                // Send notifications if --notify flag is set
                if ($this->option('notify') && ! empty($result['device_anomalies'])) {
                    $this->sendNotifications($home, $result);
                }
            } catch (\Throwable $e) {
                $this->error("  Error checking home {$home->id}: {$e->getMessage()}");
                Log::error('Energy anomaly detection failed', [
                    'home_id' => $home->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info('');
        $this->info("Done. Total device anomalies: {$totalAnomalies}, Total hourly spikes: {$totalSpikes}");

        return Command::SUCCESS;
    }

    /**
     * Send notifications to home members about detected anomalies.
     */
    protected function sendNotifications(Home $home, array $result): void
    {
        $highSeverity = array_filter($result['device_anomalies'], fn ($a) => ($a['severity'] ?? '') === 'high');

        if (empty($highSeverity)) {
            return;
        }

        // Get home members with Telegram linked
        $users = $home->members()
            ->whereHas('user', fn ($q) => $q->whereNotNull('telegram_chat_id'))
            ->with('user')
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        $token = config('services.telegram.bot_token');
        if (empty($token)) {
            return;
        }

        $deviceNames = array_map(fn ($a) => $a['device_name'] ?? 'Unknown', $highSeverity);
        $deviceList = implode(', ', array_unique($deviceNames));

        $message = "⚡ *CẢNH BÁO BẤT THƯỜNG NĂNG LƯỢNG*\n\n"
            ."Nhà: *{$home->name}*\n"
            .'Số thiết bị bất thường: *'.count($highSeverity)."*\n"
            ."Thiết bị: *{$deviceList}*\n\n"
            ."Vui lòng kiểm tra dashboard để biết chi tiết và nhận đề xuất khắc phục.\n"
            .'[Xem Dashboard]('.route('dashboard', ['home_id' => $home->id]).')';

        foreach ($users as $member) {
            try {
                Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $member->user->telegram_chat_id,
                    'text' => $message,
                    'parse_mode' => 'Markdown',
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to send anomaly notification', [
                    'home_id' => $home->id,
                    'user_id' => $member->user_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
