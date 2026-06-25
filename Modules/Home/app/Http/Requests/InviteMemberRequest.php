<?php

namespace Modules\Home\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Home\Models\Home;

class InviteMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $home = $this->route('home');

        if (! $home instanceof Home || ! $this->user()) {
            return false;
        }

        $member = $home->members()->where('user_id', $this->user()->id)->first();

        return $member && $member->canEdit();
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'role' => ['required', 'string', 'in:manager,member,viewer'],
        ];
    }
}
