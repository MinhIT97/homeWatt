<?php

namespace Modules\Home\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $home = $this->route('home');

        if (! $home || ! $this->user()) {
            return false;
        }

        $member = $home->members()->where('user_id', $this->user()->id)->first();

        return $member && $member->canEdit();
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'currency' => ['nullable', 'string', 'size:3'],
        ];
    }
}
