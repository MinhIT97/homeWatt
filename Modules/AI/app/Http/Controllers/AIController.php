<?php

namespace Modules\AI\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AI\Models\AiAnalysisRequest;

class AIController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $analyses = AiAnalysisRequest::where('user_id', $request->user()->id)
            ->with(['media', 'result.extractions'])
            ->latest()
            ->paginate(20);

        return response()->json($analyses);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => __('ai.use_endpoint')], 400);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $analysis = AiAnalysisRequest::with(['media', 'result.extractions'])->findOrFail($id);

        if ($analysis->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json($analysis);
    }

    public function update(Request $request, $id): JsonResponse
    {
        return response()->json(['message' => __('ai.not_implemented')], 405);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $analysis = AiAnalysisRequest::findOrFail($id);

        if ($analysis->user_id !== $request->user()->id) {
            abort(403);
        }

        $analysis->delete();

        return response()->json(null, 204);
    }
}
