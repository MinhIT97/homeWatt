<?php

namespace Modules\Room\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'home_id' => ['required', 'exists:homes,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:living_room,bedroom,kitchen,bathroom,garage,outdoor,other'],
            'floor' => ['nullable', 'integer'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }
}
