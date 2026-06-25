<?php

return [
    'default' => env('AI_PROVIDER', 'openai'),

    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'vision_model' => env('OPENAI_VISION_MODEL', 'gpt-4o-mini'),
        ],

        'fake' => [
            'enabled' => env('AI_FAKE_PROVIDER', false),
        ],
    ],

    'pricing' => [
        'gpt-4o' => ['prompt' => 0.0025, 'completion' => 0.01],
        'gpt-4o-mini' => ['prompt' => 0.00015, 'completion' => 0.0006],
    ],

    'rate_limits' => [
        'per_user_per_hour' => 20,
        'per_home_per_hour' => 50,
    ],

    'max_image_size_bytes' => 10 * 1024 * 1024,
    'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
];
