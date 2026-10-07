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
        'server_key'   => env('CLICKPAY_SERVER_KEY'),     // no default: this exact value was committed to source control and must be rotated with ClickPay
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
        'api_key' => env('TAMARA_API_KEY'),   // no default: this exact value was committed to source control and must be rotated with Tamara
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
        'client_id'     => env('DY_CLIENT_ID'),       // no default: committed to source control, rotate with DY365
        'client_secret' => env('DY_CLIENT_SECRET'),   // no default, same reason
    ],

    'taqnyat' => [
        'api_key'      => env('TAQNYAT_SMS_API_KEY'),     // no default: this exact value was committed to source control and must be rotated with Taqnyat
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
        'username' => env('POWERBI_USERNAME'),   // no default: this exact value (and a weak password) was committed to source control
        'password' => env('POWERBI_PASSWORD'),   // no default, same reason
    ],

    'fcm' => [
        'credentials' => env('FIREBASE_CREDENTIALS') ?? storage_path('app/firebase/firebase_cred.json'),
        'project_id' => env('FIREBASE_PROJECT_ID', 'naqi-tech') ?? "naqi-tech",
    ],

    'cloudfront' => [
        'url' => env('CLOUDFRONT_URL'),
    ],

    'telegram' => [

        // Previously hardcoded directly here (not even via env()): that exact bot token is committed to
        // source control and must be revoked/regenerated with @BotFather.
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id'   => env('TELEGRAM_CHAT_ID'),
    ],

];
