<?php

namespace Modules\Expense\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Expense\Models\ExpenseCategory;

class UpdateExpenseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()) {
            return false;
        }

        $category = $this->route('category');
        if (! $category instanceof ExpenseCategory) {
            return false;
        }

        if ($category->is_system) {
            return false;
        }

        $member = $category->home?->member($this->user()->id);

        return $member && $member->canEdit();
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'type' => ['sometimes', 'required', 'string', 'in:'.implode(',', ExpenseCategory::TYPES)],
            'icon' => ['sometimes', 'nullable', 'string', 'max:50'],
            'color' => ['sometimes', 'nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:1000'],
        ];
    }
}
