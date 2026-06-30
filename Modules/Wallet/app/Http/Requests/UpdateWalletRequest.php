<?php

namespace Modules\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Wallet\Models\Wallet;

class UpdateWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()) {
            return false;
        }

        $wallet = $this->route('wallet');
        if (! $wallet instanceof Wallet) {
            return false;
        }

        $member = $wallet->home?->member($this->user()->id);

        return $member && $member->canEdit();
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'type' => ['sometimes', 'required', 'string', 'in:'.implode(',', Wallet::TYPES)],
            'currency' => ['sometimes', 'nullable', 'string', 'size:3'],
            'opening_balance' => ['sometimes', 'required', 'numeric', 'min:0', 'max:99999999999999.99'],
            'icon' => ['sometimes', 'nullable', 'string', 'max:50'],
            'color' => ['sometimes', 'nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:1000'],
        ];
    }
}
