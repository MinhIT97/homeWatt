<?php

namespace Modules\Device\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Device\Models\Device;

class UpdateDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()) {
            return false;
        }

        $device = $this->route('device');
        if (! $device instanceof Device) {
            return false;
        }

        $member = $device->room?->home?->members()->where('user_id', $this->user()->id)->first();

        return $member && $member->canEdit();
    }

    public function rules(): array
    {
        return [
            'device_type_id' => ['nullable', 'exists:device_types,id'],
            'room_id' => ['sometimes', 'exists:rooms,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'serial' => ['nullable', 'string', 'max:255'],
            'purchased_at' => ['nullable', 'date'],
            'purchase_price' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'warranty_duration' => ['nullable', 'integer', 'min:0'],
            'warranty_unit' => ['nullable', 'string', 'in:month,year'],
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
