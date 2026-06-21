<?php

namespace Modules\Home\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'currency' => ['nullable', 'string', 'size:3'],
        ];
    }
}
