<?php

namespace Modules\AI\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\AI\Contracts\DeviceImageAnalyzer;
use Modules\AI\Models\AiAnalysisRequest;
use Modules\AI\Models\AiAnalysisResult;
use Modules\AI\Models\DeviceExtraction;
use Modules\Device\Models\Device;
use Modules\Device\Models\DeviceSpecification;
use Modules\Device\Models\DeviceType;
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
            $deviceId = $media->owner_type === 'device' ? $media->owner_id : null;

            $imageContent = Storage::disk($media->disk)->get($media->path);
            $imageBase64 = base64_encode($imageContent);

            $startTime = microtime(true);

            $result = $analyzer->analyze($imageBase64);

            $processingTimeMs = (int) ((microtime(true) - $startTime) * 1000);

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

            $this->createAndApplyExtractions($analysisResult, $result, $deviceId);

            $this->analysisRequest->update(['status' => 'completed']);
        } catch (\Throwable $e) {
            Log::error('AI analysis job failed', [
                'request_id' => $this->analysisRequest->id,
                'attempts' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            $sanitizedError = $this->sanitizeErrorMessage($e->getMessage());

            $this->analysisRequest->update([
                'status' => 'failed',
                'error' => $sanitizedError,
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff);

                return;
            }

            // Re-throw on final attempt so Laravel marks the job as failed
            throw $e;
        }
    }

    protected function createAndApplyExtractions(AiAnalysisResult $analysisResult, array $result, ?int $deviceId): void
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

        $device = $deviceId ? Device::find($deviceId) : null;

        DB::transaction(function () use ($analysisResult, $fields, $device, $result, $deviceId) {
            foreach ($fields as $field => $value) {
                $hasValue = $value !== null && $value !== '';
                $status = $hasValue ? 'confirmed' : 'pending';

                DeviceExtraction::create([
                    'ai_analysis_result_id' => $analysisResult->id,
                    'device_id' => $deviceId,
                    'field' => $field,
                    'ai_value' => $hasValue ? (string) $value : null,
                    'confirmed_value' => $hasValue ? (string) $value : null,
                    'confidence' => $result['fields_confidence'][$field] ?? null,
                    'status' => $status,
                ]);

                // Propagate confirmed values to the device immediately
                if ($device && $hasValue) {
                    if (in_array($field, ['brand', 'model'])) {
                        $device->update([$field => $value]);
                    } elseif ($field === 'device_type') {
                        $deviceType = DeviceType::where('slug', $value)
                            ->orWhere('name', $value)
                            ->first();
                        if ($deviceType) {
                            $device->update(['device_type_id' => $deviceType->id]);
                        }
                    } elseif (in_array($field, ['rated_power', 'max_power', 'standby_power', 'voltage', 'current', 'capacity'])) {
                        $spec = $device->specification ?: new DeviceSpecification(['device_id' => $device->id]);
                        $spec->fill([$field => $value])->save();
                    }
                }
            }
        });
    }

    protected function sanitizeErrorMessage(string $message): string
    {
        // Strip API keys, tokens, and credentials from error messages
        $patterns = [
            '/sk-[A-Za-z0-9_-]+/',
            '/Bearer\s+[A-Za-z0-9_-]+/',
            '/api[_-]?key["\s:=]+[A-Za-z0-9_-]+/i',
            '/token["\s:=]+[A-Za-z0-9_-]+/i',
        ];

        $cleaned = preg_replace($patterns, '[REDACTED]', $message);

        // Truncate long messages
        return mb_substr($cleaned ?? $message, 0, 500);
    }
}
