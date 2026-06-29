<?php

namespace Modules\Device\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\AI\Models\AiAnalysisRequest;
use Modules\Device\Http\Requests\StoreDeviceRequest;
use Modules\Device\Http\Requests\UpdateDeviceRequest;
use Modules\Device\Jobs\ScanDevicePhotoJob;
use Modules\Device\Models\Device;
use Modules\Device\Models\DeviceRepair;
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

        $device = DB::transaction(function () use ($request) {
            $created = Device::create($request->safe()->only([
                'room_id', 'device_type_id', 'name', 'brand', 'model', 'location', 'serial', 'purchased_at', 'purchase_price', 'warranty_duration', 'warranty_unit', 'maintenance_interval', 'last_maintained_at',
            ]));

            if ($request->hasAny(['rated_power', 'max_power', 'standby_power', 'voltage', 'current'])) {
                DeviceSpecification::create([
                    'device_id' => $created->id,
                    ...$request->safe()->only(['voltage', 'current', 'rated_power', 'max_power', 'standby_power', 'capacity']),
                ]);
            }

            if ($request->hasAny(['hours_per_day', 'duty_cycle'])) {
                DeviceUsageProfile::create([
                    'device_id' => $created->id,
                    ...$request->safe()->only(['hours_per_day', 'days_per_week', 'duty_cycle', 'season']),
                    'source' => 'manual',
                ]);
            }

            return $created;
        });

        return redirect()->route('devices.show', $device)
            ->with('success', __('device.created'));
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
            'repairs' => fn ($q) => $q->latest(),
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

        DB::transaction(function () use ($request, $device) {
            $lockedDevice = Device::where('id', $device->id)->lockForUpdate()->first();
            if (! $lockedDevice) {
                abort(404);
            }

            $lockedDevice->update($request->safe()->only([
                'device_type_id', 'name', 'brand', 'model', 'location', 'serial', 'purchased_at', 'purchase_price', 'warranty_duration', 'warranty_unit', 'maintenance_interval', 'last_maintained_at',
            ]));

            if ($lockedDevice->specification) {
                $lockedDevice->specification->update($request->safe()->only([
                    'voltage', 'current', 'rated_power', 'max_power', 'standby_power', 'capacity',
                ]));
            } elseif ($request->hasAny(['rated_power', 'max_power', 'standby_power', 'voltage', 'current'])) {
                DeviceSpecification::create([
                    'device_id' => $lockedDevice->id,
                    ...$request->safe()->only(['voltage', 'current', 'rated_power', 'max_power', 'standby_power', 'capacity']),
                ]);
            }

            if ($lockedDevice->usageProfile) {
                $lockedDevice->usageProfile->update($request->safe()->only([
                    'hours_per_day', 'days_per_week', 'duty_cycle', 'season',
                ]));
            } elseif ($request->hasAny(['hours_per_day', 'duty_cycle'])) {
                DeviceUsageProfile::create([
                    'device_id' => $lockedDevice->id,
                    ...$request->safe()->only(['hours_per_day', 'days_per_week', 'duty_cycle', 'season']),
                    'source' => 'manual',
                ]);
            }
        });

        return redirect()->route('devices.show', $device)
            ->with('success', __('device.updated'));
    }

    public function destroy(Request $request, Device $device): RedirectResponse
    {
        $this->authorize('delete', $device);

        $room = $device->room;

        DB::transaction(function () use ($device) {
            $locked = Device::where('id', $device->id)->lockForUpdate()->first();
            if (! $locked) {
                abort(404);
            }

            $locked->specification?->delete();
            $locked->usageProfile?->delete();
            $locked->media()->delete();
            $locked->energyReadings()->delete();
            $locked->delete();
        });

        return redirect()->route('rooms.show', $room)
            ->with('success', __('device.deleted'));
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

        return back()->with('success', __('device.image_uploaded'));
    }

    public function deleteImage(Request $request, Device $device, $mediaId): RedirectResponse
    {
        $this->authorize('update', $device);

        $media = $device->media()->findOrFail($mediaId);
        Storage::disk($media->disk)->delete($media->path);
        $media->delete();

        return back()->with('success', __('device.image_deleted'));
    }

    public function analyzeImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:20480'],
        ]);

        try {
            $file = $request->file('image');
            $base64 = base64_encode(file_get_contents($file->getRealPath()));

            $analysisId = 'analysis_'.Str::uuid()->toString();
            Cache::put("device_analysis:{$analysisId}", [
                'status' => 'pending',
                'user_id' => $request->user()->id,
            ], 3600);

            ScanDevicePhotoJob::dispatch($analysisId, $request->user()->id, $base64);

            return response()->json([
                'success' => true,
                'async' => true,
                'analysis_id' => $analysisId,
            ]);
        } catch (\Throwable $e) {
            Log::error('AI async upload-time analysis failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Phân tích ảnh thất bại: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function checkAnalysisStatus(Request $request, string $id): JsonResponse
    {
        $data = Cache::get("device_analysis:{$id}");

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Yêu cầu không tồn tại hoặc đã hết hạn.',
            ], 404);
        }

        if (($data['user_id'] ?? null) !== $request->user()->id) {
            abort(403);
        }

        unset($data['user_id']);

        return response()->json($data);
    }

    protected function authorizeRoomMember(Request $request, Room $room): void
    {
        $member = $room->home->members()->where('user_id', $request->user()->id)->first();
        if (! $member || ! $member->canEdit()) {
            abort(403);
        }
    }

    public function storeRepair(Request $request, Device $device): RedirectResponse
    {
        $this->authorize('update', $device);

        $validated = $request->validate([
            'repaired_at' => ['required', 'date'],
            'cost' => ['required', 'numeric', 'min:0'],
            'description' => ['required', 'string', 'max:1000'],
            'repairer' => ['nullable', 'string', 'max:255'],
        ]);

        $device->repairs()->create($validated);

        return back()->with('success', 'Đã lưu lịch sử sửa chữa thiết bị thành công!');
    }
}
