<?php

namespace Modules\Payment\Gateways;

use Modules\Payment\Enums\PaymentGateway;

final class PaymobGateway extends SimulatedGateway
{
    public function gateway(): PaymentGateway
    {
        return PaymentGateway::Paymob;
    }
}
