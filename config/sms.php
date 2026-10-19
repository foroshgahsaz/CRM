<?php

return [

    'gateway' => env('SMS_GATEWAY', 'log'),

    'log_codes' => (bool) env('SMS_LOG_CODES', false),

    'http' => [
        'url' => env('SMS_HTTP_URL'),
        'token' => env('SMS_HTTP_TOKEN'),
        'sender' => env('SMS_SENDER'),
        'timeout' => (int) env('SMS_HTTP_TIMEOUT', 10),
    ],

];
