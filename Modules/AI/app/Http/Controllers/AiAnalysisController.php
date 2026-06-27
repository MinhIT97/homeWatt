<?php

namespace Modules\AI\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\AI\Jobs\AnalyzeDeviceImageJob;
use Modules\AI\Models\AiAnalysisRequest;
use Modules\AI\Models\DeviceExtraction;
use Modules\Device\Models\DeviceSpecification;
use Modules\Device\Models\DeviceType;
use Modules\Media\Models\Media;

class AiAnalysisController extends Controller
{
    public function index(Request $request): View
    {
        $analyses = AiAnalysisRequest::where('user_id', $request->user()->id)
            ->with(['media', 'result'])
            ->latest()
            ->paginate(20);

        return view('ai::index', compact('analyses'));
    }

    public function create(Request $request): View
    {
        $userHomeIds = $request->user()
            ->homeMembers()
            ->pluck('home_id')
            ->all();

        $media = Media::where('owner_type', 'device')
            ->whereIn('owner_id', function ($query) use ($userHomeIds) {
                $query->select('id')
                    ->from('devices')
                    ->whereIn('room_id', function ($q) use ($userHomeIds) {
                        $q->select('id')->from('rooms')->whereIn('home_id', $userHomeIds);
                    });
            })
            ->latest()
            ->limit(50)
            ->get();

        return view('ai::create', compact('media'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'media_id' => ['required', 'exists:media,id'],
        ]);

        $media = Media::findOrFail($request->input('media_id'));

        if (! $this->userCanAccessMedia($request, $media)) {
            abort(403);
        }

        $analysis = AiAnalysisRequest::firstOrCreate(
            [
                'media_id' => $media->id,
                'status' => 'pending',
            ],
            [
                'user_id' => $request->user()->id,
                'provider' => config('ai.default', 'openai'),
                'model' => config('ai.providers.openai.vision_model', 'gpt-4o-mini'),
            ]
        );

        if ($analysis->wasRecentlyCreated) {
            AnalyzeDeviceImageJob::dispatch($analysis)->onQueue('ai');
        }

        return redirect()->route('ai.analyses.show', $analysis)
            ->with('success', __('ai.analysis_started'));
    }

    private function userCanAccessMedia(Request $request, Media $media): bool
    {
        if ($media->owner_type === 'device') {
            $homeId = DB::table('rooms')
                ->join('devices', 'devices.room_id', '=', 'rooms.id')
                ->where('devices.id', $media->owner_id)
                ->value('rooms.home_id');

            if (! $homeId) {
                return false;
            }

            return DB::table('home_members')
                ->where('home_id', $homeId)
                ->where('user_id', $request->user()->id)
                ->exists();
        }

        return false;
    }

    public function show(Request $request, AiAnalysisRequest $analysis): View
    {
        if ($analysis->user_id !== $request->user()->id) {
            abort(403);
        }

        $analysis->load(['media', 'result.extractions']);

        return view('ai::show', compact('analysis'));
    }

    public function confirm(Request $request, DeviceExtraction $extraction): RedirectResponse
    {
        if ($extraction->result->request->user_id !== $request->user()->id) {
            abort(403);
        }

        $request->validate([
            'confirmed_value' => ['required', 'string'],
        ]);

        $confirmedValue = $request->input('confirmed_value');

        DB::transaction(function () use ($extraction, $confirmedValue) {
            $extraction->update([
                'confirmed_value' => $confirmedValue,
                'status' => 'confirmed',
            ]);

            $device = $extraction->device;
            if ($device) {
                $field = $extraction->field;
                if (in_array($field, ['brand', 'model'])) {
                    $device->update([$field => $confirmedValue]);
                } elseif ($field === 'device_type') {
                    $deviceType = DeviceType::where('slug', $confirmedValue)
                        ->orWhere('name', $confirmedValue)
                        ->first();
                    if ($deviceType) {
                        $device->update(['device_type_id' => $deviceType->id]);
                    }
                } elseif (in_array($field, ['rated_power', 'max_power', 'standby_power', 'voltage', 'current', 'capacity'])) {
                    $spec = $device->specification ?: new DeviceSpecification(['device_id' => $device->id]);
                    $spec->fill([$field => $confirmedValue])->save();
                }
            }
        });

        return back()->with('success', __('ai.value_confirmed'));
    }
}
