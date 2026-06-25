<?php

namespace Modules\Device\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Room\Models\Room;

class StoreDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()) {
            return false;
        }

        $room = Room::find($this->input('room_id'));
        if (! $room) {
            return false;
        }

        $member = $room->home?->members()->where('user_id', $this->user()->id)->first();

        return $member && $member->canEdit();
    }

    public function rules(): array
    {
        return [
            'room_id' => ['required', 'exists:rooms,id'],
            'device_type_id' => ['nullable', 'exists:device_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial' => ['nullable', 'string', 'max:255'],
            'purchased_at' => ['nullable', 'date'],
            'rated_power' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'max_power' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'standby_power' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'voltage' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'current' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'capacity' => ['nullable', 'numeric', 'min:0'],
            'hours_per_day' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'days_per_week' => ['nullable', 'integer', 'min:1', 'max:7'],
            'duty_cycle' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'season' => ['nullable', 'string', 'in:all,summer,winter'],
        ];
    }
}
