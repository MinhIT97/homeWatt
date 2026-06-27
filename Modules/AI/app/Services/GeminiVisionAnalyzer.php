<?php

namespace Modules\AI\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\AI\Contracts\DeviceImageAnalyzer;

class GeminiVisionAnalyzer implements DeviceImageAnalyzer
{
    protected array $apiKeys;

    protected string $defaultModel;

    protected array $fallbackModels;

    public function __construct()
    {
        $this->apiKeys = config('ai.providers.gemini.api_keys', []);
        $this->defaultModel = config('ai.providers.gemini.vision_model', 'gemini-1.5-flash');
        $this->fallbackModels = config('ai.providers.gemini.fallback_models', ['gemini-1.5-flash-8b', 'gemini-2.5-flash-lite']);
    }

    public function analyze(string $imageBase64): array
    {
        if (empty($this->apiKeys)) {
            throw new \RuntimeException('Gemini API key is not configured');
        }

        $models = array_values(array_unique(array_merge([$this->defaultModel], $this->fallbackModels)));
        $lastError = 'Unknown Gemini API error.';
        $mimeType = 'image/jpeg';

        foreach ($models as $model) {
            foreach ($this->apiKeys as $keyIndex => $apiKey) {
                try {
                    $response = Http::timeout(45)
                        ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                            'contents' => [
                                [
                                    'parts' => [
                                        [
                                            'text' => $this->systemPrompt()."\n\nExtract the device specifications from this image. Return ONLY valid JSON matching the schema.",
                                        ],
                                        [
                                            'inlineData' => [
                                                'mimeType' => $mimeType,
                                                'data' => $imageBase64,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'generationConfig' => [
                                'responseMimeType' => 'application/json',
                                'temperature' => 0.1,
                            ],
                        ]);

                    if ($response->failed()) {
                        $lastError = $response->json('error.message')
                            ?? "Gemini API request failed with status {$response->status()}.";

                        Log::warning('Gemini API attempt failed.', [
                            'status' => $response->status(),
                            'error' => $lastError,
                            'model' => $model,
                            'key_index' => $keyIndex,
                        ]);

                        continue;
                    }

                    $data = $response->json();
                    $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

                    if (! is_string($content) || trim($content) === '') {
                        $lastError = 'Gemini API returned an empty response.';

                        continue;
                    }

                    $parsed = json_decode($content, true);
                    if (! is_array($parsed)) {
                        Log::warning('Gemini returned non-JSON content', [
                            'content_preview' => mb_substr($content, 0, 200),
                        ]);
                        $parsed = [];
                    }

                    $usage = $data['usageMetadata'] ?? [];
                    $promptTokens = (int) ($usage['promptTokenCount'] ?? 0);
                    $completionTokens = (int) ($usage['candidatesTokenCount'] ?? 0);

                    $cost = ($promptTokens * 0.075 / 1000000) + ($completionTokens * 0.30 / 1000000);

                    return [
                        'device_type' => $parsed['device_type'] ?? null,
                        'brand' => $parsed['brand'] ?? null,
                        'model' => $parsed['model'] ?? null,
                        'rated_power_watts' => $this->parseNumeric($parsed['rated_power_watts'] ?? null),
                        'max_power_watts' => $this->parseNumeric($parsed['max_power_watts'] ?? null),
                        'standby_power_watts' => $this->parseNumeric($parsed['standby_power_watts'] ?? null),
                        'voltage' => $this->parseNumeric($parsed['voltage'] ?? null),
                        'current' => $this->parseNumeric($parsed['current'] ?? null),
                        'capacity' => $this->parseNumeric($parsed['capacity'] ?? null),
                        'confidence' => $this->clampConfidence($parsed['overall_confidence'] ?? 0.5),
                        'fields_confidence' => $parsed['fields_confidence'] ?? [],
                        'raw_response' => $content,
                        'prompt_tokens' => $promptTokens,
                        'completion_tokens' => $completionTokens,
                        'cost' => $cost,
                    ];
                } catch (\Throwable $e) {
                    $lastError = $e->getMessage();
                    Log::warning('Gemini API attempt raised an exception.', [
                        'error' => $lastError,
                        'model' => $model,
                        'key_index' => $keyIndex,
                    ]);
                }
            }
        }

        throw new \RuntimeException("All configured Gemini keys and models failed: {$lastError}");
    }

    protected function systemPrompt(): string
    {
        return <<<'PROMPT'
You are a device label analyzer for home appliances. Extract specifications from the image and return JSON with this schema:

{
  "device_type": "air_conditioner|refrigerator|washing_machine|water_heater|television|fan|lighting|rice_cooker|microwave|computer|electric_kettle|vacuum_cleaner|other",
  "brand": "manufacturer name or null",
  "model": "model number or null",
  "rated_power_watts": number or null,
  "max_power_watts": number or null,
  "standby_power_watts": number or null,
  "voltage": number or null,
  "current": number or null,
  "capacity": number or null,
  "overall_confidence": 0.0 to 1.0,
  "fields_confidence": {
    "device_type": 0.0 to 1.0,
    "brand": 0.0 to 1.0,
    "rated_power_watts": 0.0 to 1.0
  }
}

Rules:
- Convert all power values to Watts (W). If label shows kW, multiply by 1000.
- Convert all voltage to Volts (V). If label shows kV, multiply by 1000.
- Convert all current to Amperes (A). If label shows mA, divide by 1000.
- If a value is not visible or unclear, use null and set confidence to 0.
- Do NOT guess model numbers or power specs. Only extract what is clearly visible.
- If the image is not a device label, return all nulls with overall_confidence 0.
PROMPT;
    }

    protected function parseNumeric($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $float = (float) $value;

        if (! is_finite($float)) {
            return null;
        }

        return $float;
    }

    protected function clampConfidence($confidence): float
    {
        $float = is_numeric($confidence) ? (float) $confidence : 0.5;

        return max(0.0, min(1.0, $float));
    }
}
