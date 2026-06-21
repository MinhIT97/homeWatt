<?php

namespace Modules\AI\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\AI\Contracts\DeviceImageAnalyzer;
use Modules\AI\Models\AiAnalysisRequest;
use Modules\AI\Models\AiAnalysisResult;
use Modules\AI\Models\DeviceExtraction;
use Modules\Media\Models\Media;

class AnalyzeDeviceImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 600;
    public $backoff = 30;

    public function __construct(
        protected AiAnalysisRequest $analysisRequest,
    ) {}

    public function handle(DeviceImageAnalyzer $analyzer): void
    {
        $this->analysisRequest->update([
            'status' => 'processing',
            'attempts' => $this->analysisRequest->attempts + 1,
        ]);

        try {
            $media = Media::findOrFail($this->analysisRequest->media_id);

            $imageContent = Storage::disk($media->disk)->get($media->path);
            $imageBase64 = base64_encode($imageContent);

            $startTime = microtime(true);

            $result = $analyzer->analyze($imageBase64);

            $processingTimeMs = (int)((microtime(true) - $startTime) * 1000);

            $analysisResult = AiAnalysisResult::create([
                'ai_analysis_request_id' => $this->analysisRequest->id,
                'raw_response' => $result['raw_response'],
                'normalized_data' => $result,
                'confidence' => $result['confidence'],
                'prompt_tokens' => $result['prompt_tokens'],
                'completion_tokens' => $result['completion_tokens'],
                'cost' => $result['cost'],
                'processing_time_ms' => $processingTimeMs,
            ]);

            $this->createExtractions($analysisResult, $result);

            $this->analysisRequest->update(['status' => 'completed']);
        } catch (\Throwable $e) {
            Log::error('AI analysis job failed', [
                'request_id' => $this->analysisRequest->id,
                'error' => $e->getMessage(),
            ]);

            $this->analysisRequest->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff);
            }
        }
    }

    protected function createExtractions(AiAnalysisResult $analysisResult, array $result): void
    {
        $fields = [
            'device_type' => $result['device_type'] ?? null,
            'brand' => $result['brand'] ?? null,
            'model' => $result['model'] ?? null,
            'rated_power' => $result['rated_power_watts'] ?? null,
            'max_power' => $result['max_power_watts'] ?? null,
            'standby_power' => $result['standby_power_watts'] ?? null,
            'voltage' => $result['voltage'] ?? null,
            'current' => $result['current'] ?? null,
            'capacity' => $result['capacity'] ?? null,
        ];

        foreach ($fields as $field => $value) {
            DeviceExtraction::create([
                'ai_analysis_result_id' => $analysisResult->id,
                'field' => $field,
                'ai_value' => $value !== null ? (string) $value : null,
                'confidence' => $result['fields_confidence'][$field] ?? null,
                'status' => 'pending',
            ]);
        }
    }
}
