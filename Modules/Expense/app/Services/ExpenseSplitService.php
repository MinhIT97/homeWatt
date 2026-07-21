<?php

namespace Modules\Expense\Services;

use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseSplit;

class ExpenseSplitService
{
    /**
     * Split an expense among multiple members.
     *
     * @param  array<int, array<string, mixed>>  $splits  [['user_id' => 2, 'amount' => 50000], ...]
     * @return array<int, ExpenseSplit>
     */
    public function split(Expense $expense, array $splits, User $payer): array
    {
        return DB::transaction(function () use ($expense, $splits, $payer) {
            $created = [];

            foreach ($splits as $splitData) {
                $created[] = ExpenseSplit::create([
                    'expense_id' => $expense->id,
                    'home_id' => $expense->home_id,
                    'paid_by' => $payer->id,
                    'owed_by' => (int) $splitData['user_id'],
                    'amount' => (float) $splitData['amount'],
                    'paid_amount' => 0,
                    'status' => ExpenseSplit::STATUS_PENDING,
                ]);
            }

            AuditLogger::log('expense.split', [
                'expense_id' => $expense->id,
                'home_id' => $expense->home_id,
                'paid_by' => $payer->id,
                'split_count' => count($created),
                'total_split' => array_sum(array_column($splits, 'amount')),
            ]);

            return $created;
        });
    }

    /**
     * Get all debts for a user in a home.
     *
     * @return array{owes: Collection, owed_to_you: Collection}
     */
    public function getDebts(int $homeId, User $user): array
    {
        $userId = $user->id;

        $owes = ExpenseSplit::byHome($homeId)
            ->where('owed_by', $userId)
            ->where('status', '!=', ExpenseSplit::STATUS_SETTLED)
            ->with(['expense', 'payer'])
            ->latest()
            ->get();

        $owedToYou = ExpenseSplit::byHome($homeId)
            ->where('paid_by', $userId)
            ->where('status', '!=', ExpenseSplit::STATUS_SETTLED)
            ->with(['expense', 'ower'])
            ->latest()
            ->get();

        return [
            'owes' => $owes,
            'owed_to_you' => $owedToYou,
        ];
    }

    /**
     * Get net balance between two users in a home.
     * Positive means user2 owes user1; negative means user1 owes user2.
     */
    public function getBalance(int $homeId, User $user1, User $user2): float
    {
        $user1OwesUser2 = (float) ExpenseSplit::byHome($homeId)
            ->where('paid_by', $user2->id)
            ->where('owed_by', $user1->id)
            ->where('status', '!=', ExpenseSplit::STATUS_SETTLED)
            ->sum(DB::raw('amount - paid_amount'));

        $user2OwesUser1 = (float) ExpenseSplit::byHome($homeId)
            ->where('paid_by', $user1->id)
            ->where('owed_by', $user2->id)
            ->where('status', '!=', ExpenseSplit::STATUS_SETTLED)
            ->sum(DB::raw('amount - paid_amount'));

        return $user2OwesUser1 - $user1OwesUser2;
    }

    /**
     * Settle a split (mark as paid).
     */
    public function settle(ExpenseSplit $split): void
    {
        DB::transaction(function () use ($split) {
            $locked = ExpenseSplit::where('id', $split->id)->lockForUpdate()->first();

            if (! $locked) {
                abort(404);
            }

            if ($locked->isSettled()) {
                return;
            }

            $locked->settle();

            AuditLogger::log('expense.split.settled', [
                'split_id' => $locked->id,
                'expense_id' => $locked->expense_id,
                'home_id' => $locked->home_id,
                'paid_by' => $locked->paid_by,
                'owed_by' => $locked->owed_by,
                'amount' => (float) $locked->amount,
            ]);
        });
    }

    /**
     * Get home debt summary: how much each user owes/is owed.
     *
     * @return array<int, array{user: User, total_owes: float, total_owed_to_you: float, net: float}>
     */
    public function getHomeSummary(int $homeId): array
    {
        $splits = ExpenseSplit::byHome($homeId)
            ->where('status', '!=', ExpenseSplit::STATUS_SETTLED)
            ->with(['payer', 'ower'])
            ->get();

        $summary = [];

        foreach ($splits as $split) {
            $payerId = $split->paid_by;
            $owerId = $split->owed_by;
            $remaining = $split->remaining();

            if (! isset($summary[$payerId])) {
                $summary[$payerId] = [
                    'user' => $split->payer,
                    'total_owed_to_you' => 0,
                    'total_owes' => 0,
                    'net' => 0,
                ];
            }

            if (! isset($summary[$owerId])) {
                $summary[$owerId] = [
                    'user' => $split->ower,
                    'total_owed_to_you' => 0,
                    'total_owes' => 0,
                    'net' => 0,
                ];
            }

            $summary[$payerId]['total_owed_to_you'] += $remaining;
            $summary[$payerId]['net'] += $remaining;
            $summary[$owerId]['total_owes'] += $remaining;
            $summary[$owerId]['net'] -= $remaining;
        }

        return $summary;
    }
}
