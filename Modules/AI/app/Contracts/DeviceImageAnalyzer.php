<?php

namespace Modules\AI\Contracts;

interface DeviceImageAnalyzer
{
    /**
     * Analyze a device image and return structured data.
     *
     * @param string $imageBase64 Base64-encoded image
     * @return array{
     *   device_type: string|null,
     *   brand: string|null,
     *   model: string|null,
     *   rated_power_watts: float|null,
     *   max_power_watts: float|null,
     *   standby_power_watts: float|null,
     *   voltage: float|null,
     *   current: float|null,
     *   capacity: float|null,
     *   confidence: float,
     *   fields_confidence: array<string, float>,
     *   raw_response: string,
     *   prompt_tokens: int,
     *   completion_tokens: int,
     *   cost: float,
     * }
     */
    public function analyze(string $imageBase64): array;
}
