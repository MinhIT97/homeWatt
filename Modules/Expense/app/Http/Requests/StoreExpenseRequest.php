<?php

namespace Modules\Expense\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Home\Models\Home;
use Modules\Wallet\Models\Wallet;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()) {
            return false;
        }

        $homeId = $this->input('home_id');
        $walletId = $this->input('wallet_id');
        $categoryId = $this->input('category_id');

        $home = Home::find($homeId);
        $wallet = $walletId ? Wallet::find($walletId) : null;
        $category = $categoryId ? ExpenseCategory::find($categoryId) : null;

        if (! $home || ! $wallet || ! $category) {
            return false;
        }

        if ($wallet->home_id !== $home->id || $category->home_id !== $home->id) {
            return false;
        }

        $member = $home->member($this->user()->id);

        return $member && $member->canEdit();
    }

    public function rules(): array
    {
        return [
            'home_id' => ['required', 'exists:homes,id'],
            'wallet_id' => ['required', 'exists:wallets,id'],
            'category_id' => ['required', 'exists:expense_categories,id'],
            'type' => ['required', 'string', 'in:'.implode(',', Expense::TYPES)],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999999999.99'],
            'currency' => ['nullable', 'string', 'size:3'],
            'description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'occurred_at' => ['required', 'date', 'before_or_equal:now'],
            'reference' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $category = ExpenseCategory::find($this->input('category_id'));
            if ($category && $this->input('type') && $category->type !== $this->input('type')) {
                $validator->errors()->add('category_id', __('validation.exists', ['attribute' => 'category_id']));
            }
        });
    }
}
