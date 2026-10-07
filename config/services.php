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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'clickpay' => [
        'base_url'     => env('CLICKPAY_BASE_URL', 'https://secure.clickpay.com.sa'),
        'server_key'   => env('CLICKPAY_SERVER_KEY', 'SBJNL9NZ2R-J6NZLTJKDZ-GZGJTKTLMG'),
        'profile_id'   => env('CLICKPAY_PROFILE_ID', 43354),
        'public_url'   => env('CLICKPAY_PUBLIC_URL', 'https://prod-api.naqi.sa'),
    ],

    'tabby' => [
        'secret_key'    => env('TABBY_SECRET_KEY'),      // no default: a missing key must fail loudly, never silently fall back to a stale one
        'public_key'    => env('TABBY_PUBLIC_KEY'),       // no default, same reason
        'merchant_code' => env('TABBY_MERCHANT_CODE', 'Naqiappsau'),
        'base_url'      => env('TABBY_BASE_URL', 'https://api.tabby.ai/api/v2/'),


        'webhook' => [
            'header' => env('TABBY_WEBHOOK_HEADER', 'X-Tabby-Webhook-Secret'),
            'secret' => env('TABBY_WEBHOOK_SECRET'),          // no default: this exact value was committed to source control and must be rotated with Tabby, not reused
        ],
    ],

    'tamara' => [
        'api_url' => env('TAMARA_API_URL', 'https://api.tamara.co/'),
        'api_key' => env('TAMARA_API_KEY', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhY2NvdW50SWQiOiI0YTE0MTRmNi00YzIxLTRjYTEtYWQ5Ny1hNjI0YzJlYTc4MGYiLCJ0eXBlIjoibWVyY2hhbnQiLCJzYWx0IjoiY2Q4ODJkMGJlYWNlZGQ5NjJhZTBkODA0YjJmNDY1ZDkiLCJpYXQiOjE2Nzc2NDI5OTIsImlzcyI6IlRhbWFyYSBQUCJ9.nDy-pqpIx8Cc9iUaK9tzu89-JRdQJDRcWF7nXAaHfwRj8VNK2zHh07Rba0VGdVCczYBQq4PzAju05X-yDef-uUGvFgI9pLNpauItON4ci51qtIllRP5Pntv0lMXDZXngkvtT8wXRWOxiIwRav-7k4PQnKSQyCImCkUhBWQ5i_f8UnLa2BwXJvsCPRBJjd2d2fP4PHcUh3i7KOoEJoTlLfUG7MjW4GGdPY4lTZB9RHLXYY4f1G02aGMWuhzItksLlch5yMg2tdvQoFPTw7BtZZ1f5s81ESQydE-Yw71Q4sE15mU22KBOdczfP1rQ-9Gf70TFAmbzma7JU7SDr3hxMxQ'),
        // OPTIONAL. If set, a Tamara JWT signed with this key counts as an authenticated notification. Webhook
        // processing never depends on it: Tamara's API is always asked before a payment is changed.
        'notification_token' => env('TAMARA_NOTIFICATION_TOKEN'),

        // HTTP limits for every call to Tamara (the client used to have none, so a hung call held a PHP worker).
        'timeout'         => (int) env('TAMARA_HTTP_TIMEOUT', 20),
        'connect_timeout' => (int) env('TAMARA_HTTP_CONNECT_TIMEOUT', 5),

        // The webhook this application registers with Tamara (php artisan tamara:webhook:register).
        'webhook' => [
            'type'   => 'order',
            // Event names to subscribe to. Tamara validates them and answers HTTP 400 "Invalid registered event X" for a
            // name it does not accept (it rejected order_updated). Override with a comma-separated
            // TAMARA_WEBHOOK_EVENTS; the full Tamara error is logged and printed by tamara:webhook:register. Processing is event-agnostic: the
            // name is only logged, Tamara's order status decides.
            'events' => array_values(array_filter(array_map('trim', explode(',', (string) env(
                'TAMARA_WEBHOOK_EVENTS',
                'order_approved,order_authorised,order_canceled,order_captured,order_refunded'
            ))))),
            // Hosts the webhook URL may be updated to through the API. Default (empty): only the host of APP_URL.
            'allowed_hosts' => array_values(array_filter(array_map('trim', explode(',', (string) env('TAMARA_WEBHOOK_ALLOWED_HOSTS', ''))))),
        ],
    ],

    'dy365' => [
        'client_id'     => env('DY_CLIENT_ID', '71b8304b-aed3-4cc8-975f-52dc7fde65c7'),
        'client_secret' => env('DY_CLIENT_SECRET', 'UX08Q~v2TnzlyWVTj-UQzGU4d4O4Xz9ROaUFeb8N'),
    ],

    'taqnyat' => [
        'api_key'      => env('TAQNYAT_SMS_API_KEY', '1dc224c44e3a2950d88cdaafa9dbc9e3'),
        'sender_tech'  => env('TAQNYAT_SMS_SENDER_TECH', 'Naqi-Tech'),
        'sender_care'  => env('TAQNYAT_SMS_SENDER_CARE', 'Naqi-Care'),
    ],

    'whatsapp' => [
        'token' => env('WHATSAPP_ACCESS_TOKEN', ''),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN', ''),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
    ],

    'export' => [
        'api_key' => env('EXPORT_API_KEY'),
    ],

    'powerbi' => [
        'username' => env('POWERBI_USERNAME', 'bi@admin.com'),
        'password' => env('POWERBI_PASSWORD', 'bi@@admin@@'),
    ],

    'fcm' => [
        'credentials' => env('FIREBASE_CREDENTIALS') ?? storage_path('app/firebase/firebase_cred.json'),
        'project_id' => env('FIREBASE_PROJECT_ID', 'naqi-tech') ?? "naqi-tech",
    ],

    'cloudfront' => [
        'url' => env('CLOUDFRONT_URL'),
    ],

    'telegram' => [

        'bot_token' => '8506550704:AAHYtIX343DpYpAUvCJEPaszC0mjusu0BuI',
        'chat_id' => '-1004380688068',
    ],

];
