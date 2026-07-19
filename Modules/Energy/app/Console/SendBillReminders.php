<?php

namespace Modules\Energy\Console;

use Illuminate\Console\Command;
use Modules\Energy\Services\BillReminderService;

class SendBillReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:send-bills';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send bill due and overdue reminders to home members';

    /**
     * Execute the console command.
     */
    public function handle(BillReminderService $service): int
    {
        $this->info('Scanning for due bill reminders...');
        $dueCount = $service->sendDueReminders();
        $this->info("Sent {$dueCount} due bill reminder(s).");

        $this->info('Scanning for overdue bills...');
        $overdueCount = $service->sendOverdueReminders();
        $this->info("Sent {$overdueCount} overdue bill reminder(s).");

        return self::SUCCESS;
    }
}
