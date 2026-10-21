<?php

use Modules\Payment\Gateways\CreditCardGateway;
use Modules\Payment\Gateways\PaypalGateway;

return [
    'default' => env('PAYMENT_DEFAULT_GATEWAY', 'credit_card'),

    'gateways' => [
        'credit_card' => [
            'driver' => CreditCardGateway::class,
            'api_key' => env('CREDIT_CARD_API_KEY'),
            'secret' => env('CREDIT_CARD_SECRET'),
            'sandbox' => env('CREDIT_CARD_SANDBOX', true),
        ],
        'paypal' => [
            'driver' => PaypalGateway::class,
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'secret' => env('PAYPAL_SECRET'),
            'sandbox' => env('PAYPAL_SANDBOX', true),
        ],
    ],
];
