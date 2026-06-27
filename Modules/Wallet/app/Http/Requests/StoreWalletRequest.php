<?php

namespace Modules\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Home\Models\Home;

class StoreWalletRequest extends FormRequest
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
            'type' => ['required', 'string', 'in:cash,bank,credit_card'],
            'currency' => ['nullable', 'string', 'size:3'],
            'opening_balance' => ['required', 'numeric', 'min:0', 'max:99999999999999.99'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ];
    }
}
