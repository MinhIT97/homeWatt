<?php

return [
    'name' => 'Notification',

    /*
    |--------------------------------------------------------------------------
    | Telegram Bot Configuration
    |--------------------------------------------------------------------------
    */
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Push Notification Configuration
    |--------------------------------------------------------------------------
    */
    'push' => [
        'vapid_public_key'  => env('VAPID_PUBLIC_KEY'),
        'vapid_private_key' => env('VAPID_PRIVATE_KEY'),
        'vapid_subject'     => env('VAPID_SUBJECT', 'mailto:' . env('MAIL_FROM_ADDRESS', 'admin@example.com')),
    ],
];
