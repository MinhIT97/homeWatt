<?php

namespace Modules\Device\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\AI\Models\AiAnalysisRequest;
use Modules\Device\Http\Requests\StoreDeviceRequest;
use Modules\Device\Http\Requests\UpdateDeviceRequest;
use Modules\Device\Models\Device;
use Modules\Device\Models\DeviceSpecification;
use Modules\Device\Models\DeviceType;
use Modules\Device\Models\DeviceUsageProfile;
use Modules\Room\Models\Room;

class DeviceController extends Controller
{
    public function index(Request $request): View
    {
        $query = Device::whereHas('room.home.members', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with(['room.home', 'deviceType', 'specification']);

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }
        if ($request->filled('type_id')) {
            $query->where('device_type_id', $request->type_id);
        }
        if ($request->filled('search')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('brand', 'like', "%{$request->search}%")
                ->orWhere('model', 'like', "%{$request->search}%"));
        }

        $devices = $query->latest()->paginate(20);
        $deviceTypes = DeviceType::orderBy('name')->get();

        return view('device::index', compact('devices', 'deviceTypes'));
    }

    public function create(Request $request): View
    {
        $rooms = Room::whereHas('home.members', fn ($q) => $q->where('user_id', $request->user()->id)
            ->whereIn('role', ['owner', 'manager']))
            ->with('home')
            ->get();

        $deviceTypes = DeviceType::orderBy('name')->get();
        $selectedRoomId = $request->get('room_id');

        return view('device::create', compact('rooms', 'deviceTypes', 'selectedRoomId'));
    }

    public function store(StoreDeviceRequest $request): RedirectResponse
    {
        $room = Room::findOrFail($request->validated('room_id'));
        $this->authorizeRoomMember($request, $room);

        $device = Device::create($request->safe()->only([
            'room_id', 'device_type_id', 'name', 'brand', 'model', 'serial', 'status', 'purchased_at',
        ]));

        if ($request->hasAny(['rated_power', 'max_power', 'standby_power', 'voltage', 'current'])) {
            DeviceSpecification::create([
                'device_id' => $device->id,
                ...$request->safe()->only(['voltage', 'current', 'rated_power', 'max_power', 'standby_power', 'capacity']),
            ]);
        }

        if ($request->hasAny(['hours_per_day', 'duty_cycle'])) {
            DeviceUsageProfile::create([
                'device_id' => $device->id,
                ...$request->safe()->only(['hours_per_day', 'days_per_week', 'duty_cycle', 'season']),
                'source' => 'manual',
            ]);
        }

        return redirect()->route('devices.show', $device)
            ->with('success', 'Device created successfully.');
    }

    public function show(Request $request, Device $device): View
    {
        $this->authorize('view', $device);

        $device->load([
            'room.home',
            'deviceType',
            'specification',
            'usageProfile',
            'energyReadings',
            'media' => fn ($q) => $q->latest(),
        ]);

        $analyses = AiAnalysisRequest::whereIn('media_id', $device->media->pluck('id'))
            ->with(['result.extractions'])
            ->latest()
            ->get();

        $deviceTypes = DeviceType::orderBy('name')->get();

        return view('device::show', compact('device', 'analyses', 'deviceTypes'));
    }

    public function edit(Request $request, Device $device): View
    {
        $this->authorize('update', $device);

        $device->load(['specification', 'usageProfile']);
        $deviceTypes = DeviceType::orderBy('name')->get();

        return view('device::edit', compact('device', 'deviceTypes'));
    }

    public function update(UpdateDeviceRequest $request, Device $device): RedirectResponse
    {
        $this->authorize('update', $device);

        $device->update($request->safe()->only([
            'device_type_id', 'name', 'brand', 'model', 'serial', 'status', 'purchased_at',
        ]));

        if ($device->specification) {
            $device->specification->update($request->safe()->only([
                'voltage', 'current', 'rated_power', 'max_power', 'standby_power', 'capacity',
            ]));
        } elseif ($request->hasAny(['rated_power', 'max_power', 'standby_power', 'voltage', 'current'])) {
            DeviceSpecification::create([
                'device_id' => $device->id,
                ...$request->safe()->only(['voltage', 'current', 'rated_power', 'max_power', 'standby_power', 'capacity']),
            ]);
        }

        if ($device->usageProfile) {
            $device->usageProfile->update($request->safe()->only([
                'hours_per_day', 'days_per_week', 'duty_cycle', 'season',
            ]));
        } elseif ($request->hasAny(['hours_per_day', 'duty_cycle'])) {
            DeviceUsageProfile::create([
                'device_id' => $device->id,
                ...$request->safe()->only(['hours_per_day', 'days_per_week', 'duty_cycle', 'season']),
                'source' => 'manual',
            ]);
        }

        return redirect()->route('devices.show', $device)
            ->with('success', 'Device updated successfully.');
    }

    public function destroy(Request $request, Device $device): RedirectResponse
    {
        $this->authorize('delete', $device);

        $room = $device->room;
        $device->delete();

        return redirect()->route('rooms.show', $room)
            ->with('success', 'Device deleted.');
    }

    public function uploadImage(Request $request, Device $device): RedirectResponse
    {
        $this->authorize('update', $device);

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:20480'],
        ]);

        $file = $request->file('image');
        $path = $file->store('devices/'.$device->id, 'private');

        $device->media()->create([
            'disk' => 'private',
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'checksum' => hash_file('sha256', $file->getRealPath()),
            'status' => 'ready',
        ]);

        return back()->with('success', 'Ảnh đã được tải lên. Nhấn "AI Phân tích" để trích xuất thông số.');
    }

    public function deleteImage(Request $request, Device $device, $mediaId): RedirectResponse
    {
        $this->authorize('update', $device);

        $media = $device->media()->findOrFail($mediaId);
        Storage::disk($media->disk)->delete($media->path);
        $media->delete();

        return back()->with('success', 'Đã xóa ảnh.');
    }

    protected function authorizeRoomMember(Request $request, Room $room): void
    {
        $member = $room->home->members()->where('user_id', $request->user()->id)->first();
        if (! $member || ! $member->canEdit()) {
            abort(403);
        }
    }
}
