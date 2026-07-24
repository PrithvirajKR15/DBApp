<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'maps_key' => env('GOOGLE_MAPS_API_KEY'),
        'geocoding_key' => env('GOOGLE_GEOCODING_API_KEY', env('GOOGLE_MAPS_API_KEY')),
        // Set GEOCODING_ENABLED=false in tests/CI to skip live Google calls.
        'geocoding_enabled' => env('GEOCODING_ENABLED', true),
        // When Google is disabled, invent stable coords so local ingest still works.
        'geocoding_fallback' => env('GEOCODING_FALLBACK', true),
    ],

    'partner' => [
        // Shared secret for POST /api/v1/orders/sync and /api/v1/stores/sync.
        'api_token' => env('PARTNER_API_TOKEN'),
    ],

    'zone_radius_meters' => env('ZONE_RADIUS_METERS', 4000),

];
