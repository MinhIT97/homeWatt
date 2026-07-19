<?php

/*
 * Cross-Origin Resource Sharing (CORS) Configuration
 *
 * Configure allowed origins, methods, and headers for API requests.
 *
 * For production, replace `paths` and `allowed_origins` with your actual domains.
 */

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => [
        // Add your frontend domains here
        // 'https://app.example.com',
        env('FRONTEND_URL', 'http://localhost:8087'),
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'X-XSRF-TOKEN', 'Accept'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => true,
];
