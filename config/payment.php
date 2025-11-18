<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Gateway
    |--------------------------------------------------------------------------
    |
    | Driver name registered inside `gateways` array below.
    |
    */
    'default' => env('PAYMENT_GATEWAY', 'zarinpal'),

    /*
    |--------------------------------------------------------------------------
    | Currency & Amount Normalization
    |--------------------------------------------------------------------------
    */
    'currency' => env('PAYMENT_CURRENCY', 'IRT'),
    'amount_scale' => env('PAYMENT_AMOUNT_SCALE', 1),
    'log_table' => env('PAYMENT_LOG_TABLE', 'payment_transactions'),

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Integration
    |--------------------------------------------------------------------------
    */
    'admin' => [
        'enabled' => env('PAYMENT_ADMIN_ENABLED', true),
        'prefix' => env('PAYMENT_ADMIN_PREFIX', 'payment'),
        'route_name' => 'payment',
        'middleware' => [
            'web',
            \RMS\Core\Middleware\AdminAuthenticate::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Callback URLs & Certificates
    |--------------------------------------------------------------------------
    */
    'callback_url' => env('PAYMENT_CALLBACK_URL', '/payment/callback'),
    'return_url' => env('PAYMENT_RETURN_URL', '/payment/return'),
    'certificates_path' => env('PAYMENT_CERT_PATH', storage_path('payment/certs')),

    /*
    |--------------------------------------------------------------------------
    | Registered Gateways
    |--------------------------------------------------------------------------
    */
    'gateways' => [
        'sandbox' => [
            'driver' => RMS\Payment\Gateways\SandboxGateway::class,
            'merchant_id' => env('PAYMENT_SANDBOX_MERCHANT', 'sandbox-merchant'),
            'secret_key' => env('PAYMENT_SANDBOX_SECRET', 'sandbox-secret'),
            'mode' => env('PAYMENT_SANDBOX_MODE', 'form'), // redirect|form
            'gateway_url' => env('PAYMENT_SANDBOX_GATEWAY_URL', '/payment/sandbox/gateway'),
            'title' => 'Sandbox Gateway',
            'description' => 'درگاه شبیه‌ساز داخلی برای تست روند پرداخت.',
            'logo' => null,
            'active' => true,
            'settings' => [],
            'migrations' => [
                // مثال: 'packages/rms/payment/database/migrations'
            ],
        ],
        'zarinpal' => [
            'driver' => RMS\Payment\Gateways\ZarinpalGateway::class,
            'merchant_id' => env('PAYMENT_ZARINPAL_MERCHANT', 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX'),
            'sandbox' => (bool) env('PAYMENT_ZARINPAL_SANDBOX', true),
            'description' => env('PAYMENT_ZARINPAL_DESCRIPTION', 'پرداخت سندباکس فروشگاه'),
            'default_currency' => env('PAYMENT_ZARINPAL_CURRENCY', 'IRT'),
            'start_pay_url' => env('PAYMENT_ZARINPAL_STARTPAY_URL'),
            'log_table' => env('PAYMENT_LOG_TABLE', 'payment_transactions'),
            'title' => 'زرین‌پال',
            'logo' => null,
            'active' => env('PAYMENT_ZARINPAL_ACTIVE', true),
            'settings' => [],
            'migrations' => [
                'packages/rms/payment/database/migrations',
            ],
        ],
    ],
];

