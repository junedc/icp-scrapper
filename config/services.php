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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'starline_api' => [
        'base_url' => env('API_ENDPOINT', 'https://api-master.local'),
        'timeout' => env('API_TIMEOUT', 60),
        'connect_timeout' => env('API_CONNECT_TIMEOUT', 10),
        'verify' => env('API_VERIFY', true),
        'ca_bundle' => env('API_CA_BUNDLE'),
        'origin' => env('API_ORIGIN', 'https://admin-master.test'),
        'referer' => env('API_REFERER', 'https://admin-master.test/'),
        'ordering_origin' => env('API_ORDERING_ORIGIN', 'https://ordering-master.test'),
        'ordering_referer' => env('API_ORDERING_REFERER', 'https://ordering-master.test/'),
        'healthcheck_path' => env('API_HEALTHCHECK_PATH', '/'),
    ],

];
