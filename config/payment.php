<?php

return [
    'default_gateway' => env('DEFAULT_PAYMENT_GATEWAY', 'stripe'),

    'gateways' => [
        'stripe' => [
            'class' => \App\Services\Payment\Gateways\Stripe::class,
            'config' => [
                'key' => env('STRIPE_KEY'),
                'secret' => env('STRIPE_SECRET'),
                'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
                'mode' => env('STRIPE_MODE', 'test'),
                'currency' => env('STRIPE_CURRENCY', 'usd'),
            ]
        ],
        'paypal' => [
            'class' => \App\Services\Payment\Gateways\PayPal::class,
            'config' => [
                'client_id' => env('PAYPAL_CLIENT_ID'),
                'client_secret' => env('PAYPAL_CLIENT_SECRET'),
                'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
                'mode' => env('PAYPAL_MODE', 'sandbox'),
                'currency' => env('PAYPAL_CURRENCY', 'USD'),
            ]
        ],
    ],
];
