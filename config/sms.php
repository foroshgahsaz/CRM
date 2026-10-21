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

    'smsir' => [
        'api_key' => env('SMSIR_API_KEY'),
        'line_number' => env('SMSIR_LINE_NUMBER'),
        'otp_template_id' => env('SMSIR_OTP_TEMPLATE_ID', env('SMSIR_TEMPLATE_ID')),
        'otp_parameter_name' => env('SMSIR_OTP_PARAMETER_NAME', env('SMSIR_OTP_PARAMETER', 'CODE')),
    ],

];
