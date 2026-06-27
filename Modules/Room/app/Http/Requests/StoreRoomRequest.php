<?php

namespace Modules\Room\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Home\Models\Home;

class StoreRoomRequest extends FormRequest
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

        $member = $home->members()->where('user_id', $this->user()->id)->first();

        return $member && $member->canEdit();
    }

    public function rules(): array
    {
        return [
            'home_id' => ['required', 'exists:homes,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:living_room,bedroom,kitchen,bathroom,garage,outdoor,other'],
            'floor' => ['nullable', 'integer'],
            'sort_order' => ['nullable', 'integer'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
        ];
    }
}
