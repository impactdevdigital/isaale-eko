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

    // Marketing email (newsletter, new arrivals, promotions). Email consents are
    // copied into this Brevo list. Off until both values are set. See App\Services\Marketing\Brevo.
    'brevo' => [
        'key' => env('BREVO_API_KEY'),
        'list_id' => env('BREVO_LIST_ID'),
        // Optional lists for the other account email topics (Updates & preferences).
        // Empty = that topic stays on the website only.
        'topic_lists' => [
            'email_offers' => env('BREVO_OFFERS_LIST_ID'),
            'email_rewards' => env('BREVO_REWARDS_LIST_ID'),
        ],
    ],

    // Google Analytics 4, e.g. G-XXXXXXXXXX. Off until set. See App\Services\Analytics.
    'google_analytics' => [
        'measurement_id' => env('GA_MEASUREMENT_ID'),
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
        // Copy website orders into Square (items, customer, pickup/delivery). See OrderSync.
        'sync_orders' => (bool) env('SQUARE_SYNC_ORDERS', true),

        // Card payments and refunds. Empty = the account above. Set all four
        // SQUARE_PAYMENTS_* to a sandbox app to take test payments while the
        // catalogue still comes from the live account. See SquareGateway::payments().
        'payments' => [
            'env'         => env('SQUARE_PAYMENTS_ENV'),
            'token'       => env('SQUARE_PAYMENTS_ACCESS_TOKEN'),
            'location_id' => env('SQUARE_PAYMENTS_LOCATION_ID'),
            'app_id'      => env('SQUARE_PAYMENTS_APP_ID'),
        ],
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
