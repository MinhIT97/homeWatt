<?php

namespace Modules\AI\Services;

use Modules\AI\Contracts\DeviceImageAnalyzer;

class FakeDeviceImageAnalyzer implements DeviceImageAnalyzer
{
    public function analyze(string $imageBase64): array
    {
        return [
            'device_type' => 'air_conditioner',
            'brand' => 'TestBrand',
            'model' => 'TestModel-1000',
            'rated_power_watts' => 1200.0,
            'max_power_watts' => 1500.0,
            'standby_power_watts' => 5.0,
            'voltage' => 220.0,
            'current' => 5.5,
            'capacity' => 12000.0,
            'confidence' => 0.92,
            'fields_confidence' => [
                'device_type' => 0.95,
                'brand' => 0.88,
                'rated_power_watts' => 0.90,
            ],
            'raw_response' => '{"device_type":"air_conditioner","brand":"TestBrand",...}',
            'prompt_tokens' => 150,
            'completion_tokens' => 80,
            'cost' => 0.001,
        ];
    }
}
