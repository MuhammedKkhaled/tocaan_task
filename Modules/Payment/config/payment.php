<?php

use Modules\Payment\Enums\PaymentMethod;
use Modules\Payment\Gateways\KashierGateway;
use Modules\Payment\Gateways\MyFatoorahGateway;
use Modules\Payment\Gateways\PaymobGateway;

return [
    'gateways' => [
        'kashier' => [
            'driver' => KashierGateway::class,
            'methods' => [
                PaymentMethod::CreditCard->value,
            ],
        ],
        'paymob' => [
            'driver' => PaymobGateway::class,
            'methods' => [
                PaymentMethod::CreditCard->value,
                PaymentMethod::ApplePay->value,
            ],
        ],
        'myfatoorah' => [
            'driver' => MyFatoorahGateway::class,
            'methods' => PaymentMethod::values(),
        ],
    ],
];
