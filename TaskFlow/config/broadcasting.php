<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | 'null' in every environment until a real broadcaster is configured —
    | keeps `php artisan test` and local dev from needing a Reverb server
    | running just to boot. Set BROADCAST_CONNECTION=reverb once one is
    | actually running (`php artisan reverb:start`).
    |
    */

    'default' => env('BROADCAST_CONNECTION', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    |
    | Reverb is Laravel's own self-hosted, Pusher-protocol WebSocket server
    | (pure PHP — chosen over Soketi specifically because Soketi's native
    | uWebSockets.js dependency doesn't support Node 22 on Windows, which
    | this project's dev machine runs). The frontend's Echo client speaks
    | the same Pusher protocol either way, so nothing on that side cares
    | which server is actually running underneath.
    |
    */

    'connections' => [

        'reverb' => [
            'driver' => 'reverb',
            'key' => env('REVERB_APP_KEY'),
            'secret' => env('REVERB_APP_SECRET'),
            'app_id' => env('REVERB_APP_ID'),
            'options' => [
                'host' => env('REVERB_HOST'),
                'port' => env('REVERB_PORT', 443),
                'scheme' => env('REVERB_SCHEME', 'https'),
                'useTLS' => env('REVERB_SCHEME', 'https') === 'https',
            ],
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
