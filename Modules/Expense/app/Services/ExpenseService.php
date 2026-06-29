<?php

namespace Modules\Expense\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Expense\Models\Expense;
use Modules\Energy\Models\EnergyBill;
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

            try {
                $this->checkBudgetsAndAlert($expense);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Error checking budget alerts: ' . $e->getMessage());
            }

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

            $oldWalletId = (int) $locked->getOriginal('wallet_id');
            $oldType = $locked->type;
            $oldAmount = (float) $locked->amount;

            $locked->update($data);

            $newWalletId = (int) $locked->wallet_id;

            $walletIds = collect([$oldWalletId, $newWalletId])->unique()->sort()->values();
            $lockedWallets = Wallet::whereIn('id', $walletIds)->lockForUpdate()->get()->keyBy('id');

            $oldWallet = $lockedWallets->get($oldWalletId);
            $newWallet = $lockedWallets->get($newWalletId);

            if ($oldWalletId !== $newWalletId) {
                $this->applyToBalance($oldWallet, $oldType, -$oldAmount);
                $this->applyToBalance($newWallet, $locked->type, (float) $locked->amount);
            } elseif ($oldType !== $locked->type || $oldAmount !== (float) $locked->amount) {
                $this->applyToBalance($oldWallet, $oldType, -$oldAmount);
                $this->applyToBalance($newWallet, $locked->type, (float) $locked->amount);
            }

            try {
                $this->checkBudgetsAndAlert($locked);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Error checking budget alerts on update: ' . $e->getMessage());
            }

            AuditLogger::log('expense.updated', [
                'expense_id' => $locked->id,
                'home_id' => $locked->home_id,
                'old' => [
                    'wallet_id' => $oldWalletId,
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

            EnergyBill::where('expense_id', $locked->id)->delete();
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

    public function checkBudgetsAndAlert(Expense $expense): void
    {
        if ($expense->type !== Expense::TYPE_EXPENSE) {
            return;
        }

        $homeId = $expense->home_id;
        $categoryId = $expense->category_id;
        $month = $expense->occurred_at ? $expense->occurred_at->format('Y-m') : now()->format('Y-m');
        $amount = (float) $expense->amount;

        // Find budgets (category specific & global)
        $budgets = \Modules\Expense\Models\ExpenseBudget::where('home_id', $homeId)
            ->where('month', $month)
            ->where(function($q) use ($categoryId) {
                $q->where('category_id', $categoryId)
                  ->orWhereNull('category_id');
            })
            ->get();

        if ($budgets->isEmpty()) {
            return;
        }

        // Get total spending in this month for this category and overall
        $occurredDate = $expense->occurred_at ?: now();
        $startOfMonth = $occurredDate->copy()->startOfMonth();
        $endOfMonth = $occurredDate->copy()->endOfMonth();

        $monthlyExpenses = Expense::where('home_id', $homeId)
            ->where('type', 'expense')
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
            ->get();

        $categorySpending = (float) $monthlyExpenses->where('category_id', $categoryId)->sum('amount');
        $totalSpending = (float) $monthlyExpenses->sum('amount');

        $token = config('services.telegram.bot_token');
        if (empty($token)) {
            return;
        }

        // Find home members with telegram_chat_id
        $users = \App\Models\User::whereHas('homeMembers', fn($q) => $q->where('home_id', $homeId))
            ->whereNotNull('telegram_chat_id')
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        foreach ($budgets as $budget) {
            $limit = (float) $budget->amount;
            if ($limit <= 0) continue;

            $isGlobal = is_null($budget->category_id);
            $currentSpending = $isGlobal ? $totalSpending : $categorySpending;
            $prevSpending = $currentSpending - $amount;

            $prevPct = ($prevSpending / $limit) * 100;
            $newPct = ($currentSpending / $limit) * 100;

            $alertMessage = null;
            $catName = $isGlobal ? 'Tổng chi tiêu' : ($expense->category?->name ?: 'Danh mục');

            if ($prevPct < 100 && $newPct >= 100) {
                $alertMessage = "🚨 *CẢNH BÁO VƯỢT HẠN MỨC CHI TIÊU* 🚨\n\n"
                    . "• Danh mục: *{$catName}*\n"
                    . "• Hạn mức tháng: *" . number_format($limit, 0, ',', '.') . " đ*\n"
                    . "• Đã chi tiêu: *" . number_format($currentSpending, 0, ',', '.') . " đ* (Đạt *" . round($newPct, 1) . "%*)\n"
                    . "• Giao dịch gây vượt hạn mức: *{$expense->description}* (+" . number_format($amount, 0, ',', '.') . " đ)\n\n"
                    . "⚠️ Bạn đã chi tiêu quá hạn mức thiết lập cho tháng này!";
            } elseif ($prevPct < 80 && $newPct >= 80) {
                $alertMessage = "⚠️ *CẢNH BÁO SẮP ĐẠT HẠN MỨC CHI TIÊU* ⚠️\n\n"
                    . "• Danh mục: *{$catName}*\n"
                    . "• Hạn mức tháng: *" . number_format($limit, 0, ',', '.') . " đ*\n"
                    . "• Đã chi tiêu: *" . number_format($currentSpending, 0, ',', '.') . " đ* (Đạt *" . round($newPct, 1) . "%*)\n"
                    . "• Giao dịch gần nhất: *{$expense->description}* (+" . number_format($amount, 0, ',', '.') . " đ)\n\n"
                    . "💡 Vui lòng cân đối chi tiêu hợp lý.";
            }

            if ($alertMessage) {
                foreach ($users as $user) {
                    \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                        'chat_id' => $user->telegram_chat_id,
                        'text' => $alertMessage,
                        'parse_mode' => 'Markdown',
                    ]);
                }
            }
        }
    }
}
