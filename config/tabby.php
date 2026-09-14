<?php

return [

    'webhook' => [
        'header' => env(
            'TABBY_WEBHOOK_HEADER',
            'X-Tabby-Webhook-Secret'
        ),

        'secret' => env('TABBY_WEBHOOK_SECRET'),
    ],

];
