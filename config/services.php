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
        'base_url'     => env('CLICKPAY_BASE_URL'),
        'server_key'   => env('CLICKPAY_SERVER_KEY'),
        'profile_id'   => env('CLICKPAY_PROFILE_ID'),
        'public_url'   => env('CLICKPAY_PUBLIC_URL'),
    ],

    'tabby' => [
        'secret_key'    => env('TABBY_SECRET_KEY'),
        'public_key'    => env('TABBY_PUBLIC_KEY'),
        'merchant_code' => env('TABBY_MERCHANT_CODE'),
        'base_url'      => env('TABBY_BASE_URL'),


        'webhook' => [
            'header' => env('TABBY_WEBHOOK_HEADER'),
            'secret' => env('TABBY_WEBHOOK_SECRET'),
        ],
    ],

    'tamara' => [
        'api_url' => env('TAMARA_API_URL'),
        'api_key' => env('TAMARA_API_KEY'),
    ],

    'dy365' => [
        'client_id'     => env('DY_CLIENT_ID'),
        'client_secret' => env('DY_CLIENT_SECRET'),
    ],

    'taqnyat' => [
        'api_key'      => env('TAQNYAT_SMS_API_KEY'),
        'sender_tech'  => env('TAQNYAT_SMS_SENDER_TECH'),
        'sender_care'  => env('TAQNYAT_SMS_SENDER_CARE'),
    ],

    'whatsapp' => [
        'token' => env('WHATSAPP_ACCESS_TOKEN'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
    ],

    'export' => [
        'api_key' => env('EXPORT_API_KEY'),
    ],

    'powerbi' => [
        'username' => env('POWERBI_USERNAME'),
        'password' => env('POWERBI_PASSWORD'),
    ],

    'fcm' => [
        'credentials' => env('FIREBASE_CREDENTIALS'),
        'project_id' => env('FIREBASE_PROJECT_ID'),
    ],

    'cloudfront' => [
        'url' => env('CLOUDFRONT_URL'),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
    ],

];
