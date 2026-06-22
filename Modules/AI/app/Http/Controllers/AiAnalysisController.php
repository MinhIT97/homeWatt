<?php

namespace Modules\AI\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\AI\Jobs\AnalyzeDeviceImageJob;
use Modules\AI\Models\AiAnalysisRequest;
use Modules\AI\Models\DeviceExtraction;
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
        $media = Media::where('owner_type', 'device')
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

        $analysis = AiAnalysisRequest::create([
            'user_id' => $request->user()->id,
            'media_id' => $request->input('media_id'),
            'provider' => config('ai.default', 'openai'),
            'model' => config('ai.providers.openai.vision_model', 'gpt-4o-mini'),
            'status' => 'pending',
        ]);

        AnalyzeDeviceImageJob::dispatch($analysis)->onQueue('ai');

        return redirect()->route('ai.analyses.show', $analysis)
            ->with('success', 'Analysis started. Results will appear shortly.');
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

        $extraction->update([
            'confirmed_value' => $request->input('confirmed_value'),
            'status' => 'confirmed',
        ]);

        return back()->with('success', 'Value confirmed.');
    }
}
