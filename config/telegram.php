<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default bot
    |--------------------------------------------------------------------------
    |
    | The name of the bot used when you call Telegram::driver() with no argument.
    |
    */
    'default' => env('TELEGRAM_DEFAULT_BOT', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Bots
    |--------------------------------------------------------------------------
    |
    | Register one or more bots. For dynamic multi-tenant setups you can also
    | build a bot at runtime from a DB token via Telegram::botFromToken($token).
    |
    */
    'bots' => [
        'default' => [
            'token' => env('TELEGRAM_BOT_TOKEN', ''),
            'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
            'parse_mode' => env('TELEGRAM_PARSE_MODE', 'HTML'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API URL & timeout
    |--------------------------------------------------------------------------
    */
    'api_url' => env('TELEGRAM_API_URL', 'https://api.telegram.org'),
    'timeout' => (int) env('TELEGRAM_TIMEOUT', 30),
];
