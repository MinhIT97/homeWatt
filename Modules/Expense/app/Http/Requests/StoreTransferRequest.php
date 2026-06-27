<?php

namespace Modules\Expense\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Home\Models\Home;

class StoreTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()) {
            return false;
        }

        $homeId = $this->input('home_id');
        $home = Home::find($homeId);
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
            'from_wallet_id' => ['required', 'exists:wallets,id', 'different:to_wallet_id'],
            'to_wallet_id' => ['required', 'exists:wallets,id', 'different:from_wallet_id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999999999.99'],
            'fee' => ['nullable', 'numeric', 'min:0', 'max:99999999999999.99'],
            'currency' => ['nullable', 'string', 'size:3'],
            'description' => ['nullable', 'string', 'max:1000'],
            'occurred_at' => ['required', 'date', 'before_or_equal:now'],
        ];
    }
}
