<?php

namespace Modules\Expense\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Expense\Models\Expense;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()) {
            return false;
        }

        $expense = $this->route('expense');
        if (! $expense instanceof Expense) {
            return false;
        }

        $member = $expense->home?->member($this->user()->id);

        return $member && $member->canEdit();
    }

    public function rules(): array
    {
        return [
            'wallet_id' => ['sometimes', 'required', 'exists:wallets,id'],
            'category_id' => ['sometimes', 'required', 'exists:expense_categories,id'],
            'type' => ['sometimes', 'required', 'string', 'in:'.implode(',', Expense::TYPES)],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0.01', 'max:99999999999999.99'],
            'currency' => ['sometimes', 'nullable', 'string', 'size:3'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'occurred_at' => ['sometimes', 'required', 'date', 'before_or_equal:now'],
            'reference' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }
}
