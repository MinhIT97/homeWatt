<?php

namespace Modules\Expense\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Home\Models\Home;

class StoreExpenseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()) {
            return false;
        }

        $home = Home::find($this->input('home_id'));
        if (! $home) {
            return false;
        }

        $member = $home->member($this->user()->id);

        return $member && $member->canEdit();
    }

    public function rules(): array
    {
        return [
            'home_id' => ['required', 'exists:homes,id'],
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:'.implode(',', ExpenseCategory::TYPES)],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ];
    }
}
