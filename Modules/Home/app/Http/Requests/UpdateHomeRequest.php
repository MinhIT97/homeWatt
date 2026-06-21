<?php

namespace Modules\Home\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'currency' => ['nullable', 'string', 'size:3'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
        ];
    }
}
