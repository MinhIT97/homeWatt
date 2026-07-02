<?php

namespace Modules\Expense\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseCategory;

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
        $expense = $this->route('expense');
        $homeId = $expense instanceof Expense ? $expense->home_id : null;

        return [
            'wallet_id' => [
                'sometimes',
                'required',
                Rule::exists('wallets', 'id')->where('home_id', $homeId),
            ],
            'category_id' => [
                'sometimes',
                'required',
                Rule::exists('expense_categories', 'id')->where('home_id', $homeId),
            ],
            'type' => ['sometimes', 'required', 'string', 'in:'.implode(',', Expense::TYPES)],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0.01', 'max:99999999999999.99'],
            'currency' => ['sometimes', 'nullable', 'string', 'size:3'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'occurred_at' => ['sometimes', 'required', 'date', 'before_or_equal:now'],
            'reference' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $expense = $this->route('expense');
            if (! $expense instanceof Expense) {
                return;
            }

            $type = $this->input('type', $expense->type);
            $categoryId = $this->input('category_id', $expense->category_id);
            $category = ExpenseCategory::find($categoryId);

            if ($category && $type && $category->type !== $type) {
                $validator->errors()->add('category_id', __('validation.exists', ['attribute' => 'category_id']));
            }
        });
    }
}
