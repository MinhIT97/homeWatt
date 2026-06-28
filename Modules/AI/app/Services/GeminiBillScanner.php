<?php

namespace Modules\AI\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiBillScanner
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

    public function scan(string $imageBase64): ?array
    {
        if (empty($this->apiKeys)) {
            Log::warning('Gemini API key is not configured for Bill Scanner.');
            return null;
        }

        $models = array_values(array_unique(array_merge([$this->defaultModel], $this->fallbackModels)));
        $mimeType = 'image/jpeg';
        $lastError = '';

        foreach ($models as $model) {
            foreach ($this->apiKeys as $keyIndex => $apiKey) {
                try {
                    $response = Http::timeout(45)
                        ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                            'contents' => [
                                [
                                    'parts' => [
                                        [
                                            'text' => $this->systemPrompt() . "\n\nAnalyze this Vietnamese receipt/invoice and return JSON matching the schema.",
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
                        $lastError = $response->json('error.message') ?? "Status {$response->status()}";
                        continue;
                    }

                    $data = $response->json();
                    $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

                    if (empty($content)) {
                        continue;
                    }

                    $parsed = json_decode($content, true);
                    if (is_array($parsed)) {
                        return [
                            'amount' => isset($parsed['amount']) ? (float) $parsed['amount'] : null,
                            'category' => $parsed['category'] ?? null,
                            'description' => $parsed['description'] ?? null,
                            'notes' => $parsed['notes'] ?? null,
                        ];
                    }
                } catch (\Throwable $e) {
                    $lastError = $e->getMessage();
                    Log::warning('Gemini Bill Scanner attempt raised exception: ' . $e->getMessage());
                }
            }
        }

        Log::error('All Gemini Bill Scanner attempts failed. Last error: ' . $lastError);
        return null;
    }

    protected function systemPrompt(): string
    {
        return <<<'PROMPT'
You are a receipts and shopping invoice scanner for a personal finance application.
Analyze the provided receipt image (usually in Vietnamese) and extract the financial information.
Return ONLY valid JSON matching this schema:

{
  "amount": number, // The total amount on the invoice (Tổng tiền phải thanh toán, sau thuế/giảm giá nếu có). Must be an integer number, e.g. 150000.
  "category": "eating|shopping|living|other", // Choose the best category match: "eating" (restaurants, coffee, groceries), "shopping" (clothes, electronics, cosmetics), "living" (utilities, rent, services), or "other".
  "description": "string", // A short description in Vietnamese representing the merchant name and what was bought, e.g., "Ăn uống tại Highland Coffee" or "Mua sắm tại Winmart".
  "notes": "string or null" // Extra details like payment method, date, or store address.
}

Rules:
- Extract values accurately. For "amount", clean any separators and convert to a clean numeric value (VND).
- If the image is not a receipt or doesn't show a valid transaction, return amount as null.
PROMPT;
    }
}
