<?php

namespace Modules\Room\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:living_room,bedroom,kitchen,bathroom,garage,outdoor,other'],
            'floor' => ['nullable', 'integer'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }
}
