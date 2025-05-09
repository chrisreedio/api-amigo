<?php

// config for ChrisReedIO/APIAmigo
return [
    'enabled' => env('AMIGO_ENABLED', false),
    'table_prefix' => 'amigo_',

    'filament' => [
        'navigation' => [
            // These are the defaults, Uncomment to override
            // 'icon' => 'far-truck-fast',
            // 'label' => 'API Management',
            // 'group' => null,
            // 'sort' => 6000,
            // 'breadcrumb' => 'API Management',
        ],
    ],

    'requests' => [
        'header_key' => env('AMIGO_REQUEST_ID_KEY', 'X-Amigo-Request-Id'),
        // 'header_key' => env('AMIGO_REQUEST_ID_KEY', 'X-Request-Id'),
    ],

    'responses' => [
        // This is a master switch for capturing response bodies, if off, can still be toggled on via recordings
        'capture_body' => env('AMIGO_CAPTURE_RESPONSE_BODY', false),
        'capture_body_on_error' => env('AMIGO_CAPTURE_RESPONSE_BODY_ON_ERROR', true),

        // 'header_key' => env('AMIGO_RESPONSE_ID_KEY', 'X-Amigo-Response-Id'),
        'headers' => [
            'keys' => [
                'request_id' => env('AMIGO_RESPONSE_ID_KEY', 'X-Request-Id'),
                'rate' => [
                    'limit' => env('AMIGO_RATE_LIMIT_HEADER_KEY', 'X-RateLimit-Limit'),
                    'remaining' => env('AMIGO_RATE_LIMIT_REMAINING_HEADER_KEY', 'X-RateLimit-Remaining'),
                    // 'reset_header_key' => env('AMIGO_RATE_LIMIT_RESET_HEADER_KEY', 'X-RateLimit-Reset'),
                ],
            ],
        ],
    ],

    'webhooks' => [
        // This cannot be empty and should not be changed once set
        'prefix' => 'webhooks',

        'signature_header' => 'X-Signature',
    ],

    'models' => [
        'user' => "App\Models\User",
    ],

    // WIP - Duration Thresholds
    // This is likely to change (or be removed) in a future update
    'thresholds' => [
        'duration' => [
            'warning' => 60 / 1000,
            'error' => 100 / 1000,
        ],
        'response_size' => [
            'warning' => 1000,
            'error' => 2000,
        ],
    ],

    'tdigest' => [
        'enabled' => env('AMIGO_TDIGEST_ENABLED', false),
    ],

    'aggregation' => [
        'timezone' => env('AMIGO_AGGREGATION_TIMEZONE', 'UTC'),
    ],
];
