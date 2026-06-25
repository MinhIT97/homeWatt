<?php

namespace Modules\Room\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Room\Models\Room;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()) {
            return false;
        }

        $room = $this->route('room');
        if (! $room instanceof Room) {
            return false;
        }

        $member = $room->home?->members()->where('user_id', $this->user()->id)->first();

        return $member && $member->canEdit();
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
