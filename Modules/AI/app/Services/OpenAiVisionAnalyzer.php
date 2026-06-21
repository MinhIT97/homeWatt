<?php

namespace Modules\AI\Services;

use Modules\AI\Contracts\DeviceImageAnalyzer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiVisionAnalyzer implements DeviceImageAnalyzer
{
    public function analyze(string $imageBase64): array
    {
        $apiKey = config('ai.providers.openai.api_key');
        $model = config('ai.providers.openai.vision_model', 'gpt-4o-mini');

        $response = Http::withToken($apiKey)
            ->timeout(120)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->systemPrompt(),
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => 'Extract the device specifications from this image. Return ONLY valid JSON.'],
                            ['type' => 'image_url', 'image_url' => ['url' => "data:image/jpeg;base64,{$imageBase64}"]],
                        ],
                    ],
                ],
                'response_format' => ['type' => 'json_object'],
                'max_tokens' => 1000,
                'temperature' => 0.1,
            ]);

        if (!$response->successful()) {
            Log::error('OpenAI Vision API error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \RuntimeException('AI vision analysis failed: ' . $response->status());
        }

        $data = $response->json();
        $content = $data['choices'][0]['message']['content'] ?? '{}';
        $parsed = json_decode($content, true) ?: [];

        $usage = $data['usage'] ?? [];
        $promptTokens = $usage['prompt_tokens'] ?? 0;
        $completionTokens = $usage['completion_tokens'] ?? 0;
        $cost = $this->calculateCost($model, $promptTokens, $completionTokens);

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
            'confidence' => $parsed['overall_confidence'] ?? 0.5,
            'fields_confidence' => $parsed['fields_confidence'] ?? [],
            'raw_response' => $content,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'cost' => $cost,
        ];
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
        return is_numeric($value) ? (float) $value : null;
    }

    protected function calculateCost(string $model, int $promptTokens, int $completionTokens): float
    {
        $pricing = [
            'gpt-4o' => ['prompt' => 0.0025, 'completion' => 0.01],
            'gpt-4o-mini' => ['prompt' => 0.00015, 'completion' => 0.0006],
        ];

        $rates = $pricing[$model] ?? ['prompt' => 0.0025, 'completion' => 0.01];

        return ($promptTokens / 1000) * $rates['prompt']
            + ($completionTokens / 1000) * $rates['completion'];
    }
}
