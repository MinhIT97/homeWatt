<?php

namespace Modules\Automation\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Modules\Automation\Models\AutomationRule;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Notification\Services\NotificationService;
use Modules\Wallet\Models\Wallet;

class ActionExecutor
{
    public function __construct(
        private NotificationService $notificationService,
    ) {}

    /**
     * Execute all actions defined in a rule.
     */
    public function execute(AutomationRule $rule, array $context): array
    {
        $results = [];

        foreach ($rule->actions as $action) {
            $results[] = $this->executeSingle($action, $rule, $context);
        }

        return $results;
    }

    private function executeSingle(array $action, AutomationRule $rule, array $context): array
    {
        $type = $action['type'] ?? '';
        $config = $action['config'] ?? [];

        try {
            $result = match ($type) {
                'add_note' => $this->addNote($config, $context),
                'set_category' => $this->setCategory($config, $context),
                'send_notification' => $this->sendNotification($config, $rule, $context),
                'tag_expense' => $this->tagExpense($config, $context),
                'send_telegram' => $this->sendTelegram($config, $rule, $context),
                default => ['ok' => false, 'error' => "Unknown action type: {$type}"],
            };

            return ['type' => $type, 'ok' => true, 'result' => $result];
        } catch (\Throwable $e) {
            Log::error("Automation action failed: {$type}", [
                'rule_id' => $rule->id,
                'error' => $e->getMessage(),
            ]);

            return ['type' => $type, 'ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Add a note/description to an expense.
     * Config: { text: "Cần review", append: true }
     */
    private function addNote(array $config, array $context): array
    {
        $expenseId = $context['expense_id'] ?? null;
        if (! $expenseId) return ['ok' => false, 'error' => 'No expense_id in context'];

        $expense = Expense::find($expenseId);
        if (! $expense) return ['ok' => false, 'error' => 'Expense not found'];

        $note = $config['text'] ?? '';
        $append = $config['append'] ?? true;

        $newNotes = $append
            ? trim(($expense->notes ? $expense->notes . ' | ' : '') . $note)
            : $note;

        $expense->forceFill(['notes' => $newNotes])->save();

        return ['expense_id' => $expense->id, 'note' => $newNotes];
    }

    /**
     * Auto-categorize an expense.
     * Config: { category_id: 5 }
     */
    private function setCategory(array $config, array $context): array
    {
        $expenseId = $context['expense_id'] ?? null;
        $categoryId = $config['category_id'] ?? null;
        if (! $expenseId || ! $categoryId) return ['ok' => false, 'error' => 'Missing expense_id or category_id'];

        $expense = Expense::find($expenseId);
        if (! $expense) return ['ok' => false, 'error' => 'Expense not found'];

        $expense->forceFill(['category_id' => (int) $categoryId])->save();

        return ['expense_id' => $expense->id, 'category_id' => $categoryId];
    }

    /**
     * Tag an expense with a reference label.
     * Config: { tag: "Cần review" }
     */
    private function tagExpense(array $config, array $context): array
    {
        $expenseId = $context['expense_id'] ?? null;
        $tag = $config['tag'] ?? '';
        if (! $expenseId || ! $tag) return ['ok' => false, 'error' => 'Missing expense_id or tag'];

        $expense = Expense::find($expenseId);
        if (! $expense) return ['ok' => false, 'error' => 'Expense not found'];

        $existingRef = $expense->reference ?: '';
        $newRef = $existingRef ? $existingRef . ', ' . $tag : $tag;
        $expense->forceFill(['reference' => $newRef])->save();

        return ['expense_id' => $expense->id, 'tag' => $tag];
    }

    /**
     * Send in-app notification via NotificationService.
     * Config: { template: "budget_80_percent", data: {...} }
     */
    private function sendNotification(array $config, AutomationRule $rule, array $context): array
    {
        $template = $config['template'] ?? 'budget_80_percent';
        $data = array_merge($context, $config['data'] ?? []);

        $user = User::find($rule->user_id);
        if (! $user) return ['ok' => false, 'error' => 'User not found'];

        $this->notificationService->send($template, $user, $data, $rule->home_id);

        return ['template' => $template, 'user_id' => $user->id];
    }

    /**
     * Send Telegram message directly.
     * Config: { text: "Cảnh báo: ..." }
     */
    private function sendTelegram(array $config, AutomationRule $rule, array $context): array
    {
        $text = $config['text'] ?? '';
        if (empty($text)) return ['ok' => false, 'error' => 'No text configured'];

        $token = config('services.telegram.bot_token');
        if (empty($token)) return ['ok' => false, 'error' => 'Telegram token not configured'];

        $user = User::find($rule->user_id);
        if (! $user || ! $user->telegram_chat_id) return ['ok' => false, 'error' => 'User not linked to Telegram'];

        // Replace variables in text
        foreach ($context as $key => $value) {
            if (is_scalar($value)) {
                $text = str_replace('{{' . $key . '}}', (string) $value, $text);
            }
        }

        \Illuminate\Support\Facades\Http::timeout(5)
            ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $user->telegram_chat_id,
                'text' => $text,
                'parse_mode' => 'Markdown',
            ]);

        return ['user_id' => $user->id, 'message' => $text];
    }
}
