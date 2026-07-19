<?php

namespace Modules\Automation\Services;

use Illuminate\Support\Facades\Log;
use Modules\Automation\Models\AutomationLog;
use Modules\Automation\Models\AutomationRule;

class AutomationEngine
{
    /**
     * Supported trigger events and their context fields.
     */
    public const EVENTS = [
        'expense.created' => [
            'label' => 'Khi có giao dịch mới',
            'fields' => ['amount', 'type', 'category_id', 'wallet_id', 'description', 'home_id', 'expense_id', 'user_id'],
        ],
        'expense.updated' => [
            'label' => 'Khi giao dịch được sửa',
            'fields' => ['amount', 'type', 'category_id', 'wallet_id', 'description', 'home_id', 'expense_id', 'user_id'],
        ],
        'budget.exceeded' => [
            'label' => 'Khi vượt hạn mức ngân sách',
            'fields' => ['category_id', 'category_name', 'limit', 'current_spending', 'percentage', 'home_id'],
        ],
        'goal.completed' => [
            'label' => 'Khi hoàn thành mục tiêu',
            'fields' => ['goal_name', 'goal_type', 'target_amount', 'current_amount', 'home_id'],
        ],
        'energy.anomaly' => [
            'label' => 'Khi phát hiện bất thường năng lượng',
            'fields' => ['device_name', 'severity', 'ratio', 'recommendation', 'home_id'],
        ],
        'wallet.low_balance' => [
            'label' => 'Khi số dư ví xuống thấp',
            'fields' => ['wallet_name', 'balance', 'threshold', 'home_id'],
        ],
    ];

    /**
     * Available action types for the UI.
     */
    public const ACTION_TYPES = [
        'add_note' => ['label' => 'Thêm ghi chú', 'config_fields' => ['text']],
        'set_category' => ['label' => 'Đổi danh mục', 'config_fields' => ['category_id']],
        'tag_expense' => ['label' => 'Gắn thẻ', 'config_fields' => ['tag']],
        'send_notification' => ['label' => 'Gửi thông báo in-app', 'config_fields' => ['template', 'data']],
        'send_telegram' => ['label' => 'Gửi tin nhắn Telegram', 'config_fields' => ['text']],
    ];

    public function __construct(
        private ConditionEvaluator $evaluator,
        private ActionExecutor $executor,
    ) {}

    /**
     * Run all active rules matching a trigger event with the given context.
     */
    public function run(string $event, array $context): array
    {
        $rules = AutomationRule::active()
            ->forEvent($event)
            ->where('home_id', $context['home_id'] ?? 0)
            ->orderBy('priority')
            ->get();

        if ($rules->isEmpty()) {
            return [];
        }

        $results = [];

        foreach ($rules as $rule) {
            $matchResult = $this->runRule($rule, $event, $context);
            if ($matchResult !== null) {
                $results[] = $matchResult;
            }
        }

        return $results;
    }

    /**
     * Evaluate and execute a single rule. Returns null if conditions don't match.
     */
    private function runRule(AutomationRule $rule, string $event, array $context): ?array
    {
        // Evaluate conditions
        if (! $this->evaluator->evaluate($rule->conditions, $context)) {
            // Log skipped
            $this->log($rule, $event, $context, 'skipped', [], null);

            return null;
        }

        // Execute actions
        $executedActions = $this->executor->execute($rule, $context);

        // Check for failures
        $allOk = collect($executedActions)->every(fn ($a) => $a['ok'] ?? false);
        $errorMessage = $allOk ? null : collect($executedActions)
            ->where('ok', false)
            ->pluck('error')
            ->implode('; ');

        // Mark rule as triggered
        try {
            $rule->markTriggered();
        } catch (\Throwable) {
            // Don't fail if we can't update trigger count
        }

        // Log result
        $this->log($rule, $event, $context, $allOk ? 'success' : 'failed', $executedActions, $errorMessage);

        return [
            'rule_id' => $rule->id,
            'rule_name' => $rule->name,
            'status' => $allOk ? 'success' : 'failed',
            'actions' => $executedActions,
        ];
    }

    private function log(AutomationRule $rule, string $event, array $context, string $status, array $actions, ?string $errorMessage): void
    {
        try {
            AutomationLog::create([
                'rule_id' => $rule->id,
                'home_id' => $context['home_id'] ?? $rule->home_id,
                'trigger_event' => $event,
                'matched_conditions' => $status === 'skipped' ? null : $rule->conditions,
                'executed_actions' => $actions,
                'status' => $status,
                'error_message' => $errorMessage,
                'executed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to write automation log', [
                'rule_id' => $rule->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
