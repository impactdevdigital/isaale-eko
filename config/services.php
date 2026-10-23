<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'square' => [
        'env'         => env('SQUARE_ENV', 'sandbox'),
        'token'       => env('SQUARE_ACCESS_TOKEN'),
        'location_id' => env('SQUARE_LOCATION_ID'),
        'app_id'      => env('SQUARE_APP_ID'),
        'version'     => env('SQUARE_VERSION'),
        'webhook_key' => env('SQUARE_WEBHOOK_SIGNATURE_KEY'),
        'webhook_url' => env('SQUARE_WEBHOOK_URL'),
    ],

    'doordash' => [
        'developer_id'   => env('DOORDASH_DEVELOPER_ID'),
        'key_id'         => env('DOORDASH_KEY_ID'),
        'signing_secret' => env('DOORDASH_SIGNING_SECRET'),
    ],

    'anthropic' => [
        'key'   => env('ANTHROPIC_API_KEY'),
        'model' => env('ASHA_MODEL'),
    ],

];
