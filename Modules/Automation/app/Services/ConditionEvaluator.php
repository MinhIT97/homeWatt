<?php

namespace Modules\Automation\Services;

class ConditionEvaluator
{
    /**
     * Evaluate all conditions against the given context data.
     * All conditions must match (AND logic).
     */
    public function evaluate(array $conditions, array $context): bool
    {
        if (empty($conditions)) {
            return true; // No conditions = always match
        }

        foreach ($conditions as $condition) {
            if (! $this->evaluateSingle($condition, $context)) {
                return false;
            }
        }

        return true;
    }

    private function evaluateSingle(array $condition, array $context): bool
    {
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? '==';
        $expectedValue = $condition['value'] ?? null;

        $actualValue = $context[$field] ?? null;

        return match ($operator) {
            '>' => (float) $actualValue > (float) $expectedValue,
            '<' => (float) $actualValue < (float) $expectedValue,
            '>=' => (float) $actualValue >= (float) $expectedValue,
            '<=' => (float) $actualValue <= (float) $expectedValue,
            '==' => $this->looseEqual($actualValue, $expectedValue),
            '!=' => ! $this->looseEqual($actualValue, $expectedValue),
            'contains' => is_string($actualValue) && is_string($expectedValue)
                && str_contains(mb_strtolower($actualValue, 'UTF-8'), mb_strtolower($expectedValue, 'UTF-8')),
            'in' => is_array($expectedValue) && in_array($actualValue, $expectedValue),
            'not_in' => is_array($expectedValue) && ! in_array($actualValue, $expectedValue),
            'starts_with' => is_string($actualValue) && is_string($expectedValue)
                && str_starts_with(mb_strtolower($actualValue, 'UTF-8'), mb_strtolower($expectedValue, 'UTF-8')),
            'regex' => is_string($actualValue) && is_string($expectedValue)
                && preg_match($expectedValue, (string) $actualValue) === 1,
            default => false,
        };
    }

    private function looseEqual(mixed $a, mixed $b): bool
    {
        if (is_numeric($a) && is_numeric($b)) {
            return (float) $a === (float) $b;
        }

        return (string) $a === (string) $b;
    }
}
