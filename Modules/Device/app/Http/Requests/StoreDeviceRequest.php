<?php

namespace Modules\Device\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
            'status' => ['nullable', 'string', 'in:active,inactive,broken'],
            'purchased_at' => ['nullable', 'date'],
            'rated_power' => ['nullable', 'numeric', 'min:0'],
            'max_power' => ['nullable', 'numeric', 'min:0'],
            'standby_power' => ['nullable', 'numeric', 'min:0'],
            'voltage' => ['nullable', 'numeric', 'min:0'],
            'current' => ['nullable', 'numeric', 'min:0'],
            'capacity' => ['nullable', 'numeric', 'min:0'],
            'hours_per_day' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'days_per_week' => ['nullable', 'integer', 'min:1', 'max:7'],
            'duty_cycle' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'season' => ['nullable', 'string', 'in:all,summer,winter'],
        ];
    }
}
