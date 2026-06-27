<?php

namespace Modules\Expense\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Expense\Models\Expense;
use Modules\Wallet\Models\Wallet;
use App\Support\AuditLogger;

class ExpenseService
{
    /**
     * Create an expense and update wallet balance atomically.
     */
    public function createExpense(array $data, User $user): Expense
    {
        return DB::transaction(function () use ($data, $user) {
            $wallet = Wallet::lockForUpdate()->findOrFail($data['wallet_id']);

            // Verify wallet belongs to home
            abort_unless((int) $wallet->home_id === (int) $data['home_id'], 403);

            $expense = Expense::create([
                ...$data,
                'user_id' => $user->id,
                'currency' => $data['currency'] ?? $wallet->currency,
                'occurred_at' => $data['occurred_at'] ?? now(),
            ]);

            $this->updateWalletBalance($wallet, $expense);

            AuditLogger::log('expense.created', [
                'expense_id' => $expense->id,
                'home_id' => $expense->home_id,
                'wallet_id' => $expense->wallet_id,
                'amount' => (float) $expense->amount,
                'type' => $expense->type,
                'description' => $expense->description,
            ]);

            return $expense->fresh(['wallet', 'category']);
        });
    }

    public function updateExpense(Expense $expense, array $data): Expense
    {
        return DB::transaction(function () use ($expense, $data) {
            $locked = Expense::where('id', $expense->id)->lockForUpdate()->first();
            if (! $locked) {
                abort(404);
            }

            if ($locked->belongsToTransfer()) {
                abort(403, 'Cannot edit expenses linked to a transfer');
            }

            $oldWallet = $locked->wallet;
            $oldType = $locked->type;
            $oldAmount = (float) $locked->amount;

            $locked->update($data);

            // If wallet changed, revert old and apply new
            $newWallet = Wallet::lockForUpdate()->find($locked->wallet_id);
            if ($oldWallet->id !== $newWallet->id) {
                $this->applyToBalance($oldWallet, $oldType, -$oldAmount);
                $this->applyToBalance($newWallet, $locked->type, (float) $locked->amount);
            } elseif ($oldType !== $locked->type || $oldAmount !== (float) $locked->amount) {
                // Revert old, apply new
                $this->applyToBalance($oldWallet, $oldType, -$oldAmount);
                $this->applyToBalance($newWallet, $locked->type, (float) $locked->amount);
            }

            AuditLogger::log('expense.updated', [
                'expense_id' => $locked->id,
                'home_id' => $locked->home_id,
                'old' => [
                    'wallet_id' => $oldWallet->id,
                    'amount' => $oldAmount,
                    'type' => $oldType,
                ],
                'new' => [
                    'wallet_id' => $locked->wallet_id,
                    'amount' => (float) $locked->amount,
                    'type' => $locked->type,
                    'description' => $locked->description,
                ],
            ]);

            return $locked->fresh();
        });
    }

    public function deleteExpense(Expense $expense): void
    {
        DB::transaction(function () use ($expense) {
            $locked = Expense::where('id', $expense->id)->lockForUpdate()->first();
            if (! $locked) {
                abort(404);
            }

            if ($locked->belongsToTransfer()) {
                abort(403, 'Cannot delete expenses linked to a transfer');
            }

            $wallet = Wallet::lockForUpdate()->find($locked->wallet_id);

            $this->applyToBalance($wallet, $locked->type, -(float) $locked->amount);

            AuditLogger::log('expense.deleted', [
                'expense_id' => $locked->id,
                'home_id' => $locked->home_id,
                'wallet_id' => $locked->wallet_id,
                'amount' => (float) $locked->amount,
                'type' => $locked->type,
            ]);

            $locked->delete();
        });
    }

    protected function updateWalletBalance(Wallet $wallet, Expense $expense): void
    {
        $delta = $expense->isIncome() ? (float) $expense->amount : -(float) $expense->amount;
        $wallet->forceFill(['balance' => (float) $wallet->balance + $delta])->save();
    }

    protected function applyToBalance(Wallet $wallet, string $type, float $delta): void
    {
        $signedDelta = $type === Expense::TYPE_INCOME ? $delta : -$delta;
        $wallet->forceFill(['balance' => (float) $wallet->balance + $signedDelta])->save();
    }
}
