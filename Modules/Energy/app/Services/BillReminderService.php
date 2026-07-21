<?php

namespace Modules\Energy\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Energy\Models\EnergyBill;
use Modules\Home\Models\Home;
use Modules\Notification\Services\NotificationService;

class BillReminderService
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Scan all unpaid bills due within the next 3 days that haven't had a reminder sent yet.
     * Group bills by home and notify all home members.
     */
    public function sendDueReminders(): int
    {
        $today = Carbon::today();
        $threeDaysFromNow = Carbon::today()->addDays(3);

        $bills = EnergyBill::query()
            ->where('is_paid', false)
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$today, $threeDaysFromNow])
            ->whereNull('reminder_sent_at')
            ->with('home.members.user')
            ->get();

        if ($bills->isEmpty()) {
            Log::info('BillReminder: No due bills found for reminder.');

            return 0;
        }

        $sent = 0;

        // Group bills by home
        $billsByHome = $bills->groupBy('home_id');

        foreach ($billsByHome as $homeId => $homeBills) {
            $home = Home::find($homeId);
            if (! $home) {
                continue;
            }

            $members = $home->members()->with('user')->get();

            foreach ($members as $member) {
                foreach ($homeBills as $bill) {
                    $this->notificationService->send(
                        'bill_reminder',
                        $member->user,
                        [
                            'home_name' => $home->name,
                            'provider' => $bill->provider ?? 'Unknown',
                            'amount' => number_format($bill->amount, 0, ',', '.').' '.$bill->currency,
                            'due_date' => $bill->due_date ? Carbon::parse($bill->due_date)->format('d/m/Y') : 'N/A',
                            'billing_period' => $bill->billing_period ?? 'N/A',
                        ],
                        $home->id,
                    );
                }
            }

            // Mark all bills in this home as reminded
            EnergyBill::whereIn('id', $homeBills->pluck('id'))
                ->update(['reminder_sent_at' => now()]);

            $sent += $homeBills->count();
        }

        Log::info('BillReminder: Sent due reminders.', ['count' => $sent]);

        return $sent;
    }

    /**
     * Scan all unpaid bills that are past their due date.
     * Group bills by home and notify all home members.
     */
    public function sendOverdueReminders(): int
    {
        $today = Carbon::today();

        $bills = EnergyBill::query()
            ->where('is_paid', false)
            ->whereNotNull('due_date')
            ->where('due_date', '<', $today)
            ->with('home.members.user')
            ->get();

        if ($bills->isEmpty()) {
            Log::info('BillReminder: No overdue bills found.');

            return 0;
        }

        $sent = 0;

        // Group bills by home
        $billsByHome = $bills->groupBy('home_id');

        foreach ($billsByHome as $homeId => $homeBills) {
            $home = Home::find($homeId);
            if (! $home) {
                continue;
            }

            $members = $home->members()->with('user')->get();

            foreach ($members as $member) {
                foreach ($homeBills as $bill) {
                    $daysOverdue = Carbon::parse($bill->due_date)->diffInDays($today);

                    $this->notificationService->send(
                        'bill_overdue',
                        $member->user,
                        [
                            'home_name' => $home->name,
                            'provider' => $bill->provider ?? 'Unknown',
                            'amount' => number_format($bill->amount, 0, ',', '.').' '.$bill->currency,
                            'due_date' => Carbon::parse($bill->due_date)->format('d/m/Y'),
                            'days_overdue' => (string) $daysOverdue,
                            'billing_period' => $bill->billing_period ?? 'N/A',
                        ],
                        $home->id,
                    );
                }
            }

            $sent += $homeBills->count();
        }

        Log::info('BillReminder: Sent overdue reminders.', ['count' => $sent]);

        return $sent;
    }
}
