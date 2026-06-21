<?php

namespace Modules\Home\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InviteMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'role' => ['required', 'string', 'in:manager,member,viewer'],
        ];
    }
}
