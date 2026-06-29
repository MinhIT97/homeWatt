<?php

namespace Modules\AI\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiElectricBillScanner
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
            Log::warning('Gemini API key is not configured for Electric Bill Scanner.');
            return null;
        }

        $models = array_values(array_unique(array_merge([$this->defaultModel], $this->fallbackModels)));
        $mimeType = 'image/jpeg';
        $lastError = '';

        foreach ($models as $model) {
            foreach ($this->apiKeys as $apiKey) {
                try {
                    $response = Http::timeout(45)
                        ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                            'contents' => [
                                [
                                    'parts' => [
                                        [
                                            'text' => $this->systemPrompt() . "\n\nAnalyze this image. If it is an electricity bill, return the JSON. Otherwise, return is_electric_bill as false.",
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
                            'is_electric_bill' => (bool) ($parsed['is_electric_bill'] ?? false),
                            'old_index' => isset($parsed['old_index']) ? (float) $parsed['old_index'] : null,
                            'new_index' => isset($parsed['new_index']) ? (float) $parsed['new_index'] : null,
                            'kwh' => isset($parsed['kwh']) ? (float) $parsed['kwh'] : null,
                            'amount' => isset($parsed['amount']) ? (float) $parsed['amount'] : null,
                            'merchant' => $parsed['merchant'] ?? 'EVN',
                            'customer_name' => $parsed['customer_name'] ?? null,
                            'customer_code' => $parsed['customer_code'] ?? null,
                            'billing_month' => $parsed['billing_month'] ?? null,
                        ];
                    }
                } catch (\Throwable $e) {
                    $lastError = $e->getMessage();
                    Log::warning('Gemini Electric Bill Scanner attempt raised exception: ' . $e->getMessage());
                }
            }
        }

        Log::error('All Gemini Electric Bill Scanner attempts failed. Last error: ' . $lastError);
        return null;
    }

    protected function systemPrompt(): string
    {
        return <<<'PROMPT'
You are an electricity bill scanner for a smart home energy tracking application.
Analyze the provided image (usually an EVN electric bill in Vietnamese).
Determine if it is a valid electricity bill and extract the details.
Return ONLY valid JSON matching this schema:

{
  "is_electric_bill": boolean, // true if the image is an electricity bill or electricity receipt
  "old_index": number or null, // The old electricity meter index (Chỉ số cũ / CS cũ)
  "new_index": number or null, // The new electricity meter index (Chỉ số mới / CS mới)
  "kwh": number or null, // Total energy consumed in kWh (Điện năng tiêu thụ / Sản lượng điện tiêu thụ / Điện năng giao nhận)
  "amount": number or null, // Total billing amount in VND (Tổng số tiền phải thanh toán, bao gồm VAT)
  "merchant": string or null, // Provider name, usually "EVN", "EVNHANOI", "EVNHCMC", "EVN SPC", "EVN CPC", "EVN NPC", etc.
  "customer_name": string or null, // Name of the customer (Tên khách hàng / Chủ hộ)
  "customer_code": string or null, // Customer code (Mã khách hàng / Mã danh bộ, starting with PE, PA, PB, PC, PD, v.v.)
  "billing_month": string or null // Billing month/period, format MM/YYYY or MM-YYYY (Kỳ hóa đơn, e.g. "06/2026")
}

Rules:
- Extract values accurately. Clean any currency separators/commas for numbers.
- If it is not an electricity bill, set is_electric_bill to false and other values to null.
PROMPT;
    }
}
