<?php

namespace Modules\Notification\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultNotificationTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'code' => 'budget_80_percent',
                'name' => 'Budget 80% Alert',
                'channels' => json_encode(['telegram', 'in_app']),
                'mail_subject' => null,
                'mail_body' => null,
                'telegram_body' => "Warning: Your {{category_name}} budget for {{home_name}} has reached 80%.\n\nSpent: {{spent_amount}}\nBudget: {{budget_amount}}\nRemaining: {{remaining_amount}}",
                'push_title' => 'Budget 80% Alert',
                'push_body' => 'Your {{category_name}} budget for {{home_name}} has reached 80%. Spent: {{spent_amount}} of {{budget_amount}}.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'budget_100_percent',
                'name' => 'Budget Exceeded Alert',
                'channels' => json_encode(['telegram', 'in_app', 'push']),
                'mail_subject' => null,
                'mail_body' => null,
                'telegram_body' => "Alert: Your {{category_name}} budget for {{home_name}} has been exceeded!\n\nSpent: {{spent_amount}}\nBudget: {{budget_amount}}\nOverspent: {{overspent_amount}}",
                'push_title' => 'Budget Exceeded!',
                'push_body' => 'Your {{category_name}} budget for {{home_name}} has been exceeded. Overspent by {{overspent_amount}}.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'energy_anomaly',
                'name' => 'Energy Spike Detected',
                'channels' => json_encode(['telegram', 'in_app']),
                'mail_subject' => null,
                'mail_body' => null,
                'telegram_body' => "Energy anomaly detected at {{home_name}}!\n\nDevice: {{device_name}}\nCurrent usage: {{current_usage}}\nNormal average: {{average_usage}}\nSpike: {{spike_percentage}}% above normal",
                'push_title' => 'Energy Anomaly Detected',
                'push_body' => '{{device_name}} at {{home_name}} is using {{spike_percentage}}% more energy than usual.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'goal_completed',
                'name' => 'Goal Achieved',
                'channels' => json_encode(['telegram', 'in_app', 'push']),
                'mail_subject' => null,
                'mail_body' => null,
                'telegram_body' => "Congratulations! You've achieved your goal: {{goal_name}} at {{home_name}}.\n\nTarget: {{target_value}}\nAchieved: {{achieved_value}}",
                'push_title' => 'Goal Achieved!',
                'push_body' => 'Congratulations! You have achieved your goal: {{goal_name}}.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'weekly_summary',
                'name' => 'Weekly Summary',
                'channels' => json_encode(['telegram', 'mail']),
                'mail_subject' => 'Weekly Energy Summary for {{home_name}}',
                'mail_body' => "Hello,\n\nHere is your weekly energy summary for {{home_name}}:\n\nTotal consumption: {{total_consumption}}\nTotal cost: {{total_cost}}\nCompared to last week: {{comparison_percentage}}%\n\nTop devices:\n{{top_devices}}\n\nView full report: {{report_url}}",
                'telegram_body' => "Weekly Summary for {{home_name}}:\n\nTotal: {{total_consumption}} ({{total_cost}})\nvs last week: {{comparison_percentage}}%\n\nTop device: {{top_device}}\n\n{{report_url}}",
                'push_title' => null,
                'push_body' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($templates as $template) {
            DB::table('notification_templates')->updateOrInsert(
                ['code' => $template['code']],
                $template
            );
        }
    }
}
