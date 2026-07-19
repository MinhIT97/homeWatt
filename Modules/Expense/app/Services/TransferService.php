<?php

namespace Modules\Expense\Services;

use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Expense\Models\Transfer;
use Modules\Wallet\Models\Wallet;

class TransferService
{
    public function createTransfer(array $data, User $user): Transfer
    {
        return DB::transaction(function () use ($data, $user) {
            // Lock both wallets to prevent deadlock - lock in ID order
            $fromId = (int) $data['from_wallet_id'];
            $toId = (int) $data['to_wallet_id'];

            if ($fromId === $toId) {
                throw new \InvalidArgumentException('Cannot transfer to the same wallet');
            }

            $firstId = min($fromId, $toId);
            $secondId = max($fromId, $toId);

            $first = Wallet::lockForUpdate()->findOrFail($firstId);
            $second = Wallet::lockForUpdate()->findOrFail($secondId);

            $from = $first->id === $fromId ? $first : $second;
            $to = $first->id === $toId ? $first : $second;

            if ($from->type === Wallet::TYPE_CREDIT_CARD) {
                throw new \RuntimeException(__('expense.cannot_transfer_from_credit_card'));
            }

            abort_unless((int) $from->home_id === (int) $data['home_id'], 403);
            abort_unless((int) $to->home_id === (int) $data['home_id'], 403);

            $amount = (float) $data['amount'];
            $fee = (float) ($data['fee'] ?? 0);
            $totalOut = $amount + $fee;

            // Verify sufficient balance
            if ((float) $from->calculatedBalance() < $totalOut) {
                throw new \RuntimeException(__('expense.insufficient_balance'));
            }

            // Create transfer record
            $transfer = Transfer::create([
                'home_id' => $data['home_id'],
                'from_wallet_id' => $from->id,
                'to_wallet_id' => $to->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'fee' => $fee,
                'currency' => $data['currency'] ?? $from->currency,
                'description' => $data['description'] ?? null,
                'occurred_at' => $data['occurred_at'] ?? now(),
            ]);

            // Create 2 expense records (out + in) with type=transfer
            $outCategory = $this->getOrCreateTransferCategory($from->home_id, Expense::TYPE_EXPENSE);
            $inCategory = $this->getOrCreateTransferCategory($to->home_id, Expense::TYPE_INCOME);

            Expense::create([
                'home_id' => $from->home_id,
                'wallet_id' => $from->id,
                'category_id' => $outCategory->id,
                'user_id' => $user->id,
                'type' => Expense::TYPE_EXPENSE,
                'amount' => $amount,
                'currency' => $transfer->currency,
                'description' => $data['description'] ?? null,
                'occurred_at' => $transfer->occurred_at,
                'transfer_id' => $transfer->id,
            ]);

            Expense::create([
                'home_id' => $to->home_id,
                'wallet_id' => $to->id,
                'category_id' => $inCategory->id,
                'user_id' => $user->id,
                'type' => Expense::TYPE_INCOME,
                'amount' => $amount,
                'currency' => $transfer->currency,
                'description' => $data['description'] ?? null,
                'occurred_at' => $transfer->occurred_at,
                'transfer_id' => $transfer->id,
            ]);

            // Update balances
            $from->forceFill(['balance' => (float) $from->balance - $totalOut])->save();
            $to->forceFill(['balance' => (float) $to->balance + $amount])->save();

            // Create fee expense record so fee doesn't "disappear" from reports (M7)
            if ($fee > 0) {
                $feeCategory = $this->getOrCreateFeeCategory($from->home_id);

                Expense::create([
                    'home_id' => $from->home_id,
                    'wallet_id' => $from->id,
                    'category_id' => $feeCategory->id,
                    'user_id' => $user->id,
                    'type' => Expense::TYPE_EXPENSE,
                    'amount' => $fee,
                    'currency' => $transfer->currency,
                    'description' => __('expense.transfer_fee_description', [
                        'amount' => number_format($amount, 0, ',', '.'),
                        'to' => $to->name,
                    ]),
                    'occurred_at' => $transfer->occurred_at,
                    'transfer_id' => $transfer->id,
                ]);
            }

            AuditLogger::log('transfer.created', [
                'transfer_id' => $transfer->id,
                'home_id' => $transfer->home_id,
                'from_wallet_id' => $transfer->from_wallet_id,
                'to_wallet_id' => $transfer->to_wallet_id,
                'amount' => (float) $transfer->amount,
                'fee' => (float) $transfer->fee,
            ]);

            return $transfer->fresh(['fromWallet', 'toWallet']);
        });
    }

    public function reverseTransfer(Transfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            $locked = Transfer::where('id', $transfer->id)->lockForUpdate()->first();
            if (! $locked) {
                abort(404);
            }

            $fromId = $locked->from_wallet_id;
            $toId = $locked->to_wallet_id;

            $firstId = min($fromId, $toId);
            $secondId = max($fromId, $toId);

            $first = Wallet::lockForUpdate()->findOrFail($firstId);
            $second = Wallet::lockForUpdate()->findOrFail($secondId);

            $from = $first->id === $fromId ? $first : $second;
            $to = $first->id === $toId ? $first : $second;

            $amount = (float) $locked->amount;
            $fee = (float) $locked->fee;

            // Reverse: money goes back to from-wallet
            $from->forceFill(['balance' => (float) $from->balance + $amount + $fee])->save();
            $to->forceFill(['balance' => (float) $to->balance - $amount])->save();

            // Soft delete expenses
            Expense::where('transfer_id', $locked->id)->delete();

            AuditLogger::log('transfer.reversed', [
                'transfer_id' => $locked->id,
                'home_id' => $locked->home_id,
                'from_wallet_id' => $locked->from_wallet_id,
                'to_wallet_id' => $locked->to_wallet_id,
                'amount' => (float) $locked->amount,
            ]);

            $locked->delete();
        });
    }

    protected function getOrCreateTransferCategory(int $homeId, string $type)
    {
        $name = $type === Expense::TYPE_INCOME ? 'Chuyển tiền vào' : 'Chuyển tiền ra';

        try {
            return ExpenseCategory::firstOrCreate(
                ['home_id' => $homeId, 'name' => $name, 'type' => $type],
                [
                    'category_group' => ExpenseCategory::GROUP_TRANSFER,
                    'icon' => $type === Expense::TYPE_INCOME ? '⬇️' : '⬆️',
                    'color' => '#6b7280',
                    'is_system' => true,
                ]
            );
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            return ExpenseCategory::where('home_id', $homeId)
                ->where('name', $name)
                ->where('type', $type)
                ->firstOrFail();
        }
    }

    protected function getOrCreateFeeCategory(int $homeId): ExpenseCategory
    {
        try {
            return ExpenseCategory::firstOrCreate(
                ['home_id' => $homeId, 'name' => 'Phí chuyển khoản', 'type' => Expense::TYPE_EXPENSE],
                [
                    'category_group' => ExpenseCategory::GROUP_TRANSFER,
                    'icon' => '💸',
                    'color' => '#ef4444',
                    'is_system' => true,
                ]
            );
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            return ExpenseCategory::where('home_id', $homeId)
                ->where('name', 'Phí chuyển khoản')
                ->where('type', Expense::TYPE_EXPENSE)
                ->firstOrFail();
        }
    }
}
